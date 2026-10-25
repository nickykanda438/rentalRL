<?php

namespace App\Models;

use App\Models\Concerns\ASourceArgent;
use App\Models\Concerns\AUnNumero;
use Illuminate\Validation\ValidationException;
use App\Support\Format;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Bulletin extends Model
{
    use ASourceArgent, AUnNumero;

    /** Numéro automatique : SAL-AAAAMM-0001 */
    public const PREFIXE_NUMERO = 'SAL';

    protected $table = 'bulletins';

    protected $fillable = [
        'employe_id', 'mois', 'salaire_base', 'primes', 'total_avances', 'total_credits', 'net_a_payer',
        'statut', 'date_paiement', 'mode_paiement', 'reference', 'source', 'autre_revenu_id',
    ];

    protected function casts(): array
    {
        return [
            'salaire_base' => 'decimal:2',
            'primes' => 'decimal:2',
            'total_avances' => 'decimal:2',
            'total_credits' => 'decimal:2',
            'net_a_payer' => 'decimal:2',
            'date_paiement' => 'date',
        ];
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    /** Retenues de crédit appliquées sur ce bulletin. */
    public function remboursements(): HasMany
    {
        return $this->hasMany(CreditRemboursement::class);
    }

    public function estPaye(): bool
    {
        return $this->statut === 'Payé';
    }

    public function couleurStatut(): string
    {
        return $this->estPaye() ? 'green' : 'orange';
    }

    /**
     * Crée ou recalcule le bulletin d'un employé pour un mois.
     * Ne touche jamais à un bulletin déjà payé (retourne null dans ce cas).
     *
     * Net = salaire de base + primes − avances du mois − mensualités de crédit.
     */
    public static function preparer(Employe $employe, string $mois): ?self
    {
        return DB::transaction(function () use ($employe, $mois) {
            $bulletin = self::firstOrNew(['employe_id' => $employe->id, 'mois' => $mois]);

            if ($bulletin->estPaye()) {
                return null;
            }

            $bulletin->salaire_base = $employe->salaire;
            $bulletin->primes ??= 0;
            $bulletin->total_avances = $employe->avances()->where('mois', $mois)->sum('montant');
            $bulletin->total_credits = 0;
            $bulletin->net_a_payer = 0;
            $bulletin->statut = 'En attente';
            $bulletin->save();

            // On refait les retenues de crédit de ce bulletin
            $bulletin->remboursements()->delete();
            $totalCredits = 0;

            $credits = $employe->credits()
                ->with('remboursements.bulletin')
                ->where('statut', 'Actif')
                ->where('mois_debut', '<=', $mois)
                ->get();

            foreach ($credits as $credit) {
                $retenue = min((float) $credit->mensualite, $credit->reste());
                if ($retenue > 0) {
                    $bulletin->remboursements()->create(['credit_id' => $credit->id, 'montant' => $retenue]);
                    $totalCredits += $retenue;
                }
            }

            $bulletin->total_credits = $totalCredits;
            $bulletin->calculerNet();
            $bulletin->save();

            return $bulletin;
        });
    }

    /**
     * Paie le salaire d'un employé pour un mois : prépare le bulletin à jour, le marque payé,
     * déduit les avances du mois et solde les crédits remboursés.
     * Le net payé sort de la source choisie (recettes de location ou autre revenu) : si le revenu
     * n'a pas assez de reste, rien n'est enregistré.
     * Retourne null si le salaire de ce mois est déjà payé.
     *
     * @param  array{date_paiement: string, mode_paiement: string, reference?: ?string, primes?: float|string|null, source?: string, autre_revenu_id?: ?int}  $donnees
     */
    public static function payerPour(Employe $employe, string $mois, array $donnees): ?self
    {
        return DB::transaction(function () use ($employe, $mois, $donnees) {
            $bulletin = self::preparer($employe, $mois);
            if (! $bulletin) {
                return null; // déjà payé
            }

            $bulletin->fill([
                'primes' => $donnees['primes'] ?? $bulletin->primes,
                'date_paiement' => $donnees['date_paiement'],
                'mode_paiement' => $donnees['mode_paiement'],
                'reference' => $donnees['reference'] ?? null,
                'source' => $donnees['source'] ?? 'location',
                'autre_revenu_id' => ($donnees['source'] ?? 'location') === 'revenu' ? $donnees['autre_revenu_id'] : null,
                'statut' => 'Payé',
            ]);
            $bulletin->calculerNet();

            // Le revenu choisi doit couvrir le net (la transaction est annulée sinon)
            if ($bulletin->source === 'revenu') {
                $reste = AutreRevenu::findOrFail($bulletin->autre_revenu_id)->reste();
                if ((float) $bulletin->net_a_payer > $reste + 0.001) {
                    throw ValidationException::withMessages([
                        'source' => "Salaire de {$employe->nom} : il ne reste que ".Format::usd($reste).' $ sur ce revenu pour payer '.Format::usd($bulletin->net_a_payer).' $.',
                    ]);
                }
            }

            $bulletin->save();

            Avance::where('employe_id', $employe->id)->where('mois', $mois)->update(['statut' => 'Déduite']);

            foreach ($bulletin->remboursements()->with('credit.remboursements.bulletin')->get() as $ligne) {
                if ($ligne->credit->reste() <= 0) {
                    $ligne->credit->update(['statut' => 'Soldé']);
                }
            }

            return $bulletin;
        });
    }

    /**
     * Aperçu du salaire d'un mois SANS rien enregistrer (pour afficher avant de payer).
     *
     * @return array{deja_paye: bool, salaire: float, primes: float, avances: float, credits: float, net: float}
     */
    public static function apercu(Employe $employe, string $mois): array
    {
        $existant = self::where('employe_id', $employe->id)->where('mois', $mois)->first();
        if ($existant?->estPaye()) {
            return ['deja_paye' => true, 'salaire' => (float) $existant->salaire_base, 'primes' => (float) $existant->primes,
                'avances' => (float) $existant->total_avances, 'credits' => (float) $existant->total_credits, 'net' => (float) $existant->net_a_payer];
        }

        $avances = (float) $employe->avances()->where('mois', $mois)->sum('montant');
        $credits = $employe->credits()->with('remboursements.bulletin')
            ->where('statut', 'Actif')->where('mois_debut', '<=', $mois)->get()
            ->sum(fn (Credit $c) => min((float) $c->mensualite, $c->reste()));
        $primes = (float) ($existant?->primes ?? 0);
        $salaire = (float) $employe->salaire;

        return ['deja_paye' => false, 'salaire' => $salaire, 'primes' => $primes, 'avances' => $avances,
            'credits' => round($credits, 2), 'net' => round($salaire + $primes - $avances - $credits, 2)];
    }

    public function calculerNet(): void
    {
        $this->net_a_payer = round(
            (float) $this->salaire_base + (float) $this->primes - (float) $this->total_avances - (float) $this->total_credits,
            2
        );
    }

    /**
     * Recalcule les bulletins non payés d'un employé (après ajout/suppression d'une avance ou d'un crédit).
     */
    public static function recalculerPour(Employe $employe, string $depuisMois): void
    {
        self::where('employe_id', $employe->id)
            ->where('mois', '>=', $depuisMois)
            ->where('statut', '!=', 'Payé')
            ->orderBy('mois')
            ->pluck('mois')
            ->each(fn (string $mois) => self::preparer($employe, $mois));
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\AUnNumero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Revenu hors location (une personne remet de l'argent, vente, partenariat…).
 * Chaque revenu garde sa trace : montant reçu, montant utilisé (dépenses, avances, salaires, crédits), reste.
 */
class AutreRevenu extends Model
{
    use AUnNumero;

    /** Numéro automatique : REV-AAAAMM-0001 */
    public const PREFIXE_NUMERO = 'REV';

    public const MODES = ['Espèces', 'Mobile Money', 'Virement', 'Chèque'];

    protected $table = 'autres_revenus';

    protected $fillable = [
        'mois', 'date_revenu', 'source', 'description', 'devise', 'montant_saisi', 'taux_change',
        'montant_usd', 'montant_cdf', 'mode_reception', 'reference', 'piece_jointe',
    ];

    protected function casts(): array
    {
        return [
            'date_revenu' => 'date',
            'montant_saisi' => 'decimal:2',
            'taux_change' => 'decimal:2',
            'montant_usd' => 'decimal:2',
            'montant_cdf' => 'decimal:2',
        ];
    }

    /** Dépenses payées avec ce revenu. */
    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class, 'autre_revenu_id');
    }

    public function avances(): HasMany
    {
        return $this->hasMany(Avance::class, 'autre_revenu_id');
    }

    /** Salaires payés avec ce revenu. */
    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class, 'autre_revenu_id')->where('statut', 'Payé');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class, 'autre_revenu_id');
    }

    /** Charge en une requête les totaux utilisés (dépenses, avances, salaires, crédits). */
    public function scopeAvecUtilisation(Builder $query): Builder
    {
        return $query->withSum('depenses', 'montant_usd')->withSum('avances', 'montant')
            ->withSum('bulletins', 'net_a_payer')->withSum('credits', 'montant');
    }

    /**
     * Montant utilisé : dépenses + avances + salaires payés + crédits accordés.
     * $saufDepenseId : ignorer une dépense (quand on la modifie).
     */
    public function montantUtilise(?int $saufDepenseId = null): float
    {
        $charge = array_key_exists('depenses_sum_montant_usd', $this->attributes) && array_key_exists('credits_sum_montant', $this->attributes);

        $depenses = $charge && $saufDepenseId === null
            ? (float) $this->depenses_sum_montant_usd
            : (float) $this->depenses()->when($saufDepenseId, fn ($q) => $q->whereKeyNot($saufDepenseId))->sum('montant_usd');

        $paie = $charge
            ? (float) $this->avances_sum_montant + (float) $this->bulletins_sum_net_a_payer + (float) $this->credits_sum_montant
            : (float) $this->avances()->sum('montant') + (float) $this->bulletins()->sum('net_a_payer') + (float) $this->credits()->sum('montant');

        return round($depenses + $paie, 2);
    }

    /**
     * Toutes les sorties d'argent faites avec ce revenu, dans l'ordre chronologique (la trace).
     *
     * @return \Illuminate\Support\Collection<int, array{date: \Illuminate\Support\Carbon, numero: string, type: string, libelle: string, detail: ?string, montant: float, lien: ?string, couleur: string}>
     */
    public function mouvements(): \Illuminate\Support\Collection
    {
        $lignes = collect();

        foreach ($this->depenses()->with(['categorie', 'employe', 'vehicule'])->get() as $d) {
            $lignes->push(['date' => $d->date_depense, 'numero' => $d->numero, 'type' => $d->categorie->nom, 'couleur' => $d->categorie->couleur,
                'libelle' => $d->description, 'detail' => trim(($d->beneficiaire ? 'Payé à '.$d->beneficiaire : '').($d->employe ? ' · par '.$d->employe->nom : '').($d->vehicule ? ' · '.$d->vehicule->plaque : ''), ' ·'),
                'montant' => (float) $d->montant_usd, 'lien' => null, 'piece' => $d->piece_jointe, 'depense' => $d]);
        }
        foreach ($this->avances()->with('employe')->get() as $a) {
            $lignes->push(['date' => $a->date_avance, 'numero' => $a->numero, 'type' => 'Avance sur salaire', 'couleur' => 'orange',
                'libelle' => 'Avance à '.$a->employe->nom, 'detail' => $a->commentaire, 'montant' => (float) $a->montant,
                'lien' => route('employes.show', $a->employe), 'piece' => null, 'depense' => null]);
        }
        foreach ($this->bulletins()->with('employe')->get() as $b) {
            $lignes->push(['date' => $b->date_paiement, 'numero' => $b->numero, 'type' => 'Salaire', 'couleur' => 'lime',
                'libelle' => 'Salaire de '.$b->employe->nom.' · '.\App\Support\Format::mois($b->mois), 'detail' => $b->mode_paiement,
                'montant' => (float) $b->net_a_payer, 'lien' => route('paie.bulletins.imprimer', ['bulletin' => $b->id]), 'piece' => null, 'depense' => null]);
        }
        foreach ($this->credits()->with('employe')->get() as $c) {
            $lignes->push(['date' => $c->date_octroi, 'numero' => 'Crédit', 'type' => 'Crédit au personnel', 'couleur' => 'teal',
                'libelle' => 'Crédit à '.$c->employe->nom.' ('.$c->duree_mois.' mois)', 'detail' => $c->commentaire,
                'montant' => (float) $c->montant, 'lien' => route('employes.show', $c->employe), 'piece' => null, 'depense' => null]);
        }

        return $lignes->sortBy(fn ($l) => $l['date']->format('Y-m-d').$l['numero'])->values();
    }

    public function reste(?int $saufDepenseId = null): float
    {
        return round((float) $this->montant_usd - $this->montantUtilise($saufDepenseId), 2);
    }

    public function pourcentageUtilise(): int
    {
        return (float) $this->montant_usd > 0 ? (int) min(100, round($this->montantUtilise() / (float) $this->montant_usd * 100)) : 0;
    }

    public function statut(): string
    {
        return match (true) {
            $this->reste() <= 0 => 'Épuisé',
            $this->montantUtilise() > 0 => 'Entamé',
            default => 'Intact',
        };
    }

    public function couleurStatut(): string
    {
        return ['Épuisé' => 'gray', 'Entamé' => 'orange', 'Intact' => 'green'][$this->statut()];
    }

    public function scopeFiltrer(Builder $query, ?string $recherche, ?string $mois): Builder
    {
        return $query
            ->when($recherche, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('source', 'like', "%{$recherche}%")
                ->orWhere('description', 'like', "%{$recherche}%")
                ->orWhere('numero', 'like', "%{$recherche}%")))
            ->when($mois, fn (Builder $q) => $q->where('mois', $mois));
    }
}

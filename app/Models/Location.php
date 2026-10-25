<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    public const STATUTS = ['Actif', 'Terminé', 'Annulé'];

    protected $table = 'locations';

    protected $fillable = [
        'numero_contrat', 'client_id', 'chauffeur_id', 'vehicule_id', 'destination',
        'date_depart', 'nombre_jours', 'date_fin', 'date_retour',
        'montant_journalier', 'sous_total', 'pourcentage_commission', 'commission', 'total_usd', 'taux_change', 'total_cdf',
        'date_perception', 'statut', 'piece_jointe', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_depart' => 'date',
            'date_fin' => 'date',
            'date_retour' => 'date',
            'date_perception' => 'date',
            'montant_journalier' => 'decimal:2',
            'sous_total' => 'decimal:2',
            'pourcentage_commission' => 'decimal:2',
            'commission' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'taux_change' => 'decimal:2',
            'total_cdf' => 'decimal:2',
        ];
    }

    /**
     * Numéro de contrat automatique et unique (RG-02) : CONT-AAAA-000001, la suite repart à 1 chaque année.
     */
    protected static function booted(): void
    {
        static::creating(function (self $location) {
            if (blank($location->numero_contrat)) {
                $prefixe = 'CONT-'.$location->date_depart->format('Y').'-';
                $dernier = static::where('numero_contrat', 'like', $prefixe.'%')
                    ->lockForUpdate()->orderByDesc('numero_contrat')->value('numero_contrat');
                $suivant = $dernier ? (int) substr($dernier, -6) + 1 : 1;
                $location->numero_contrat = $prefixe.str_pad((string) $suivant, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'chauffeur_id');
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    /**
     * Calculs du cahier des charges (section 5) — la commission est déduite :
     * sous-total = journalier × jours ; commission = sous-total × % ; total USD = sous-total − commission ; total CDF = total USD × taux.
     *
     * @return array{sous_total: float, commission: float, total_usd: float, total_cdf: float}
     */
    public static function calculer(float $journalier, int $jours, float $pourcentage, float $taux): array
    {
        $sousTotal = round($journalier * $jours, 2);
        $commission = round($sousTotal * $pourcentage / 100, 2);
        $totalUsd = round($sousTotal - $commission, 2);

        return [
            'sous_total' => $sousTotal,
            'commission' => $commission,
            'total_usd' => $totalUsd,
            'total_cdf' => round($totalUsd * $taux, 2),
        ];
    }

    /** RG-05 : en retard si la date de fin est dépassée sans retour enregistré. */
    public function estEnRetard(): bool
    {
        return $this->statut === 'Actif' && $this->date_retour === null && $this->date_fin->isBefore(today());
    }

    public function statutAffiche(): string
    {
        return $this->estEnRetard() ? 'En retard' : $this->statut;
    }

    public function couleurStatut(): string
    {
        return match ($this->statutAffiche()) {
            'Actif' => 'green',
            'En retard' => 'red',
            'Terminé' => 'teal',
            default => 'gray',
        };
    }

    /**
     * Filtres de la liste : recherche (contrat, client, destination, plaque), mois de départ, statut.
     */
    public function scopeFiltrer(Builder $query, ?string $recherche, ?string $mois, ?string $statut): Builder
    {
        return $query
            ->when($recherche, fn (Builder $q) => $q->where(function (Builder $q) use ($recherche) {
                $q->where('numero_contrat', 'like', "%{$recherche}%")
                    ->orWhere('destination', 'like', "%{$recherche}%")
                    ->orWhereHas('client', fn (Builder $c) => $c->where('nom', 'like', "%{$recherche}%")->orWhere('telephone', 'like', "%{$recherche}%"))
                    ->orWhereHas('vehicule', fn (Builder $v) => $v->where('plaque', 'like', "%{$recherche}%")->orWhere('modele', 'like', "%{$recherche}%"));
            }))
            ->when($mois, fn (Builder $q) => $q->whereBetween('date_depart', [$mois.'-01', date('Y-m-t', strtotime($mois.'-01'))]))
            ->when($statut === 'En retard', fn (Builder $q) => $q->where('statut', 'Actif')->whereNull('date_retour')->whereDate('date_fin', '<', today()))
            ->when(in_array($statut, self::STATUTS, true), fn (Builder $q) => $q->where('statut', $statut));
    }

    /**
     * RG-04 : locations actives du même véhicule dont la période chevauche [début, fin[.
     */
    public static function chevauchements(int $vehiculeId, string $debut, string $fin, ?int $saufId = null): Builder
    {
        return static::query()
            ->where('vehicule_id', $vehiculeId)
            ->where('statut', 'Actif')
            ->whereNull('date_retour')
            ->when($saufId, fn (Builder $q) => $q->whereKeyNot($saufId))
            ->whereDate('date_depart', '<', $fin)
            ->whereDate('date_fin', '>', $debut);
    }
}

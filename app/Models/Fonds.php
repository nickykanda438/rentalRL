<?php

namespace App\Models;

use App\Models\Concerns\AUnNumero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Somme d'argent reçue en dehors des recettes de location (ex. une personne remet de l'argent à l'entreprise).
 * On suit ce qui a été dépensé dessus et ce qu'il reste.
 */
class Fonds extends Model
{
    use AUnNumero;

    /** Numéro automatique : FDS-AAAAMM-0001 */
    public const PREFIXE_NUMERO = 'FDS';

    public const MODES = ['Espèces', 'Mobile Money', 'Virement', 'Chèque'];

    protected $table = 'fonds';

    protected $fillable = [
        'mois', 'date_reception', 'provenance', 'motif', 'devise', 'montant_saisi', 'taux_change',
        'montant_usd', 'montant_cdf', 'mode_reception', 'reference', 'piece_jointe', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_reception' => 'date',
            'montant_saisi' => 'decimal:2',
            'taux_change' => 'decimal:2',
            'montant_usd' => 'decimal:2',
            'montant_cdf' => 'decimal:2',
        ];
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class);
    }

    /** Total dépensé (utilise withSum('depenses', 'montant_usd') si déjà chargé). */
    public function montantDepense(?int $saufDepenseId = null): float
    {
        if ($saufDepenseId === null && array_key_exists('depenses_sum_montant_usd', $this->attributes)) {
            return (float) $this->depenses_sum_montant_usd;
        }

        return (float) $this->depenses()->when($saufDepenseId, fn ($q) => $q->whereKeyNot($saufDepenseId))->sum('montant_usd');
    }

    public function reste(?int $saufDepenseId = null): float
    {
        return round((float) $this->montant_usd - $this->montantDepense($saufDepenseId), 2);
    }

    /** % déjà utilisé (barre de progression). */
    public function pourcentageUtilise(): int
    {
        return (float) $this->montant_usd > 0 ? (int) min(100, round($this->montantDepense() / (float) $this->montant_usd * 100)) : 0;
    }

    public function statut(): string
    {
        return $this->reste() <= 0 ? 'Épuisé' : 'Disponible';
    }

    /** « FDS-202610-0001 · Papa Kanda — Achat pneus (reste 150,00 $) » */
    public function libelle(): string
    {
        return "{$this->numero} · {$this->provenance} — {$this->motif}";
    }
}

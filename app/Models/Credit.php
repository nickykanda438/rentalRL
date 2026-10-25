<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credit extends Model
{
    protected $table = 'credits';

    protected $fillable = [
        'employe_id', 'montant', 'taux', 'duree_mois', 'montant_total', 'mensualite',
        'date_octroi', 'mois_debut', 'commentaire', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'taux' => 'decimal:2',
            'montant_total' => 'decimal:2',
            'mensualite' => 'decimal:2',
            'date_octroi' => 'date',
        ];
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(CreditRemboursement::class);
    }

    /**
     * Calcule le total à rembourser et la mensualité (intérêt simple).
     * Ex. 500 $ à 10 % sur 10 mois → total 550 $, mensualité 55 $.
     *
     * @return array{montant_total: float, mensualite: float}
     */
    public static function calculer(float $montant, float $taux, int $dureeMois): array
    {
        $total = round($montant * (1 + $taux / 100), 2);

        return [
            'montant_total' => $total,
            'mensualite' => round($total / max(1, $dureeMois), 2),
        ];
    }

    /**
     * Montant déjà remboursé : seules les retenues des bulletins payés comptent.
     */
    public function montantRembourse(): float
    {
        return (float) $this->remboursements
            ->filter(fn (CreditRemboursement $r) => $r->bulletin_id === null || $r->bulletin?->statut === 'Payé')
            ->sum('montant');
    }

    public function reste(): float
    {
        return max(0, round((float) $this->montant_total - $this->montantRembourse(), 2));
    }

    /**
     * Le crédit a-t-il déjà été remboursé en partie (bulletin payé) ?
     */
    public function aDesRemboursements(): bool
    {
        return $this->montantRembourse() > 0;
    }

    public function couleurStatut(): string
    {
        return $this->statut === 'Soldé' ? 'green' : 'teal';
    }
}

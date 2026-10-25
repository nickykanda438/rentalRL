<?php

namespace App\Models;

use App\Models\Concerns\AUnNumero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    use AUnNumero;

    /** Numéro automatique : DEP-AAAAMM-0001 */
    public const PREFIXE_NUMERO = 'DEP';

    /** Avec quel argent la dépense est payée. */
    public const SOURCES = ['location' => 'Recettes de location', 'revenu' => 'Autre revenu'];

    public const MODES = ['Espèces', 'Mobile Money', 'Virement', 'Chèque'];

    protected $table = 'depenses';

    protected $fillable = [
        'mois', 'date_depense', 'categorie_id', 'description', 'beneficiaire', 'source', 'autre_revenu_id',
        'vehicule_id', 'employe_id', 'devise', 'montant_saisi', 'taux_change', 'montant_usd', 'montant_cdf',
        'mode_paiement', 'piece_jointe', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'date_depense' => 'date',
            'montant_saisi' => 'decimal:2',
            'taux_change' => 'decimal:2',
            'montant_usd' => 'decimal:2',
            'montant_cdf' => 'decimal:2',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieDepense::class, 'categorie_id');
    }

    /** Autre revenu avec lequel la dépense est payée (source « revenu »). */
    public function autreRevenu(): BelongsTo
    {
        return $this->belongsTo(AutreRevenu::class, 'autre_revenu_id');
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    /** Employé qui a effectué la dépense (ou concerné par elle). */
    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    public function sourceLibelle(): string
    {
        return $this->source === 'revenu' && $this->autreRevenu
            ? "{$this->autreRevenu->numero} · {$this->autreRevenu->source}"
            : self::SOURCES['location'];
    }

    /**
     * Filtres : recherche, mois, catégorie, source (« location », « revenu » ou « revenu-ID »).
     */
    public function scopeFiltrer(Builder $query, ?string $recherche, ?string $mois, ?string $categorie, ?string $source): Builder
    {
        return $query
            ->when($recherche, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('description', 'like', "%{$recherche}%")
                ->orWhere('beneficiaire', 'like', "%{$recherche}%")
                ->orWhere('numero', 'like', "%{$recherche}%")
                ->orWhereHas('autreRevenu', fn (Builder $r) => $r->where('source', 'like', "%{$recherche}%")->orWhere('numero', 'like', "%{$recherche}%"))))
            ->when($mois, fn (Builder $q) => $q->where('mois', $mois))
            ->when($categorie, fn (Builder $q) => $q->where('categorie_id', $categorie))
            ->when($source === 'location', fn (Builder $q) => $q->where('source', 'location'))
            ->when($source === 'revenu', fn (Builder $q) => $q->where('source', 'revenu'))
            ->when(str_starts_with((string) $source, 'revenu-'), fn (Builder $q) => $q->where('autre_revenu_id', (int) substr($source, 7)));
    }
}

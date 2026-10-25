<?php

namespace App\Models;

use App\Models\Concerns\ASourceArgent;
use App\Models\Concerns\AUnNumero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avance extends Model
{
    use ASourceArgent, AUnNumero;

    /** Numéro automatique : AV-AAAAMM-0001 */
    public const PREFIXE_NUMERO = 'AV';

    public const MODES_PAIEMENT = ['Espèces', 'Mobile Money', 'Virement', 'Chèque'];

    protected $table = 'avances';

    protected $fillable = [
        'employe_id', 'montant', 'date_avance', 'mois', 'mode_paiement', 'reference', 'commentaire', 'statut',
        'source', 'autre_revenu_id',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_avance' => 'date',
        ];
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    public function couleurStatut(): string
    {
        return $this->statut === 'Déduite' ? 'green' : 'orange';
    }
}

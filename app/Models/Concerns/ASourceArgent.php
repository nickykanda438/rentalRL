<?php

namespace App\Models\Concerns;

use App\Models\AutreRevenu;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pour une sortie d'argent (avance, salaire, crédit) : colonnes « source » (location / revenu) et « autre_revenu_id ».
 */
trait ASourceArgent
{
    public function autreRevenu(): BelongsTo
    {
        return $this->belongsTo(AutreRevenu::class, 'autre_revenu_id');
    }

    /** « Recettes de location » ou « REV-202610-0001 · Nicky » */
    public function sourceLibelle(): string
    {
        return $this->source === 'revenu' && $this->autreRevenu
            ? "{$this->autreRevenu->numero} · {$this->autreRevenu->source}"
            : 'Recettes de location';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditRemboursement extends Model
{
    protected $table = 'credit_remboursements';

    protected $fillable = ['credit_id', 'bulletin_id', 'montant'];

    protected function casts(): array
    {
        return ['montant' => 'decimal:2'];
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class);
    }
}

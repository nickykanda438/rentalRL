<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = ['nom', 'telephone', 'adresse'];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}

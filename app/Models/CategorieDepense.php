<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieDepense extends Model
{
    public const COULEURS = ['green' => 'Vert', 'orange' => 'Orange', 'red' => 'Rouge', 'teal' => 'Bleu-vert', 'lime' => 'Citron', 'gray' => 'Gris'];

    protected $table = 'categories_depense';

    protected $fillable = ['nom', 'couleur', 'par_defaut', 'active'];

    protected function casts(): array
    {
        return ['par_defaut' => 'boolean', 'active' => 'boolean'];
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class, 'categorie_id');
    }
}

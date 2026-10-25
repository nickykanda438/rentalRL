<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicule extends Model
{
    public const CATEGORIES = ['Économique', 'Compacte', 'Berline', 'SUV', 'Pick-up', 'Minibus', 'Luxe'];

    public const STATUTS = ['Disponible', 'Loué', 'En maintenance'];

    protected $table = 'vehicules';

    protected $fillable = ['marque', 'modele', 'annee', 'plaque', 'tarif_journalier', 'categorie', 'statut', 'photo'];

    protected function casts(): array
    {
        return ['tarif_journalier' => 'decimal:2'];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /** Location active pas encore rentrée (VE-02). */
    public function locationEnCours(): HasOne
    {
        return $this->hasOne(Location::class)
            ->where('statut', 'Actif')->whereNull('date_retour')
            ->orderBy('date_depart');
    }

    /** « Toyota Land Cruiser Prado · CG 1234 AB » */
    public function libelle(): string
    {
        return trim("{$this->marque} {$this->modele}").' · '.$this->plaque;
    }

    /**
     * Met le statut à jour selon les locations : « Loué » tant qu'une location
     * active n'est pas rentrée, sinon « Disponible ». Un véhicule en maintenance n'est pas touché.
     */
    public function synchroniserStatut(): void
    {
        if ($this->statut === 'En maintenance') {
            return;
        }

        $loue = $this->locations()->where('statut', 'Actif')->whereNull('date_retour')->exists();
        $this->update(['statut' => $loue ? 'Loué' : 'Disponible']);
    }

    public function couleurStatut(): string
    {
        return match ($this->statut) {
            'Disponible' => 'green',
            'Loué' => 'orange',
            default => 'red',
        };
    }
}

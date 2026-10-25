<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employe extends Model
{
    /** Listes de valeurs utilisées dans les formulaires et la validation. */
    public const ROLES = ['Chauffeur', 'Manager', 'Comptable', 'Maintenance', 'Administrateur'];

    public const STATUTS = ['Actif', 'Suspendu', 'Licencié'];

    public const GENRES = ['Homme', 'Femme'];

    public const ETATS_CIVILS = ['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf(ve)'];

    public const NIVEAUX_ETUDES = ["Diplôme d'État", 'Gradué', 'Licence', 'Master', 'Doctorat'];

    protected $table = 'employes';

    protected $fillable = [
        'nom', 'email', 'telephone', 'poste', 'role', 'salaire', 'date_embauche', 'statut', 'lieu_travail',
        'genre', 'date_naissance', 'lieu_naissance', 'etat_civil', 'niveau_etudes', 'domaine', 'adresse',
        'province', 'district', 'territoire', 'personne_reference', 'contact_reference',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'salaire' => 'decimal:2',
            'date_embauche' => 'date',
            'date_naissance' => 'date',
        ];
    }

    public function avances(): HasMany
    {
        return $this->hasMany(Avance::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function bulletins(): HasMany
    {
        return $this->hasMany(Bulletin::class);
    }

    /** Locations où l'employé est le chauffeur. */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'chauffeur_id');
    }

    /**
     * L'employé a-t-il un historique de paie (avance, crédit ou bulletin) ?
     */
    public function aUnHistoriqueDePaie(): bool
    {
        return $this->avances()->exists() || $this->credits()->exists() || $this->bulletins()->exists();
    }

    /**
     * Couleur du badge selon le statut (composant x-badge).
     */
    public function couleurStatut(): string
    {
        return match ($this->statut) {
            'Actif' => 'green',
            'Suspendu' => 'orange',
            default => 'red',
        };
    }

    /**
     * Salaire au format « 1 500,00 ».
     */
    public function salaireFormate(): string
    {
        return number_format((float) $this->salaire, 2, ',', ' ');
    }

    /**
     * Filtre : recherche texte, rôle et statut.
     */
    public function scopeFiltrer(Builder $query, ?string $recherche, ?string $role, ?string $statut): Builder
    {
        return $query
            ->when($recherche, fn (Builder $q) => $q->where(function (Builder $q) use ($recherche) {
                $q->where('nom', 'like', "%{$recherche}%")
                    ->orWhere('poste', 'like', "%{$recherche}%")
                    ->orWhere('telephone', 'like', "%{$recherche}%")
                    ->orWhere('email', 'like', "%{$recherche}%");
            }))
            ->when(in_array($role, self::ROLES, true), fn (Builder $q) => $q->where('role', $role))
            ->when(in_array($statut, self::STATUTS, true), fn (Builder $q) => $q->where('statut', $statut));
    }
}

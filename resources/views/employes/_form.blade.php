{{-- Champs du formulaire employé, partagés par l'ajout et la modification.
     $e : employé à modifier (null pour un nouvel employé) ; $p : préfixe unique des id. --}}
@php $e = $e ?? null; @endphp
<input type="hidden" name="_form" value="{{ $p }}">
<p class="text-sm font-bold">Informations de base</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Nom complet *" name="nom" placeholder="Nom et prénom" :value="$e?->nom" required />
    <x-field :prefix="$p" label="Email" name="email" type="email" placeholder="nom@rental.cd" :value="$e?->email" />
    <x-field :prefix="$p" label="Téléphone" name="telephone" type="tel" placeholder="+243 ..." :value="$e?->telephone" />
    <x-field :prefix="$p" label="Poste *" name="poste" placeholder="Ex. Chauffeur" :value="$e?->poste" required />
    <x-field :prefix="$p" label="Rôle *" name="role" :options="\App\Models\Employe::ROLES" :value="$e?->role" />
    <x-field :prefix="$p" label="Salaire (USD) *" name="salaire" :value="$e ? $e->salaireFormate() : '0,00'" required />
    <x-field :prefix="$p" label="Date d'embauche" name="date_embauche" type="date" :value="$e?->date_embauche?->format('Y-m-d')" />
    <x-field :prefix="$p" label="Statut *" name="statut" :options="\App\Models\Employe::STATUTS" :value="$e?->statut" />
    <x-field :prefix="$p" label="Lieu de travail" name="lieu_travail" placeholder="Siège, Kinshasa" :value="$e?->lieu_travail" />
</div>
<p class="text-sm font-bold">Informations personnelles</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Genre" name="genre" :options="\App\Models\Employe::GENRES" placeholder-option="—" :value="$e?->genre" />
    <x-field :prefix="$p" label="Date de naissance" name="date_naissance" type="date" :value="$e?->date_naissance?->format('Y-m-d')" />
    <x-field :prefix="$p" label="Lieu de naissance" name="lieu_naissance" placeholder="Kinshasa" :value="$e?->lieu_naissance" />
    <x-field :prefix="$p" label="État civil" name="etat_civil" :options="\App\Models\Employe::ETATS_CIVILS" placeholder-option="—" :value="$e?->etat_civil" />
    <x-field :prefix="$p" label="Niveau d'études" name="niveau_etudes" :options="\App\Models\Employe::NIVEAUX_ETUDES" placeholder-option="—" :value="$e?->niveau_etudes" />
    <x-field :prefix="$p" label="Domaine d'étude" name="domaine" placeholder="Ex. Mécanique" :value="$e?->domaine" />
    <x-field :prefix="$p" label="Adresse physique" name="adresse" placeholder="Avenue, quartier, commune" :value="$e?->adresse" wide />
</div>
<p class="text-sm font-bold">Origine</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Province" name="province" placeholder="Ex. Kwilu" :value="$e?->province" />
    <x-field :prefix="$p" label="District" name="district" placeholder="Ex. Kwilu" :value="$e?->district" />
    <x-field :prefix="$p" label="Territoire" name="territoire" placeholder="Ex. Bulungu" :value="$e?->territoire" />
</div>
<p class="text-sm font-bold">Personne de référence</p>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-field :prefix="$p" label="Nom de la personne de référence" name="personne_reference" placeholder="Nom et lien (ex. frère)" :value="$e?->personne_reference" />
    <x-field :prefix="$p" label="Contact de la personne de référence" name="contact_reference" type="tel" placeholder="+243 ..." :value="$e?->contact_reference" />
</div>
<div>
    <label for="{{ $p }}-photo" class="mb-1.5 block text-sm font-medium text-gray-700">Photo</label>
    <input id="{{ $p }}-photo" name="photo" type="file" accept="image/*" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
    <p class="mt-1 text-xs text-gray-500">JPG ou PNG, 5 Mo maximum.@if ($e?->photo) Laisser vide pour garder la photo actuelle.@endif</p>
</div>

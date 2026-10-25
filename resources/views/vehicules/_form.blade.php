{{-- Formulaire véhicule (ajout et modification). $v : véhicule ou null ; $p : préfixe unique. --}}
@php $v = $v ?? null; @endphp
<input type="hidden" name="_form" value="{{ $p }}">
<div class="grid gap-4 sm:grid-cols-2">
    <x-field :prefix="$p" label="Immatriculation *" name="plaque" placeholder="CG 0000 AA" :value="$v?->plaque" required />
    <x-field :prefix="$p" label="Marque *" name="marque" placeholder="Toyota" :value="$v?->marque" required />
    <x-field :prefix="$p" label="Modèle *" name="modele" placeholder="Land Cruiser Prado" :value="$v?->modele" required />
    <x-field :prefix="$p" label="Année" name="annee" type="number" min="1980" :max="now()->year + 1" :value="$v?->annee ?? now()->year" />
    <x-field :prefix="$p" label="Catégorie *" name="categorie" :options="\App\Models\Vehicule::CATEGORIES" :value="$v?->categorie ?? 'SUV'" />
    <x-field :prefix="$p" label="Tarif journalier (USD) *" name="tarif_journalier" inputmode="decimal" :value="$v ? \App\Support\Format::usd($v->tarif_journalier) : ''" placeholder="0,00" required />
    <div class="sm:col-span-2">
        <label for="{{ $p }}-photo" class="mb-1.5 block text-sm font-medium text-gray-700">Photo du véhicule</label>
        <input id="{{ $p }}-photo" name="photo" type="file" accept="image/*" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
        <p class="mt-1 text-xs text-gray-500">JPG ou PNG, 5 Mo maximum.@if ($v?->photo) Laisser vide pour garder la photo actuelle.@endif</p>
    </div>
</div>
<p class="text-xs text-gray-500">Le statut « Loué » est mis à jour automatiquement par les locations ; la maintenance se gère avec son bouton.</p>

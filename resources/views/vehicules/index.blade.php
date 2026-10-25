@php
    $vehicules = [
        ['CG 1234 AB', 'Toyota Land Cruiser Prado', 'prado', 2022, 'SUV', '120', 'Disponible', 'green'],
        ['CG 5678 AC', 'Toyota Land Cruiser 70', 'land-cruiser-70', 2021, 'Pick-up', '100', 'Loué', 'orange'],
        ['CG 9012 AD', 'Toyota Land Cruiser V8', 'land-cruiser', 2020, 'SUV', '150', 'Disponible', 'green'],
        ['CG 3456 AE', 'Toyota Land Cruiser', 'land-cruiser-plage', 2019, 'SUV', '135', 'En maintenance', 'red'],
        ['CG 7788 AF', 'Toyota Corolla', 'corolla', 2023, 'Berline', '45', 'Disponible', 'green'],
        ['CG 2244 AG', 'Toyota Coaster', null, 2019, 'Minibus', '180', 'Loué', 'orange'],
    ];
@endphp
<x-layouts.app title="Flotte">
    <x-page-header title="Flotte de véhicules" subtitle="12 véhicules · le statut suit les contrats de location">
        <x-button icon="plus" data-modal-target="modal-vehicule" data-modal-toggle="modal-vehicule">Ajouter un véhicule</x-button>
    </x-page-header>

    <x-search-bar placeholder="Rechercher un véhicule..." :filters="[['Tous les statuts', 'Disponible', 'Loué', 'En maintenance'], ['Toutes catégories', 'SUV', 'Pick-up', 'Berline', 'Minibus']]" />

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Total véhicules" value="12" />
        <x-stat label="Disponibles" value="9" tone="ok" />
        <x-stat label="Loués" value="2" tone="accent" />
        <x-stat label="En maintenance" value="1" tone="warn" />
    </section>

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-label="Véhicules">
        @foreach ($vehicules as [$plaque, $modele, $photo, $annee, $categorie, $tarif, $statut, $couleur])
            <article class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs transition hover:shadow-md">
                <div class="relative h-44 overflow-hidden">
                    <x-photo :name="$photo" :alt="$modele" :width="800" class="h-full w-full transition duration-300 group-hover:scale-105" />
                    <x-badge :color="$couleur" class="absolute left-3 top-3 shadow-sm">{{ $statut }}</x-badge>
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-gray-900">{{ $modele }}</h2>
                            <p class="mt-0.5 text-sm text-gray-500">{{ $categorie }} · {{ $annee }}</p>
                        </div>
                        <p class="text-right"><span class="text-lg font-bold text-primary-700">{{ $tarif }} $</span><span class="block text-xs text-gray-500">par jour</span></p>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                        <span class="rounded-md border-2 border-gray-800 px-2 py-0.5 font-mono text-xs font-bold">{{ $plaque }}</span>
                        <x-row-actions modal="modal-vehicule" />
                    </div>
                </div>
            </article>
        @endforeach
    </section>
    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500"><x-pagination text="Affichage de 1 à 6 sur 12 véhicules" /></div>

    <x-slot:modals>
        <x-modal id="modal-vehicule" title="Ajouter un véhicule" size="max-w-xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Immatriculation" name="plaque" placeholder="CG 0000 AA" />
                <x-field label="Marque" name="marque" placeholder="Toyota" />
                <x-field label="Modèle" name="modele" placeholder="Land Cruiser Prado" />
                <x-field label="Année" name="annee" type="number" value="2024" />
                <x-field label="Catégorie" name="categorie" :options="['SUV', 'Pick-up', 'Berline', 'Minibus', 'Luxe']" />
                <x-field label="Tarif journalier (USD)" name="tarif" value="0,00" />
                <x-field label="Statut" name="statut" :options="['Disponible', 'En maintenance']" />
                <x-file-input name="photo" label="Photo du véhicule" />
            </div>
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

@php
    $rapports = [
        ['chart', 'Revenus & dépenses', 'Revenus, dépenses et solde du mois', 'dashboard'],
        ['calc', 'Locations', 'Liste des contrats et commissions', 'locations.index'],
        ['trend', 'Autres revenus', 'Sources et montants', 'revenus.index'],
        ['receipt', 'Dépenses', 'Détail par catégorie', 'depenses.index'],
        ['money', 'Paie', 'Bulletins, avances et crédits', 'paie.index'],
        ['car', 'Flotte', 'État et occupation des véhicules', 'vehicules.index'],
    ];
@endphp
<x-layouts.app title="Rapports">
    <x-page-header title="Rapports" subtitle="Consultez et exportez vos données">
        <label for="mois-rapport" class="sr-only">Mois</label>
        <select id="mois-rapport" class="rounded-lg border border-gray-300 bg-white py-2.5 text-sm focus:border-primary-600 focus:ring-primary-600"><option>Octobre 2026</option><option>Septembre 2026</option></select>
        <x-button icon="download">Exporter</x-button>
    </x-page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($rapports as [$icon, $titre, $texte, $route])
            <a href="{{ route($route) }}" class="group flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-xs transition hover:border-primary-200 hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 group-hover:bg-primary-600 group-hover:text-white"><x-icon :name="$icon" /></span>
                <span class="mt-4 font-bold text-gray-900">{{ $titre }}</span>
                <span class="mt-1 text-sm text-gray-500">{{ $texte }}</span>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700">Ouvrir le rapport<x-icon name="arrow" class="h-4 w-4" /></span>
            </a>
        @endforeach
    </section>
</x-layouts.app>

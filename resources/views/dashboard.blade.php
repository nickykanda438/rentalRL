@php
    // Données d'exemple : à remplacer par les requêtes sur les modèles
    $kpis = [
        ['label' => 'Revenus location (USD)', 'value' => '12 500,00', 'trend' => '12%', 'icon' => 'building', 'box' => 'bg-primary-50 border-primary-100', 'ic' => 'bg-primary-600 text-white', 'lbl' => 'text-primary-700'],
        ['label' => 'Autres revenus (USD)', 'value' => '3 200,00', 'trend' => '8%', 'icon' => 'trend', 'box' => 'bg-accent-50 border-accent-100', 'ic' => 'bg-accent-500 text-white', 'lbl' => 'text-accent-700'],
        ['label' => 'Dépenses (USD)', 'value' => '7 850,00', 'trend' => '5%', 'icon' => 'target', 'box' => 'bg-red-50 border-red-100', 'ic' => 'bg-red-500 text-white', 'lbl' => 'text-red-700'],
    ];
    $mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct'];
    $location = [6.8, 7.4, 8.1, 9.0, 8.6, 9.8, 10.4, 11.2, 11.9, 12.5];
    $autres = [2.1, 2.6, 2.4, 3.0, 2.8, 3.4, 2.9, 3.6, 3.1, 3.2];
    $depenses = [5.2, 5.9, 6.3, 6.8, 6.1, 7.2, 6.9, 7.6, 7.4, 7.85];
    $enCours = [
        ['C-2026-012', 'Société Kin Logistique', 'Land Cruiser V8', 'land-cruiser', '21/10/2026', '1 080,00', 'Actif', 'green'],
        ['C-2026-011', 'ONG Mwinda', 'Land Cruiser 70', 'land-cruiser-70', '20/10/2026', '650,00', 'Retour ce jour', 'orange'],
        ['C-2026-010', 'P. Mbuyi', 'Toyota Coaster', null, '16/10/2026', '1 200,00', 'En retard', 'red'],
    ];
@endphp
<x-layouts.app title="Tableau de bord">
    <x-page-header title="Tableau de bord" subtitle="Vue d'ensemble de votre activité">
        <label for="periode" class="sr-only">Période</label>
        <select id="periode" class="rounded-lg border border-gray-300 bg-white py-2.5 text-sm focus:border-primary-600 focus:ring-primary-600"><option>Octobre 2026</option><option>Septembre 2026</option></select>
        <x-button icon="plus" :href="route('locations.index')">Nouvelle location</x-button>
    </x-page-header>

    {{-- Indicateurs --}}
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicateurs du mois">
        @foreach ($kpis as $k)
            <div class="flex gap-4 rounded-xl border p-5 {{ $k['box'] }}">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $k['ic'] }}"><x-icon :name="$k['icon']" class="h-6 w-6" /></span>
                <div>
                    <p class="text-xs font-semibold {{ $k['lbl'] }}">{{ $k['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ $k['value'] }}</p>
                    <p class="mt-1.5 flex items-center gap-1 text-xs text-gray-500"><x-icon name="up" class="h-3.5 w-3.5 text-primary-600" /><b class="text-primary-600">{{ $k['trend'] }}</b> vs mois précédent</p>
                </div>
            </div>
        @endforeach
        <div class="flex gap-4 rounded-xl border border-primary-800 bg-primary-800 p-5 text-white">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-lime-brand text-primary-900"><x-icon name="wallet" class="h-6 w-6" /></span>
            <div>
                <p class="text-xs font-semibold text-primary-100">Solde (USD)</p>
                <p class="mt-1 text-2xl font-bold tabular-nums">7 850,00</p>
                <p class="mt-1.5 flex items-center gap-1 text-xs text-primary-100"><x-icon name="up" class="h-3.5 w-3.5 text-lime-brand" /><b class="text-lime-brand">15%</b> vs mois précédent</p>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,2.3fr)_minmax(300px,1fr)]">
        {{-- Graphique --}}
        <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-bold">Évolution des revenus et dépenses</h2>
                <div class="flex flex-wrap gap-4 text-xs font-medium text-gray-500">
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-primary-600"></i>Location</span>
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-accent-500"></i>Autres revenus</span>
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-red-500"></i>Dépenses</span>
                </div>
            </div>
            <div class="mt-6 flex h-64 gap-3" role="img" aria-label="Les revenus de location progressent de janvier à octobre 2026 et restent au-dessus des dépenses">
                <div class="flex flex-col justify-between pb-6 text-right text-[11px] text-gray-400"><span>15k</span><span>10k</span><span>5k</span><span>0</span></div>
                <div class="relative flex-1">
                    <div class="absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between">
                        <div class="border-t border-dashed border-gray-200"></div><div class="border-t border-dashed border-gray-200"></div><div class="border-t border-dashed border-gray-200"></div><div class="border-t border-gray-300"></div>
                    </div>
                    <div class="absolute inset-0 grid grid-cols-10">
                        @foreach ($mois as $i => $m)
                            <div class="flex flex-col items-center justify-end">
                                <div class="flex h-[calc(100%-1.5rem)] items-end gap-[3px]">
                                    <span class="w-1.5 rounded-t-sm bg-primary-600 sm:w-2.5" style="height: {{ round($location[$i] / 15 * 100, 1) }}%" title="Location : {{ $location[$i] }} k$"></span>
                                    <span class="w-1.5 rounded-t-sm bg-accent-500 sm:w-2.5" style="height: {{ round($autres[$i] / 15 * 100, 1) }}%" title="Autres : {{ $autres[$i] }} k$"></span>
                                    <span class="w-1.5 rounded-t-sm bg-red-500 sm:w-2.5" style="height: {{ round($depenses[$i] / 15 * 100, 1) }}%" title="Dépenses : {{ $depenses[$i] }} k$"></span>
                                </div>
                                <span class="flex h-6 items-end text-[11px] text-gray-500">{{ $m }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Flotte avec photo --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
            <div class="relative h-36">
                <x-photo name="land-cruiser" alt="Toyota Land Cruiser blanc" :width="800" class="h-full w-full" />
                <div class="absolute inset-0 bg-gradient-to-t from-primary-950/80 to-transparent"></div>
                <div class="absolute bottom-3 left-4 text-white">
                    <p class="text-xs font-medium text-primary-100">Véhicules en flotte</p>
                    <p class="text-3xl font-bold leading-none">12</p>
                </div>
                <a href="{{ route('vehicules.index') }}" class="absolute right-3 top-3 rounded-lg bg-white/90 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-white">Voir la flotte</a>
            </div>
            <div class="p-5">
                <div class="flex h-2 overflow-hidden rounded-full bg-gray-100" aria-hidden="true">
                    <span class="bg-primary-600" style="width: 75%"></span><span class="bg-accent-500" style="width: 16.7%"></span><span class="bg-red-500" style="width: 8.3%"></span>
                </div>
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    <li class="flex items-center gap-2.5 py-2.5"><i class="h-2.5 w-2.5 rounded-full bg-primary-600"></i>Disponibles<b class="ml-auto">9</b></li>
                    <li class="flex items-center gap-2.5 py-2.5"><i class="h-2.5 w-2.5 rounded-full bg-accent-500"></i>Loués<b class="ml-auto">2</b></li>
                    <li class="flex items-center gap-2.5 py-2.5"><i class="h-2.5 w-2.5 rounded-full bg-red-500"></i>En maintenance<b class="ml-auto">1</b></li>
                </ul>
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,2.3fr)_minmax(300px,1fr)]">
        <x-table title="Locations en cours" class="min-w-0">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                <th class="px-4 py-3">N° contrat</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Véhicule</th><th class="px-4 py-3">Retour prévu</th><th class="px-4 py-3 text-right">Total (USD)</th><th class="px-4 py-3">Statut</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($enCours as [$num, $client, $veh, $photo, $retour, $total, $statut, $couleur])
                    <tr class="hover:bg-cream">
                        <td class="px-4 py-3 font-medium">{{ $num }}</td>
                        <td class="px-4 py-3">{{ $client }}</td>
                        <td class="px-4 py-3"><div class="flex items-center gap-3"><x-photo :name="$photo" :alt="$veh" :width="160" class="h-9 w-14 rounded-md" />{{ $veh }}</div></td>
                        <td class="px-4 py-3">{{ $retour }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $total }}</td>
                        <td class="px-4 py-3"><x-badge :color="$couleur">{{ $statut }}</x-badge></td>
                    </tr>
                @endforeach
            </tbody>
            <x-slot:footer><span>3 locations en cours</span><a href="{{ route('locations.index') }}" class="font-semibold text-primary-700 hover:underline">Voir toutes les locations</a></x-slot:footer>
        </x-table>

        <section class="flex flex-col gap-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <h2 class="text-base font-bold">À traiter</h2>
                <ul class="mt-2 divide-y divide-gray-100 text-sm">
                    <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-red-500"></i>Coaster en retard de 4 jours<x-badge color="red" class="ml-auto">Urgent</x-badge></li>
                    <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-accent-500"></i>2 avances à approuver<a href="{{ route('paie.index') }}" class="ml-auto font-semibold text-primary-700">Voir</a></li>
                    <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-primary-600"></i>Besoins en attente : 650,00 $<a href="{{ route('besoins.index') }}" class="ml-auto font-semibold text-primary-700">Voir</a></li>
                </ul>
            </div>
            <div class="flex overflow-hidden rounded-xl bg-accent-500 text-white">
                <div class="flex-1 p-5">
                    <p class="text-lg font-bold leading-snug">9 véhicules prêts à partir aujourd'hui</p>
                    <p class="mt-1 text-sm text-accent-50">Créez un contrat en moins d'une minute.</p>
                    <a href="{{ route('locations.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-sm font-semibold text-accent-700 hover:bg-accent-50">Nouvelle location<x-icon name="arrow" class="h-4 w-4" /></a>
                </div>
                <x-photo name="corolla" alt="" :width="500" class="w-32 shrink-0 sm:w-40" />
            </div>
        </section>
    </div>
</x-layouts.app>

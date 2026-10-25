@use('App\Support\Format')
@use('App\Models\Location')
@php
    $th = 'px-5 py-3';
    $titreMois = $mois ? Format::mois($mois) : 'Tous les mois';
@endphp
<x-layouts.app title="Budget location">
    <x-page-header title="Budget location" small="(Recettes de location)" :subtitle="$titreMois">
        <x-button variant="light" icon="download" :href="route('locations.exporter', request()->query() + ['mois' => $mois ?? ''])">Exporter (Excel)</x-button>
        <x-button icon="plus" data-modal-target="modal-location" data-modal-toggle="modal-location">Nouvelle location</x-button>
    </x-page-header>

    <x-flash />

    @if ($stats['retards'] > 0)
        <a href="{{ route('locations.index', ['mois' => '', 'statut' => 'En retard']) }}" class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 hover:bg-red-100">
            <x-icon name="bell" class="h-4 w-4" />
            {{ $stats['retards'] }} location(s) en retard : la date de retour prévue est dépassée. Voir la liste →
        </a>
    @endif

    {{-- Recherche et filtres --}}
    <form method="GET" action="{{ route('locations.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-60 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
            <label for="recherche" class="sr-only">Rechercher</label>
            <input id="recherche" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher un client, contrat, destination, plaque..." class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
        </div>
        <label for="filtre-mois" class="sr-only">Mois</label>
        <input id="filtre-mois" name="mois" type="month" value="{{ $mois }}" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-44">
        <label for="filtre-statut" class="sr-only">Statut</label>
        <select id="filtre-statut" name="statut" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-44">
            <option value="">Tous les statuts</option>
            @foreach ([...Location::STATUTS, 'En retard'] as $s)<option @selected($statut === $s)>{{ $s }}</option>@endforeach
        </select>
        @if ($mois)
            <a href="{{ route('locations.index', ['mois' => '', 'statut' => $statut, 'q' => request('q')]) }}" class="text-sm font-semibold text-primary-700 hover:underline">Tous les mois</a>
        @endif
    </form>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Revenus USD" :value="Format::usd($stats['revenus'])" tone="ok" />
        <x-stat label="Commissions USD" :value="Format::usd($stats['commissions'])" />
        <x-stat label="Total CDF" :value="Format::cdf($stats['cdf'])" />
        <x-stat label="Nombre d'entrées" :value="$stats['entrees']" />
    </section>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="{{ $th }}">N° contrat</th><th class="{{ $th }}">Période</th><th class="{{ $th }}">Client</th><th class="{{ $th }}">Destination</th><th class="{{ $th }}">Véhicule / chauffeur</th><th class="{{ $th }} text-right">Total (USD)</th><th class="{{ $th }}">Statut</th><th class="{{ $th }} text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($locations as $location)
                <tr class="hover:bg-cream">
                    <td class="{{ $th }}"><a href="{{ route('locations.show', $location) }}" class="font-mono text-xs font-semibold text-primary-700 hover:underline">{{ $location->numero_contrat }}</a></td>
                    <td class="{{ $th }}">
                        {{ $location->date_depart->format('d/m/Y') }} → {{ $location->date_fin->format('d/m/Y') }}
                        <span class="block text-xs text-gray-500">{{ $location->nombre_jours }} jour(s)</span>
                    </td>
                    <td class="{{ $th }}">
                        <span class="font-medium text-gray-900">{{ $location->client->nom }}</span>
                        @if ($location->client->telephone)<span class="block text-xs text-gray-500">{{ $location->client->telephone }}</span>@endif
                    </td>
                    <td class="{{ $th }}">{{ $location->destination ?? '—' }}</td>
                    <td class="{{ $th }}">
                        {{ $location->vehicule?->libelle() ?? '—' }}
                        <span class="block text-xs text-gray-500">{{ $location->chauffeur?->nom ?? 'Sans chauffeur' }}</span>
                    </td>
                    <td class="{{ $th }} text-right tabular-nums">
                        <span class="font-semibold">{{ Format::usd($location->total_usd) }}</span>
                        <span class="block text-xs text-gray-500">{{ Format::cdf($location->total_cdf) }} FC</span>
                    </td>
                    <td class="{{ $th }}"><x-badge :color="$location->couleurStatut()">{{ $location->statutAffiche() }}</x-badge></td>
                    <td class="{{ $th }}">
                        <x-row-actions :show="route('locations.show', $location)" :modal="'modal-location-'.$location->id" :delete="route('locations.destroy', $location)" :confirm="'Supprimer définitivement la location '.$location->numero_contrat.' ?'" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">
                    Aucune location pour ces critères.
                    @if ($mois)<a href="{{ route('locations.index', ['mois' => '']) }}" class="font-semibold text-primary-700 hover:underline">Voir tous les mois</a>@endif
                </td></tr>
            @endforelse
        </tbody>
        @if ($locations->total() > 0)
            <x-slot:footer>
                <span>Affichage de {{ $locations->firstItem() }} à {{ $locations->lastItem() }} sur {{ $locations->total() }} entrée(s)</span>
                @if ($locations->hasPages())
                    <nav aria-label="Pagination">
                        <ul class="inline-flex -space-x-px text-sm">
                            <li><a href="{{ $locations->previousPageUrl() ?? '#' }}" class="flex h-8 items-center rounded-s-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50 {{ $locations->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}">Précédent</a></li>
                            @foreach ($locations->getUrlRange(max(1, $locations->currentPage() - 2), min($locations->lastPage(), $locations->currentPage() + 2)) as $page => $url)
                                <li><a href="{{ $url }}" @if ($page === $locations->currentPage()) aria-current="page" @endif class="flex h-8 items-center border px-3 {{ $page === $locations->currentPage() ? 'border-primary-600 bg-primary-600 font-semibold text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50' }}">{{ $page }}</a></li>
                            @endforeach
                            <li><a href="{{ $locations->nextPageUrl() ?? '#' }}" class="flex h-8 items-center rounded-e-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50 {{ $locations->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}">Suivant</a></li>
                        </ul>
                    </nav>
                @endif
            </x-slot:footer>
        @endif
    </x-table>

    <x-slot:modals>
        @include('locations._clients')

        <x-modal id="modal-location" title="Nouvelle location" size="max-w-3xl" form-id="form-location" :action="route('locations.store')">
            @include('locations._form', ['l' => null, 'p' => 'nouvelle', 'vehiculeParDefaut' => request('nouvelle')])
        </x-modal>

        @foreach ($locations as $location)
            <x-modal :id="'modal-location-'.$location->id" :title="'Modifier '.$location->numero_contrat" size="max-w-3xl" :form-id="'form-location-'.$location->id" :action="route('locations.update', $location)" method="PUT" submit="Mettre à jour">
                @include('locations._form', ['l' => $location, 'p' => 'loc'.$location->id])
            </x-modal>
        @endforeach

        {{-- Rouvre la bonne fenêtre si la validation a échoué --}}
        @if (request('nouvelle') && ! $errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', () => document.querySelector('[data-modal-toggle="modal-location"]')?.click());
            </script>
        @endif

        @if ($errors->any() && old('_form'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = @js(old('_form'));
                    const id = form === 'nouvelle' ? 'modal-location' : 'modal-location-' + form.replace('loc', '');
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

@use('App\Support\Format')
@use('App\Models\Vehicule')
<x-layouts.app title="Flotte">
    <x-page-header title="Flotte de véhicules" :subtitle="$stats['total'].' véhicule(s) · le statut suit les contrats de location'">
        <x-button variant="light" icon="calc" :href="route('locations.index')">Budget location</x-button>
        <x-button icon="plus" data-modal-target="modal-vehicule" data-modal-toggle="modal-vehicule">Ajouter un véhicule</x-button>
    </x-page-header>

    <x-flash />

    {{-- Alertes (VE-04, RG-06) --}}
    @if ($alertes['sous_seuil'])
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            Disponibilité critique : seulement {{ $stats['taux'] }} % des véhicules sont disponibles (seuil : {{ Format::cdf($alertes['seuil']) }} %).
        </div>
    @endif
    @if ($alertes['retards']->isNotEmpty() || $alertes['retours_du_jour']->isNotEmpty())
        <section class="grid gap-3 lg:grid-cols-2">
            @if ($alertes['retards']->isNotEmpty())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm">
                    <p class="mb-1.5 font-semibold text-red-800">En retard ({{ $alertes['retards']->count() }})</p>
                    @foreach ($alertes['retards'] as $l)
                        <a href="{{ route('locations.show', $l) }}" class="block text-red-700 hover:underline">
                            {{ $l->vehicule?->plaque ?? 'Sans véhicule' }} · {{ $l->client->nom }} · retour prévu le {{ $l->date_fin->format('d/m') }}
                        </a>
                    @endforeach
                </div>
            @endif
            @if ($alertes['retours_du_jour']->isNotEmpty())
                <div class="rounded-xl border border-accent-200 bg-accent-50 px-4 py-3 text-sm">
                    <p class="mb-1.5 font-semibold text-accent-800">Retours attendus aujourd'hui ({{ $alertes['retours_du_jour']->count() }})</p>
                    @foreach ($alertes['retours_du_jour'] as $l)
                        <a href="{{ route('locations.show', $l) }}" class="block text-accent-700 hover:underline">
                            {{ $l->vehicule?->plaque ?? 'Sans véhicule' }} · {{ $l->client->nom }} · {{ $l->numero_contrat }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Recherche et filtres --}}
    <form method="GET" action="{{ route('vehicules.index') }}" class="flex flex-wrap gap-3">
        <div class="relative min-w-60 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
            <label for="recherche" class="sr-only">Rechercher</label>
            <input id="recherche" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher une plaque, une marque, un modèle..." class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
        </div>
        <label for="filtre-statut" class="sr-only">Statut</label>
        <select id="filtre-statut" name="statut" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-48">
            <option value="">Tous les statuts</option>
            @foreach (Vehicule::STATUTS as $s)<option @selected($statut === $s)>{{ $s }}</option>@endforeach
        </select>
        <label for="filtre-categorie" class="sr-only">Catégorie</label>
        <select id="filtre-categorie" name="categorie" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-48">
            <option value="">Toutes catégories</option>
            @foreach (Vehicule::CATEGORIES as $c)<option @selected($categorie === $c)>{{ $c }}</option>@endforeach
        </select>
    </form>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Total véhicules" :value="$stats['total']" />
        <x-stat :label="'Disponibles · '.$stats['taux'].' %'" :value="$stats['disponibles']" :tone="$alertes['sous_seuil'] ? 'warn' : 'ok'" />
        <x-stat label="Loués" :value="$stats['loues']" tone="accent" />
        <x-stat label="En maintenance" :value="$stats['maintenance']" :tone="$stats['maintenance'] ? 'warn' : 'default'" />
    </section>

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-label="Véhicules">
        @forelse ($vehicules as $vehicule)
            @php $enCours = $vehicule->locationEnCours; @endphp
            <article class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs transition hover:shadow-md">
                <a href="{{ route('vehicules.show', $vehicule) }}" class="relative block h-44 overflow-hidden">
                    <x-vehicule-photo :vehicule="$vehicule" class="h-full w-full transition duration-300 group-hover:scale-105" />
                    <x-badge :color="$vehicule->couleurStatut()" class="absolute left-3 top-3 shadow-sm">{{ $vehicule->statut }}</x-badge>
                </a>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-gray-900"><a href="{{ route('vehicules.show', $vehicule) }}" class="hover:underline">{{ $vehicule->marque }} {{ $vehicule->modele }}</a></h2>
                            <p class="mt-0.5 text-sm text-gray-500">{{ $vehicule->categorie }}@if ($vehicule->annee) · {{ $vehicule->annee }}@endif</p>
                        </div>
                        <p class="text-right"><span class="text-lg font-bold text-primary-700">{{ Format::usd($vehicule->tarif_journalier) }} $</span><span class="block text-xs text-gray-500">par jour</span></p>
                    </div>

                    @if ($enCours)
                        <a href="{{ route('locations.show', $enCours) }}" class="mt-3 block rounded-lg px-3 py-2 text-xs {{ $enCours->estEnRetard() ? 'bg-red-50 text-red-800' : 'bg-accent-50 text-accent-800' }} hover:underline">
                            {{ $enCours->estEnRetard() ? 'En retard' : 'Loué' }} · {{ $enCours->client->nom }} · retour {{ $enCours->date_fin->format('d/m/Y') }}
                        </a>
                    @endif

                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                        <span class="rounded-md border-2 border-gray-800 px-2 py-0.5 font-mono text-xs font-bold">{{ $vehicule->plaque }}</span>
                        <x-row-actions :show="route('vehicules.show', $vehicule)" :modal="'modal-vehicule-'.$vehicule->id" :delete="route('vehicules.destroy', $vehicule)" :confirm="'Retirer '.$vehicule->plaque.' de la flotte ?'" />
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-sm text-gray-500 sm:col-span-2 xl:col-span-3">
                @if (request()->hasAny(['q', 'statut', 'categorie']))
                    Aucun véhicule ne correspond à la recherche.
                @else
                    Aucun véhicule dans la flotte. Cliquez sur « Ajouter un véhicule » pour commencer.
                @endif
            </div>
        @endforelse
    </section>

    @if ($vehicules->hasPages())
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
            <span>Affichage de {{ $vehicules->firstItem() }} à {{ $vehicules->lastItem() }} sur {{ $vehicules->total() }} véhicules</span>
            <nav aria-label="Pagination">
                <ul class="inline-flex -space-x-px text-sm">
                    <li><a href="{{ $vehicules->previousPageUrl() ?? '#' }}" class="flex h-8 items-center rounded-s-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50 {{ $vehicules->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}">Précédent</a></li>
                    @foreach ($vehicules->getUrlRange(1, $vehicules->lastPage()) as $page => $url)
                        <li><a href="{{ $url }}" class="flex h-8 items-center border px-3 {{ $page === $vehicules->currentPage() ? 'border-primary-600 bg-primary-600 font-semibold text-white' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50' }}">{{ $page }}</a></li>
                    @endforeach
                    <li><a href="{{ $vehicules->nextPageUrl() ?? '#' }}" class="flex h-8 items-center rounded-e-lg border border-gray-300 bg-white px-3 text-gray-500 hover:bg-gray-50 {{ $vehicules->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}">Suivant</a></li>
                </ul>
            </nav>
        </div>
    @endif

    <x-slot:modals>
        <x-modal id="modal-vehicule" title="Ajouter un véhicule" size="max-w-xl" :action="route('vehicules.store')">
            @include('vehicules._form', ['v' => null, 'p' => 'nouveau'])
        </x-modal>

        @foreach ($vehicules as $vehicule)
            <x-modal :id="'modal-vehicule-'.$vehicule->id" :title="'Modifier '.$vehicule->plaque" size="max-w-xl" :action="route('vehicules.update', $vehicule)" method="PUT" submit="Mettre à jour">
                @include('vehicules._form', ['v' => $vehicule, 'p' => 'veh'.$vehicule->id])
            </x-modal>
        @endforeach

        @if ($errors->any() && old('_form'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = @js(old('_form'));
                    const id = form === 'nouveau' ? 'modal-vehicule' : 'modal-vehicule-' + form.replace('veh', '');
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

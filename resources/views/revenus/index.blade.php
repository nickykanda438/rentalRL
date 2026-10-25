@use('App\Support\Format')
@php
    $th = 'px-5 py-3';
    $titreMois = $mois ? Format::mois($mois) : 'Tous les mois';
@endphp
<x-layouts.app title="Autres revenus">
    <x-page-header title="Autres revenus" :subtitle="'Entrées d\'argent hors location · '.$titreMois">
        <x-button variant="light" icon="download" :href="route('revenus.exporter', request()->query() + ['mois' => $mois ?? ''])">Exporter (Excel)</x-button>
        <x-button icon="plus" data-modal-target="modal-revenu" data-modal-toggle="modal-revenu">Nouveau revenu</x-button>
    </x-page-header>

    <x-flash />

    <form method="GET" action="{{ route('revenus.index') }}" class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-60 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
            <label for="recherche" class="sr-only">Rechercher</label>
            <input id="recherche" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher une source, une description, un n°..." class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
        </div>
        <input name="mois" type="month" value="{{ $mois }}" onchange="this.form.submit()" aria-label="Mois" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-44">
        @if ($mois)<a href="{{ route('revenus.index', ['mois' => '', 'q' => request('q')]) }}" class="text-sm font-semibold text-primary-700 hover:underline">Tous les mois</a>@endif
    </form>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Reçu (USD)" :value="Format::usd($stats['usd'])" tone="ok" />
        <x-stat label="Utilisé par des dépenses (USD)" :value="Format::usd($stats['utilise'])" tone="accent" />
        <x-stat label="Reste (USD)" :value="Format::usd($stats['usd'] - $stats['utilise'])" />
        <x-stat :label="'Total CDF · '.$stats['sources'].' source(s)'" :value="Format::cdf($stats['cdf'])" />
    </section>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="{{ $th }}">N° / date</th><th class="{{ $th }}">Source / provenance</th><th class="{{ $th }} text-right">Reçu (USD)</th><th class="{{ $th }} text-right">Utilisé</th><th class="{{ $th }} text-right">Reste</th><th class="{{ $th }}">Statut</th><th class="{{ $th }} text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($revenus as $r)
                <tr class="align-top hover:bg-cream">
                    <td class="{{ $th }}"><a href="{{ route('revenus.show', $r) }}" class="font-mono text-xs font-semibold text-primary-700 hover:underline">{{ $r->numero }}</a><span class="block text-xs text-gray-500">{{ $r->date_revenu->format('d/m/Y') }}</span></td>
                    <td class="{{ $th }} whitespace-normal">
                        <span class="font-medium text-gray-900">{{ $r->source }}</span>
                        @if ($r->description)<span class="block text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($r->description, 80) }}</span>@endif
                        @if ($r->piece_jointe)<a href="{{ asset('storage/'.$r->piece_jointe) }}" target="_blank" class="text-xs font-semibold text-primary-700 hover:underline">Pièce jointe</a>@endif
                    </td>
                    <td class="{{ $th }} text-right tabular-nums">
                        <span class="font-semibold">{{ Format::usd($r->montant_usd) }}</span>
                        <span class="block text-xs text-gray-500">{{ Format::cdf($r->montant_cdf) }} FC · {{ $r->mode_reception }}</span>
                    </td>
                    <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($r->montantUtilise()) }}</td>
                    <td class="{{ $th }} text-right tabular-nums">
                        <span class="font-semibold">{{ Format::usd($r->reste()) }}</span>
                        <span class="mt-1 block h-1.5 w-20 overflow-hidden rounded-full bg-gray-100 ml-auto"><span class="block h-full rounded-full bg-accent-500" style="width: {{ $r->pourcentageUtilise() }}%"></span></span>
                    </td>
                    <td class="{{ $th }}"><x-badge :color="$r->couleurStatut()">{{ $r->statut() }}</x-badge></td>
                    <td class="{{ $th }}"><x-row-actions :show="route('revenus.show', $r)" :modal="'modal-revenu-'.$r->id" :delete="$r->montantUtilise() > 0 ? null : route('revenus.destroy', $r)" :confirm="'Supprimer le revenu '.$r->numero.' ?'" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">
                    Aucun revenu pour ces critères.
                    @if ($mois)<a href="{{ route('revenus.index', ['mois' => '']) }}" class="font-semibold text-primary-700 hover:underline">Voir tous les mois</a>@endif
                </td></tr>
            @endforelse
        </tbody>
        @if ($revenus->total() > 0)
            <x-slot:footer>
                <span>Affichage de {{ $revenus->firstItem() }} à {{ $revenus->lastItem() }} sur {{ $revenus->total() }} entrée(s)</span>
                <span class="flex gap-3">
                    @if ($revenus->previousPageUrl())<a href="{{ $revenus->previousPageUrl() }}" class="font-semibold text-primary-700 hover:underline">← Précédent</a>@endif
                    @if ($revenus->nextPageUrl())<a href="{{ $revenus->nextPageUrl() }}" class="font-semibold text-primary-700 hover:underline">Suivant →</a>@endif
                </span>
            </x-slot:footer>
        @endif
    </x-table>

    <x-slot:modals>
        <datalist id="liste-sources">
            @foreach ($sources as $s)<option value="{{ $s }}"></option>@endforeach
        </datalist>

        <x-modal id="modal-revenu" title="Nouveau revenu" size="max-w-2xl" form-id="form-revenu" :action="route('revenus.store')">
            @include('revenus._form', ['r' => null, 'p' => 'nouveau'])
        </x-modal>

        @foreach ($revenus as $r)
            <x-modal :id="'modal-revenu-'.$r->id" :title="'Modifier '.$r->numero" size="max-w-2xl" :form-id="'form-revenu-'.$r->id" :action="route('revenus.update', $r)" method="PUT" submit="Mettre à jour">
                @include('revenus._form', ['r' => $r, 'p' => 'rev'.$r->id])
            </x-modal>
        @endforeach

        @if ($errors->any() && old('_form'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = @js(old('_form'));
                    const id = form === 'nouveau' ? 'modal-revenu' : 'modal-revenu-' + form.replace('rev', '');
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

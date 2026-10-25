@use('App\Support\Format')
@php
    $v = $vehicule;
    $enCours = $v->locationEnCours;
    $th = 'px-5 py-3';
@endphp
<x-layouts.app :title="$v->marque.' '.$v->modele">
    <x-page-header :title="$v->marque.' '.$v->modele" :subtitle="$v->plaque.' · '.$v->categorie.($v->annee ? ' · '.$v->annee : '')">
        <x-button variant="light" icon="arrow" :href="route('vehicules.index')">Retour à la flotte</x-button>
        <x-button variant="light" icon="edit" data-modal-target="modal-vehicule-{{ $v->id }}" data-modal-toggle="modal-vehicule-{{ $v->id }}">Modifier</x-button>
        @unless ($enCours)
            <form method="POST" action="{{ route('vehicules.maintenance', $v) }}">
                @csrf @method('PATCH')
                <x-button type="submit" variant="light" icon="settings">{{ $v->statut === 'En maintenance' ? 'Remettre en service' : 'Mettre en maintenance' }}</x-button>
            </form>
        @endunless
        @if ($v->statut === 'Disponible')
            <x-button icon="plus" :href="route('locations.index', ['mois' => '', 'nouvelle' => $v->id])">Louer ce véhicule</x-button>
        @endif
    </x-page-header>

    <x-flash />

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
            <div class="relative h-52">
                <x-vehicule-photo :vehicule="$v" class="h-full w-full" />
                <x-badge :color="$v->couleurStatut()" class="absolute left-3 top-3 shadow-sm">{{ $v->statut }}</x-badge>
            </div>
            <dl class="space-y-3 px-6 py-5 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Immatriculation</dt><dd><span class="rounded-md border-2 border-gray-800 px-2 py-0.5 font-mono text-xs font-bold">{{ $v->plaque }}</span></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Tarif journalier</dt><dd class="font-semibold text-primary-700">{{ Format::usd($v->tarif_journalier) }} $</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Locations</dt><dd class="font-medium">{{ $bilan['locations'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Jours loués</dt><dd class="font-medium">{{ $bilan['jours'] }}</dd></div>
                <div class="flex justify-between border-t border-gray-100 pt-3"><dt class="text-gray-500">Revenus générés (net)</dt><dd class="font-bold">{{ Format::usd($bilan['revenus']) }} $</dd></div>
            </dl>
        </section>

        <div class="space-y-6 lg:col-span-2">
            {{-- Location en cours (VE-02) --}}
            <section class="rounded-xl border border-gray-200 bg-white shadow-xs">
                <h2 class="border-b border-gray-100 px-6 py-4 text-base font-bold text-gray-900">Location en cours</h2>
                @if ($enCours)
                    <div class="grid gap-x-6 gap-y-3 px-6 py-5 text-sm sm:grid-cols-2">
                        <p><span class="text-gray-500">Contrat :</span> <a href="{{ route('locations.show', $enCours) }}" class="font-mono font-semibold text-primary-700 hover:underline">{{ $enCours->numero_contrat }}</a></p>
                        <p><span class="text-gray-500">Client :</span> <strong>{{ $enCours->client->nom }}</strong> {{ $enCours->client->telephone }}</p>
                        <p><span class="text-gray-500">Chauffeur :</span> {{ $enCours->chauffeur?->nom ?? '—' }}</p>
                        <p><span class="text-gray-500">Destination :</span> {{ $enCours->destination ?? '—' }}</p>
                        <p><span class="text-gray-500">Période :</span> {{ $enCours->date_depart->format('d/m/Y') }} → {{ $enCours->date_fin->format('d/m/Y') }}</p>
                        <p><span class="text-gray-500">Montant :</span> {{ Format::usd($enCours->total_usd) }} $</p>
                        @if ($enCours->estEnRetard())
                            <p class="font-semibold text-red-700 sm:col-span-2">En retard depuis le {{ $enCours->date_fin->format('d/m/Y') }}.</p>
                        @endif
                        <div class="sm:col-span-2">
                            <x-button variant="light" icon="check" :href="route('locations.show', $enCours)">Ouvrir le contrat pour clôturer le retour</x-button>
                        </div>
                    </div>
                @else
                    <p class="px-6 py-5 text-sm text-gray-500">{{ $v->statut === 'En maintenance' ? 'Véhicule en maintenance.' : 'Aucune location en cours : le véhicule est disponible.' }}</p>
                @endif
            </section>

            {{-- Historique --}}
            <x-table title="Historique des locations">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                    <th class="{{ $th }}">N° contrat</th><th class="{{ $th }}">Période</th><th class="{{ $th }}">Client</th><th class="{{ $th }}">Chauffeur</th><th class="{{ $th }} text-right">Total (USD)</th><th class="{{ $th }}">Statut</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($historique as $l)
                        <tr class="hover:bg-cream">
                            <td class="{{ $th }}"><a href="{{ route('locations.show', $l) }}" class="font-mono text-xs font-semibold text-primary-700 hover:underline">{{ $l->numero_contrat }}</a></td>
                            <td class="{{ $th }}">{{ $l->date_depart->format('d/m/Y') }} → {{ ($l->date_retour ?? $l->date_fin)->format('d/m/Y') }}</td>
                            <td class="{{ $th }}">{{ $l->client->nom }}</td>
                            <td class="{{ $th }}">{{ $l->chauffeur?->nom ?? '—' }}</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($l->total_usd) }}</td>
                            <td class="{{ $th }}"><x-badge :color="$l->couleurStatut()">{{ $l->statutAffiche() }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">Ce véhicule n'a encore jamais été loué.</td></tr>
                    @endforelse
                </tbody>
                @if ($historique->hasPages())
                    <x-slot:footer>
                        <span>Page {{ $historique->currentPage() }} sur {{ $historique->lastPage() }}</span>
                        <span class="flex gap-3">
                            @if ($historique->previousPageUrl())<a href="{{ $historique->previousPageUrl() }}" class="font-semibold text-primary-700 hover:underline">← Précédent</a>@endif
                            @if ($historique->nextPageUrl())<a href="{{ $historique->nextPageUrl() }}" class="font-semibold text-primary-700 hover:underline">Suivant →</a>@endif
                        </span>
                    </x-slot:footer>
                @endif
            </x-table>

            <div class="flex justify-end">
                <form method="POST" action="{{ route('vehicules.destroy', $v) }}" onsubmit="return confirm(@js('Retirer '.$v->plaque.' de la flotte ?'))">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><x-icon name="trash" class="h-4 w-4" /> Retirer de la flotte</button>
                </form>
            </div>
        </div>
    </div>

    <x-slot:modals>
        <x-modal :id="'modal-vehicule-'.$v->id" :title="'Modifier '.$v->plaque" size="max-w-xl" :action="route('vehicules.update', $v)" method="PUT" submit="Mettre à jour">
            @include('vehicules._form', ['v' => $v, 'p' => 'veh'.$v->id])
        </x-modal>
        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', () => document.querySelector('[data-modal-toggle="modal-vehicule-{{ $v->id }}"]')?.click());
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

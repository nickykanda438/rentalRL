@use('App\Support\Format')
@php
    $l = $location;
    $ligne = 'flex justify-between gap-4 py-2';
@endphp
<x-layouts.app :title="'Location '.$l->numero_contrat">
    <x-page-header :title="'Contrat '.$l->numero_contrat" :subtitle="'Enregistré le '.$l->created_at->format('d/m/Y').' · '.$l->client->nom">
        <x-button variant="light" icon="arrow" :href="route('locations.index')">Retour à la liste</x-button>
        <x-button variant="light" icon="download" :href="route('locations.contrat', $l)" target="_blank">Imprimer le contrat</x-button>
        <x-button variant="light" icon="edit" data-modal-target="modal-location-{{ $l->id }}" data-modal-toggle="modal-location-{{ $l->id }}">Modifier</x-button>
        @if ($l->statut === 'Actif')
            <x-button icon="check" data-modal-target="modal-cloturer" data-modal-toggle="modal-cloturer">Clôturer (retour)</x-button>
        @endif
    </x-page-header>

    <x-flash />

    @if ($l->estEnRetard())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            En retard : le retour était prévu le {{ $l->date_fin->format('d/m/Y') }} ({{ $l->date_fin->locale('fr')->diffForHumans() }}). Clôturez la location dès que le véhicule est rentré.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Trajet --}}
            <section class="rounded-xl border border-gray-200 bg-white shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-base font-bold text-gray-900">Trajet et période</h2>
                    <x-badge :color="$l->couleurStatut()">{{ $l->statutAffiche() }}</x-badge>
                </div>
                <dl class="grid gap-x-6 gap-y-4 px-6 py-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Destination</dt><dd class="mt-0.5 font-medium">{{ $l->destination ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Durée</dt><dd class="mt-0.5 font-medium">{{ $l->nombre_jours }} jour(s)</dd></div>
                    <div><dt class="text-gray-500">Départ</dt><dd class="mt-0.5 font-medium">{{ ucfirst($l->date_depart->locale('fr')->translatedFormat('l d/m/Y')) }}</dd></div>
                    <div><dt class="text-gray-500">Retour prévu</dt><dd class="mt-0.5 font-medium">{{ ucfirst($l->date_fin->locale('fr')->translatedFormat('l d/m/Y')) }}</dd></div>
                    <div><dt class="text-gray-500">Retour réel</dt><dd class="mt-0.5 font-medium">{{ $l->date_retour?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Argent perçu le</dt><dd class="mt-0.5 font-medium">{{ $l->date_perception?->format('d/m/Y') ?? 'Non perçu' }}</dd></div>
                </dl>
            </section>

            {{-- Montants --}}
            <section class="rounded-xl border border-gray-200 bg-white shadow-xs">
                <h2 class="border-b border-gray-100 px-6 py-4 text-base font-bold text-gray-900">Montants</h2>
                <div class="divide-y divide-gray-100 px-6 py-3 text-sm">
                    <div class="{{ $ligne }}"><span class="text-gray-500">Montant journalier × jours</span><span class="tabular-nums">{{ Format::usd($l->montant_journalier) }} $ × {{ $l->nombre_jours }}</span></div>
                    <div class="{{ $ligne }}"><span class="text-gray-500">Sous-total</span><span class="tabular-nums">{{ Format::usd($l->sous_total) }} $</span></div>
                    <div class="{{ $ligne }}"><span class="text-gray-500">− Commission ({{ rtrim(rtrim(number_format((float) $l->pourcentage_commission, 2, ',', ''), '0'), ',') }} %)</span><span class="tabular-nums">{{ Format::usd($l->commission) }} $</span></div>
                    <div class="{{ $ligne }} text-base font-bold text-primary-700"><span>Total USD</span><span class="tabular-nums">{{ Format::usd($l->total_usd) }} $</span></div>
                    <div class="{{ $ligne }}"><span class="text-gray-500">Taux de change</span><span class="tabular-nums">1 $ = {{ Format::cdf($l->taux_change) }} FC</span></div>
                    <div class="{{ $ligne }} font-semibold"><span>Total CDF</span><span class="tabular-nums">{{ Format::cdf($l->total_cdf) }} FC</span></div>
                </div>
            </section>

            @if ($l->observation)
                <section class="rounded-xl border border-gray-200 bg-white px-6 py-5 text-sm shadow-xs">
                    <h2 class="mb-2 text-base font-bold text-gray-900">Observation</h2>
                    <p class="whitespace-pre-line text-gray-700">{{ $l->observation }}</p>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white px-6 py-5 text-sm shadow-xs">
                <h2 class="mb-3 text-base font-bold text-gray-900">Client</h2>
                <p class="font-semibold text-gray-900">{{ $l->client->nom }}</p>
                <p class="text-gray-600">{{ $l->client->telephone ?? '—' }}</p>
                <p class="text-gray-600">{{ $l->client->adresse ?? '' }}</p>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white px-6 py-5 text-sm shadow-xs">
                <h2 class="mb-3 text-base font-bold text-gray-900">Véhicule et chauffeur</h2>
                @if ($l->vehicule)
                    <p class="font-semibold text-gray-900">{{ $l->vehicule->marque }} {{ $l->vehicule->modele }}</p>
                    <p class="mt-1"><span class="rounded-md border-2 border-gray-800 px-2 py-0.5 font-mono text-xs font-bold">{{ $l->vehicule->plaque }}</span></p>
                @else
                    <p class="text-gray-500">Aucun véhicule assigné.</p>
                @endif
                <p class="mt-3 text-gray-500">Chauffeur</p>
                @if ($l->chauffeur)
                    <a href="{{ route('employes.show', $l->chauffeur) }}" class="font-semibold text-primary-700 hover:underline">{{ $l->chauffeur->nom }}</a>
                    <p class="text-gray-600">{{ $l->chauffeur->telephone }}</p>
                @else
                    <p class="text-gray-500">—</p>
                @endif
            </section>

            @if ($l->piece_jointe)
                <a href="{{ asset('storage/'.$l->piece_jointe) }}" target="_blank" class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-4 text-sm font-semibold text-primary-700 shadow-xs hover:bg-gray-50">
                    <x-icon name="file" class="h-4 w-4" /> Voir la pièce jointe
                </a>
            @endif

            <div class="flex flex-wrap justify-end gap-2">
                @if ($l->statut === 'Actif')
                    <form method="POST" action="{{ route('locations.annuler', $l) }}" onsubmit="return confirm('Annuler la location {{ $l->numero_contrat }} ? Elle ne comptera plus dans les recettes.')">
                        @csrf @method('PATCH')
                        <button type="submit" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-accent-700 hover:bg-accent-50">Annuler la location</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('locations.destroy', $l) }}" onsubmit="return confirm('Supprimer définitivement la location {{ $l->numero_contrat }} ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><x-icon name="trash" class="h-4 w-4" /> Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <x-slot:modals>
        @include('locations._clients')

        <x-modal :id="'modal-location-'.$l->id" :title="'Modifier '.$l->numero_contrat" size="max-w-3xl" :form-id="'form-location-'.$l->id" :action="route('locations.update', $l)" method="PUT" submit="Mettre à jour">
            @include('locations._form', ['l' => $l, 'p' => 'loc'.$l->id])
        </x-modal>

        @if ($l->statut === 'Actif')
            <x-modal id="modal-cloturer" title="Clôturer la location" size="max-w-lg" :action="route('locations.cloturer', $l)" method="PATCH" submit="Clôturer">
                <input type="hidden" name="_form" value="cloturer">
                <p class="text-sm text-gray-600">Enregistrez le retour réel du véhicule. La location passera à « Terminé »@if ($l->vehicule), et le véhicule {{ $l->vehicule->plaque }} redeviendra disponible @endif.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field prefix="cloturer" label="Date de retour *" name="date_retour" type="date" :value="now()->format('Y-m-d')" required />
                    <x-field prefix="cloturer" label="Argent perçu le" name="date_perception" type="date" :value="$l->date_perception?->format('Y-m-d')" />
                </div>
            </x-modal>
        @endif

        @if ($errors->any() && old('_form'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const id = @js(old('_form')) === 'cloturer' ? 'modal-cloturer' : 'modal-location-{{ $l->id }}';
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

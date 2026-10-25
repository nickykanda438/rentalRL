@use('App\Support\Format')
@php
    $f = $fonds;
    $th = 'px-5 py-3';
    $reste = $f->reste();
    $listes = ['categories' => $categories, 'vehicules' => $vehicules, 'employes' => $employes, 'caisse' => ['solde' => 0], 'fondsDisponibles' => collect()];
@endphp
<x-layouts.app :title="'Somme '.$f->numero">
    <x-page-header :title="'Somme reçue '.$f->numero" :subtitle="'Reçue de '.$f->provenance.' le '.$f->date_reception->format('d/m/Y')">
        <x-button variant="light" icon="arrow" :href="route('depenses.index', ['onglet' => 'fonds'])" class="print:hidden">Retour</x-button>
        <x-button variant="light" icon="download" onclick="window.print()" class="print:hidden">Imprimer le relevé</x-button>
        <x-button variant="light" icon="edit" data-modal-target="modal-fonds-{{ $f->id }}" data-modal-toggle="modal-fonds-{{ $f->id }}" class="print:hidden">Modifier</x-button>
        @if ($reste > 0)
            <x-button icon="plus" data-modal-target="modal-depense" data-modal-toggle="modal-depense" class="print:hidden">Dépenser sur cette somme</x-button>
        @endif
    </x-page-header>

    <x-flash />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- D'où vient l'argent et pourquoi --}}
        <section class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 text-sm shadow-xs">
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Qui a donné</p>
                <p class="text-lg font-bold text-gray-900">{{ $f->provenance }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-gray-500">Pourquoi</p>
                <p class="text-gray-800">{{ $f->motif }}</p>
            </div>
            <dl class="space-y-2 border-t border-gray-100 pt-4">
                <div class="flex justify-between"><dt class="text-gray-500">Montant reçu</dt><dd class="font-semibold tabular-nums">{{ Format::usd($f->montant_usd) }} $</dd></div>
                @if ($f->devise === 'CDF')<div class="flex justify-between"><dt class="text-gray-500">Saisi en</dt><dd class="tabular-nums">{{ Format::cdf($f->montant_cdf) }} FC (1 $ = {{ Format::cdf($f->taux_change) }})</dd></div>@endif
                <div class="flex justify-between"><dt class="text-gray-500">Reçu par</dt><dd>{{ $f->mode_reception }}@if ($f->reference) · {{ $f->reference }}@endif</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Dépensé</dt><dd class="tabular-nums">{{ Format::usd($f->montantDepense()) }} $</dd></div>
                <div class="flex justify-between border-t border-gray-100 pt-2 text-base"><dt class="font-bold">Reste</dt><dd class="font-bold tabular-nums {{ $reste > 0 ? 'text-teal-700' : 'text-gray-500' }}">{{ Format::usd($reste) }} $</dd></div>
            </dl>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-teal-500" style="width: {{ $f->pourcentageUtilise() }}%"></div></div>
            <p class="text-xs text-gray-500">{{ $f->pourcentageUtilise() }} % utilisé · <x-badge :color="$f->statut() === 'Épuisé' ? 'gray' : 'teal'">{{ $f->statut() }}</x-badge></p>
            @if ($f->piece_jointe)
                <a href="{{ asset('storage/'.$f->piece_jointe) }}" target="_blank" class="inline-flex items-center gap-2 font-semibold text-primary-700 hover:underline print:hidden"><x-icon name="file" class="h-4 w-4" />Pièce jointe</a>
            @endif
            @if ($f->observation)<p class="whitespace-pre-line rounded-lg bg-gray-50 px-3 py-2 text-gray-700">{{ $f->observation }}</p>@endif
        </section>

        <div class="space-y-6 lg:col-span-2">
            {{-- À quoi a servi l'argent --}}
            @if ($parCategorie->isNotEmpty())
                <section class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-xs">
                    <h2 class="mb-3 text-base font-bold text-gray-900">À quoi a servi cet argent</h2>
                    <div class="space-y-2.5 text-sm">
                        @foreach ($parCategorie as $nom => $ligne)
                            <div>
                                <div class="flex justify-between"><span><x-badge :color="$ligne['couleur']">{{ $nom }}</x-badge> <span class="text-xs text-gray-500">{{ $ligne['nombre'] }} dépense(s)</span></span><span class="font-semibold tabular-nums">{{ Format::usd($ligne['montant']) }} $</span></div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary-500" style="width: {{ (float) $f->montant_usd > 0 ? min(100, round($ligne['montant'] / (float) $f->montant_usd * 100)) : 0 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Détail des dépenses --}}
            <x-table title="Dépenses faites sur cette somme">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                    <th class="{{ $th }}">Date / N°</th><th class="{{ $th }}">Qu'est-ce qui a été payé</th><th class="{{ $th }}">Payé à / par</th><th class="{{ $th }} text-right">Montant</th><th class="{{ $th }} text-right">Reste après</th><th class="{{ $th }} text-right print:hidden"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @php $cumul = 0; @endphp
                    @forelse ($depenses as $d)
                        @php $cumul += (float) $d->montant_usd; @endphp
                        <tr class="align-top">
                            <td class="{{ $th }}">{{ $d->date_depense->format('d/m/Y') }}<span class="block font-mono text-xs text-gray-500">{{ $d->numero }}</span></td>
                            <td class="{{ $th }} whitespace-normal">
                                <span class="font-medium text-gray-900">{{ $d->description }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <x-badge :color="$d->categorie->couleur">{{ $d->categorie->nom }}</x-badge>
                                    @if ($d->vehicule)<span class="text-xs text-gray-500">{{ $d->vehicule->plaque }}</span>@endif
                                    @if ($d->piece_jointe)<a href="{{ asset('storage/'.$d->piece_jointe) }}" target="_blank" class="text-xs font-semibold text-primary-700 hover:underline print:hidden">Justificatif</a>@endif
                                </span>
                            </td>
                            <td class="{{ $th }}">{{ $d->beneficiaire ?? '—' }}<span class="block text-xs text-gray-500">par {{ $d->employe?->nom ?? '—' }}</span></td>
                            <td class="{{ $th }} text-right font-semibold tabular-nums">{{ Format::usd($d->montant_usd) }}</td>
                            <td class="{{ $th }} text-right tabular-nums text-gray-500">{{ Format::usd((float) $f->montant_usd - $cumul) }}</td>
                            <td class="{{ $th }} print:hidden">
                                <x-row-actions :modal="'modal-depense-'.$d->id" :delete="route('depenses.destroy', $d)" :confirm="'Supprimer la dépense '.$d->numero.' ? Le montant reviendra sur cette somme.'" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Rien n'a encore été dépensé sur cette somme.</td></tr>
                    @endforelse
                </tbody>
            </x-table>

            @if ($depenses->isEmpty())
                <div class="flex justify-end print:hidden">
                    <form method="POST" action="{{ route('fonds.destroy', $f) }}" onsubmit="return confirm(@js('Supprimer la somme '.$f->numero.' ?'))">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"><x-icon name="trash" class="h-4 w-4" />Supprimer cette somme</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <x-slot:modals>
        @if ($reste > 0)
            <x-modal id="modal-depense" :title="'Dépense sur la somme '.$f->numero" size="max-w-3xl" form-id="form-depense" :action="route('depenses.store')">
                @include('depenses._form', ['d' => null, 'p' => 'nouvelle', 'fondsFixe' => $f] + $listes)
            </x-modal>
        @endif

        @foreach ($depenses as $d)
            <x-modal :id="'modal-depense-'.$d->id" :title="'Modifier '.$d->numero" size="max-w-3xl" :form-id="'form-depense-'.$d->id" :action="route('depenses.update', $d)" method="PUT" submit="Mettre à jour">
                @include('depenses._form', ['d' => $d, 'p' => 'dep'.$d->id, 'fondsFixe' => $f] + $listes)
            </x-modal>
        @endforeach

        <x-modal :id="'modal-fonds-'.$f->id" :title="'Modifier '.$f->numero" size="max-w-2xl" :form-id="'form-fonds-'.$f->id" :action="route('fonds.update', $f)" method="PUT" submit="Mettre à jour">
            @include('fonds._form', ['f' => $f, 'p' => 'fonds'.$f->id])
        </x-modal>

        @if ($errors->any() && old('_form'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = @js(old('_form'));
                    const id = form === 'nouvelle' ? 'modal-depense' : (form.startsWith('fonds') ? 'modal-fonds-{{ $f->id }}' : 'modal-depense-' + form.replace('dep', ''));
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

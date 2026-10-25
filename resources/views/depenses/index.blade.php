@use('App\Support\Format')
@use('App\Models\CategorieDepense')
@php
    $th = 'px-5 py-3';
    if (old('_form') === 'categorie') { $onglet = 'categories'; } // erreur sur le formulaire de catégorie
    $titreMois = $mois ? Format::mois($mois) : 'Tous les mois';
    $listes = compact('categories', 'vehicules', 'employes', 'revenusDisponibles', 'caisse');
    $onglets = [
        'depenses' => ['Dépenses', 'receipt', $stats['nombre']],
        'categories' => ['Catégories', 'list', $categories->count()],
    ];
@endphp
<x-layouts.app title="Dépenses">
    <x-page-header title="Dépenses" :subtitle="'Chaque dépense est payée avec les recettes de location ou avec un autre revenu · '.$titreMois">
        <x-button variant="light" icon="trend" :href="route('revenus.index')">Autres revenus</x-button>
        <x-button icon="plus" data-modal-target="modal-depense" data-modal-toggle="modal-depense">Nouvelle dépense</x-button>
    </x-page-header>

    <x-flash />

    {{-- Sources d'argent : combien reçu, combien utilisé, combien reste --}}
    <section aria-label="Sources d'argent" class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('depenses.index', ['mois' => '', 'source' => 'location']) }}" class="rounded-xl border p-5 transition hover:shadow-md {{ $caisse['solde'] < 0 ? 'border-red-200 bg-red-50' : 'border-primary-200 bg-primary-50' }}">
            <p class="flex items-center gap-2 text-sm font-semibold text-gray-900"><x-icon name="calc" class="h-4 w-4" />Recettes de location</p>
            <p class="mt-2 text-3xl font-bold tabular-nums {{ $caisse['solde'] < 0 ? 'text-red-700' : 'text-primary-800' }}">{{ Format::usd($caisse['solde']) }} $ <span class="text-sm font-medium text-gray-500">restants</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white"><div class="h-full rounded-full bg-primary-600" style="width: {{ $caisse['entrees'] > 0 ? min(100, round($caisse['sorties'] / $caisse['entrees'] * 100)) : 0 }}%"></div></div>
            <p class="mt-2 text-xs text-gray-600">Encaissé {{ Format::usd($caisse['entrees']) }} $ · utilisé {{ Format::usd($caisse['sorties']) }} $</p>
            <p class="mt-1 text-[11px] text-gray-500">Dépenses {{ Format::usd($caisse['detail']['depenses']) }} · avances {{ Format::usd($caisse['detail']['avances']) }} · salaires {{ Format::usd($caisse['detail']['salaires']) }} · crédits {{ Format::usd($caisse['detail']['credits']) }}</p>
        </a>
        <a href="{{ route('revenus.index', ['mois' => '']) }}" class="rounded-xl border border-accent-200 bg-accent-50 p-5 transition hover:shadow-md">
            <p class="flex items-center gap-2 text-sm font-semibold text-gray-900"><x-icon name="trend" class="h-4 w-4" />Autres revenus ({{ $tousLesRevenus->count() }})</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-accent-800">{{ Format::usd($caisseRevenus['solde']) }} $ <span class="text-sm font-medium text-gray-500">restants</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white"><div class="h-full rounded-full bg-accent-500" style="width: {{ $caisseRevenus['entrees'] > 0 ? min(100, round($caisseRevenus['sorties'] / $caisseRevenus['entrees'] * 100)) : 0 }}%"></div></div>
            <p class="mt-2 text-xs text-gray-600">Reçu {{ Format::usd($caisseRevenus['entrees']) }} $ · utilisé {{ Format::usd($caisseRevenus['sorties']) }} $</p>
            <p class="mt-1 text-[11px] text-gray-500">Dépenses {{ Format::usd($caisseRevenus['detail']['depenses']) }} · avances {{ Format::usd($caisseRevenus['detail']['avances']) }} · salaires {{ Format::usd($caisseRevenus['detail']['salaires']) }} · crédits {{ Format::usd($caisseRevenus['detail']['credits']) }}</p>
        </a>
    </section>

    {{-- Détail : chaque autre revenu avec son reste --}}
    @if ($revenusDisponibles->isNotEmpty())
        <section aria-label="Autres revenus disponibles" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($revenusDisponibles->take(8) as $r)
                <a href="{{ route('revenus.show', $r) }}" class="rounded-xl border border-gray-200 bg-white p-3.5 text-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <span class="truncate font-semibold text-gray-900">{{ $r->source }}</span>
                        <span class="shrink-0 font-mono text-[11px] text-gray-500">{{ $r->numero }}</span>
                    </div>
                    <p class="mt-1 font-bold tabular-nums text-accent-800">{{ Format::usd($r->reste()) }} $ <span class="text-xs font-normal text-gray-500">sur {{ Format::usd($r->montant_usd) }} $</span></p>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-accent-500" style="width: {{ $r->pourcentageUtilise() }}%"></div></div>
                </a>
            @endforeach
        </section>
    @endif

    {{-- Onglets --}}
    <div class="border-b border-gray-200">
        <ul class="-mb-px flex flex-wrap gap-2 text-sm font-semibold" data-tabs-toggle="#contenu-depenses"
            data-tabs-active-classes="text-primary-700 border-primary-600" data-tabs-inactive-classes="text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300" role="tablist">
            @foreach ($onglets as $cle => [$libelle, $icone, $nombre])
                <li role="presentation">
                    <button class="inline-flex items-center gap-2 border-b-2 px-4 py-3" id="tab-{{ $cle }}" data-tabs-target="#{{ $cle }}" type="button" role="tab" aria-controls="{{ $cle }}" aria-selected="{{ $onglet === $cle ? 'true' : 'false' }}">
                        <x-icon :name="$icone" class="h-4 w-4" />{{ $libelle }}
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $nombre }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <div id="contenu-depenses">
        {{-- ========== DÉPENSES ========== --}}
        <div id="depenses" class="{{ $onglet === 'depenses' ? '' : 'hidden' }} space-y-4" role="tabpanel" aria-labelledby="tab-depenses">
            <form method="GET" action="{{ route('depenses.index') }}" class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-60 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
                    <label for="recherche" class="sr-only">Rechercher</label>
                    <input id="recherche" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher une description, un bénéficiaire, un n°, une source de revenu..." class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
                </div>
                <input name="mois" type="month" value="{{ $mois }}" onchange="this.form.submit()" aria-label="Mois" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-44">
                <select name="categorie" onchange="this.form.submit()" aria-label="Catégorie" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-44">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) $categorie === $c->id)>{{ $c->nom }}</option>@endforeach
                </select>
                <select name="source" onchange="this.form.submit()" aria-label="Source de l'argent" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-56">
                    <option value="">Toutes les sources</option>
                    <option value="location" @selected($source === 'location')>Recettes de location</option>
                    <option value="revenu" @selected($source === 'revenu')>Tous les autres revenus</option>
                    @foreach ($tousLesRevenus as $r)<option value="revenu-{{ $r->id }}" @selected($source === 'revenu-'.$r->id)>{{ $r->numero }} · {{ $r->source }}</option>@endforeach
                </select>
                @if ($mois)<a href="{{ route('depenses.index', ['mois' => '', 'categorie' => $categorie, 'source' => $source]) }}" class="text-sm font-semibold text-primary-700 hover:underline">Tous les mois</a>@endif
            </form>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat label="Total dépenses (USD)" :value="Format::usd($stats['total_usd'])" />
                <x-stat label="Total (CDF)" :value="Format::cdf($stats['total_cdf'])" />
                <x-stat label="Payé avec les locations" :value="Format::usd($stats['sur_locations'])" />
                <x-stat label="Payé avec autres revenus" :value="Format::usd($stats['sur_revenus'])" tone="accent" />
            </section>

            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                    <th class="{{ $th }}">N° / date</th><th class="{{ $th }}">Dépense</th><th class="{{ $th }}">Payé à / par</th><th class="{{ $th }}">Avec quel argent</th><th class="{{ $th }} text-right">Montant</th><th class="{{ $th }} text-right">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($depenses as $d)
                        <tr class="align-top hover:bg-cream">
                            <td class="{{ $th }}">
                                <span class="font-mono text-xs font-semibold text-gray-700">{{ $d->numero }}</span>
                                <span class="block text-xs text-gray-500">{{ $d->date_depense->format('d/m/Y') }}</span>
                            </td>
                            <td class="{{ $th }} whitespace-normal">
                                <span class="font-medium text-gray-900">{{ $d->description }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <x-badge :color="$d->categorie->couleur">{{ $d->categorie->nom }}</x-badge>
                                    @if ($d->vehicule)<a href="{{ route('vehicules.show', $d->vehicule) }}" class="text-xs text-gray-500 hover:underline">{{ $d->vehicule->plaque }}</a>@endif
                                    @if ($d->piece_jointe)<a href="{{ asset('storage/'.$d->piece_jointe) }}" target="_blank" class="text-xs font-semibold text-primary-700 hover:underline">Justificatif</a>@endif
                                </span>
                            </td>
                            <td class="{{ $th }}">
                                {{ $d->beneficiaire ?? '—' }}
                                <span class="block text-xs text-gray-500">par {{ $d->employe?->nom ?? '—' }}</span>
                            </td>
                            <td class="{{ $th }}">
                                @if ($d->source === 'revenu' && $d->autreRevenu)
                                    <a href="{{ route('revenus.show', $d->autreRevenu) }}" class="inline-flex flex-col rounded-lg bg-accent-50 px-2.5 py-1 text-xs text-accent-800 hover:bg-accent-100">
                                        <span class="font-semibold">Revenu {{ $d->autreRevenu->numero }}</span>
                                        <span>{{ $d->autreRevenu->source }}</span>
                                    </a>
                                @else
                                    <span class="inline-flex rounded-lg bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-800">Recettes de location</span>
                                @endif
                            </td>
                            <td class="{{ $th }} text-right tabular-nums">
                                <span class="font-semibold">{{ Format::usd($d->montant_usd) }} $</span>
                                <span class="block text-xs text-gray-500">{{ Format::cdf($d->montant_cdf) }} FC</span>
                            </td>
                            <td class="{{ $th }}">
                                <x-row-actions :modal="'modal-depense-'.$d->id" :delete="route('depenses.destroy', $d)" :confirm="'Supprimer la dépense '.$d->numero.' ? Le montant reviendra sur sa source.'" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">
                            Aucune dépense pour ces critères.
                            @if ($mois)<a href="{{ route('depenses.index', ['mois' => '']) }}" class="font-semibold text-primary-700 hover:underline">Voir tous les mois</a>@endif
                        </td></tr>
                    @endforelse
                </tbody>
                @if ($depenses->hasPages())
                    <x-slot:footer>
                        <span>Affichage de {{ $depenses->firstItem() }} à {{ $depenses->lastItem() }} sur {{ $depenses->total() }}</span>
                        <span class="flex gap-3">
                            @if ($depenses->previousPageUrl())<a href="{{ $depenses->previousPageUrl() }}" class="font-semibold text-primary-700 hover:underline">← Précédent</a>@endif
                            @if ($depenses->nextPageUrl())<a href="{{ $depenses->nextPageUrl() }}" class="font-semibold text-primary-700 hover:underline">Suivant →</a>@endif
                        </span>
                    </x-slot:footer>
                @endif
            </x-table>
        </div>

        {{-- ========== CATÉGORIES ========== --}}
        <div id="categories" class="{{ $onglet === 'categories' ? '' : 'hidden' }} grid gap-6 lg:grid-cols-3" role="tabpanel" aria-labelledby="tab-categories">
            <x-table class="lg:col-span-2">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr><th class="{{ $th }}">Catégorie</th><th class="{{ $th }}">Type</th><th class="{{ $th }}">État</th><th class="{{ $th }} text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($categories as $c)
                        <tr>
                            <td class="{{ $th }}"><x-badge :color="$c->couleur">{{ $c->nom }}</x-badge></td>
                            <td class="{{ $th }} text-gray-500">{{ $c->par_defaut ? 'Par défaut' : 'Personnalisée' }}</td>
                            <td class="{{ $th }}">{{ $c->active ? 'Active' : 'Désactivée' }}</td>
                            <td class="{{ $th }}">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('depenses.categories.basculer', $c) }}">@csrf @method('PATCH')
                                        <button type="submit" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-100">{{ $c->active ? 'Désactiver' : 'Activer' }}</button>
                                    </form>
                                    @unless ($c->par_defaut)
                                        <form method="POST" action="{{ route('depenses.categories.destroy', $c) }}" onsubmit="return confirm(@js('Supprimer la catégorie '.$c->nom.' ?'))">@csrf @method('DELETE')
                                            <button type="submit" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Supprimer</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
            <form method="POST" action="{{ route('depenses.categories.store') }}" class="h-fit space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                @csrf
                <input type="hidden" name="_form" value="categorie">
                <p class="text-base font-bold text-gray-900">Nouvelle catégorie</p>
                <x-field prefix="categorie" label="Nom *" name="nom" placeholder="Ex. Péage, Pneus, Taxes..." maxlength="50" required />
                <div>
                    <label for="categorie-couleur" class="mb-1.5 block text-sm font-medium text-gray-700">Couleur</label>
                    <select id="categorie-couleur" name="couleur" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        @foreach (CategorieDepense::COULEURS as $cle => $nom)<option value="{{ $cle }}">{{ $nom }}</option>@endforeach
                    </select>
                </div>
                <x-button type="submit" icon="plus" class="w-full">Ajouter</x-button>
            </form>
        </div>
    </div>

    <x-slot:modals>
        <x-modal id="modal-depense" title="Nouvelle dépense" size="max-w-3xl" form-id="form-depense" :action="route('depenses.store')">
            @include('depenses._form', ['d' => null, 'p' => 'nouvelle'] + $listes)
        </x-modal>

        @foreach ($depenses as $d)
            <x-modal :id="'modal-depense-'.$d->id" :title="'Modifier '.$d->numero" size="max-w-3xl" :form-id="'form-depense-'.$d->id" :action="route('depenses.update', $d)" method="PUT" submit="Mettre à jour">
                @include('depenses._form', ['d' => $d, 'p' => 'dep'.$d->id] + $listes)
            </x-modal>
        @endforeach

        @if ($errors->any() && old('_form') && old('_form') !== 'categorie')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = @js(old('_form'));
                    const id = form === 'nouvelle' ? 'modal-depense' : 'modal-depense-' + form.replace('dep', '');
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                });
            </script>
        @endif
    </x-slot:modals>
</x-layouts.app>

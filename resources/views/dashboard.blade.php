@use('App\Support\Format')
@php
    $kpis = [
        ['label' => 'Revenus location (USD)', 'cle' => 'locations', 'icon' => 'building', 'box' => 'bg-primary-50 border-primary-100', 'ic' => 'bg-primary-600 text-white', 'lbl' => 'text-primary-700', 'lien' => route('locations.index', ['mois' => $mois])],
        ['label' => 'Autres revenus (USD)', 'cle' => 'autres', 'icon' => 'trend', 'box' => 'bg-accent-50 border-accent-100', 'ic' => 'bg-accent-500 text-white', 'lbl' => 'text-accent-700', 'lien' => route('revenus.index', ['mois' => $mois])],
        ['label' => 'Dépenses (USD)', 'cle' => 'depenses', 'icon' => 'target', 'box' => 'bg-red-50 border-red-100', 'ic' => 'bg-red-500 text-white', 'lbl' => 'text-red-700', 'lien' => route('depenses.index', ['mois' => $mois]), 'inverse' => true],
        ['label' => 'Paie : avances, salaires, crédits (USD)', 'cle' => 'paie', 'icon' => 'users', 'box' => 'bg-gray-50 border-gray-200', 'ic' => 'bg-gray-700 text-white', 'lbl' => 'text-gray-700', 'lien' => route('paie.index', ['mois' => $mois]), 'inverse' => true],
    ];
    // Flèche et couleur de tendance (pour les dépenses, une hausse est mauvaise)
    $fleche = function (?float $t, bool $inverse = false) {
        if ($t === null) {
            return ['texte' => '—', 'icone' => null, 'couleur' => 'text-gray-400'];
        }
        $bon = $inverse ? $t <= 0 : $t >= 0;

        return ['texte' => ($t > 0 ? '+' : '').$t.' %', 'icone' => $t >= 0 ? 'up' : 'chevd', 'couleur' => $bon ? 'text-primary-600' : 'text-red-600'];
    };
    $pct = fn ($n) => $flotte['total'] ? round($n / $flotte['total'] * 100, 1) : 0;
@endphp
<x-layouts.app title="Tableau de bord">
    <x-page-header title="Tableau de bord" :subtitle="'Vue d\'ensemble de votre activité · '.Format::mois($mois)">
        <form method="GET" action="{{ route('dashboard') }}">
            <label for="periode" class="sr-only">Période</label>
            <input id="periode" name="mois" type="month" value="{{ $mois }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()" class="rounded-lg border border-gray-300 bg-white py-2.5 text-sm focus:border-primary-600 focus:ring-primary-600">
        </form>
        <x-button icon="plus" :href="route('locations.index', ['nouvelle' => 0, 'mois' => ''])">Nouvelle location</x-button>
    </x-page-header>

    <x-flash />

    {{-- Indicateurs du mois (TB-01) --}}
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5" aria-label="Indicateurs du mois">
        @foreach ($kpis as $k)
            @php $t = $fleche($tendance($k['cle']), $k['inverse'] ?? false); @endphp
            <a href="{{ $k['lien'] }}" class="flex gap-4 rounded-xl border p-5 transition hover:shadow-md {{ $k['box'] }}">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $k['ic'] }}"><x-icon :name="$k['icon']" class="h-6 w-6" /></span>
                <div>
                    <p class="text-xs font-semibold {{ $k['lbl'] }}">{{ $k['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ Format::usd($actuel[$k['cle']]) }}</p>
                    <p class="mt-1.5 flex items-center gap-1 text-xs text-gray-500">
                        @if ($t['icone'])<x-icon :name="$t['icone']" class="h-3.5 w-3.5 {{ $t['couleur'] }}" />@endif
                        <b class="{{ $t['couleur'] }}">{{ $t['texte'] }}</b> vs mois précédent
                    </p>
                </div>
            </a>
        @endforeach
        @php $t = $fleche($tendance('solde')); @endphp
        <div class="flex gap-4 rounded-xl border p-5 text-white {{ $actuel['solde'] < 0 ? 'border-red-700 bg-red-700' : 'border-primary-800 bg-primary-800' }}">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15"><x-icon name="wallet" class="h-6 w-6" /></span>
            <div>
                <p class="text-xs font-semibold text-white/80">Solde du mois (USD)</p>
                <p class="mt-1 text-2xl font-bold tabular-nums">{{ Format::usd($actuel['solde']) }}</p>
                <p class="mt-1.5 text-xs text-white/80"><b>{{ $t['texte'] }}</b> vs mois précédent</p>
                <p class="text-[11px] text-white/70">Entrées − dépenses − paie</p>
            </div>
        </div>
    </section>

    {{-- Indicateurs complémentaires --}}
    <section class="grid gap-3 sm:grid-cols-3 xl:grid-cols-6">
        <x-stat label="Locations du mois" :value="$complementaires['nb_locations']" />
        <x-stat label="Commissions (USD)" :value="Format::usd($complementaires['commissions'])" />
        <x-stat label="Revenu moyen / location" :value="Format::usd($complementaires['revenu_moyen'])" />
        <x-stat label="Taux de commission moyen" :value="Format::usd($complementaires['taux_commission']).' %'" />
        <x-stat label="Chauffeurs actifs" :value="$complementaires['chauffeurs_actifs']" />
        <x-stat label="Salaires payés (USD)" :value="Format::usd($complementaires['salaires_payes'])" />
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,2.3fr)_minmax(300px,1fr)]">
        {{-- Graphique 10 mois (TB-02) --}}
        <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-bold">Évolution des entrées et sorties</h2>
                <div class="flex flex-wrap gap-4 text-xs font-medium text-gray-500">
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-primary-600"></i>Location</span>
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-accent-500"></i>Autres revenus</span>
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-red-500"></i>Dépenses</span>
                    <span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-sm bg-gray-600"></i>Paie</span>
                </div>
            </div>
            <div class="mt-6 flex h-64 gap-3" role="img" aria-label="Revenus de location, autres revenus, dépenses et paie sur les 10 derniers mois">
                <div class="flex flex-col justify-between pb-6 text-right text-[11px] text-gray-400">
                    @foreach ([1, 2 / 3, 1 / 3, 0] as $f)<span>{{ Format::cdf($maxGraphique * $f) }}</span>@endforeach
                </div>
                <div class="relative flex-1">
                    <div class="absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between">
                        <div class="border-t border-dashed border-gray-200"></div><div class="border-t border-dashed border-gray-200"></div><div class="border-t border-dashed border-gray-200"></div><div class="border-t border-gray-300"></div>
                    </div>
                    <div class="absolute inset-0 grid grid-cols-10">
                        @foreach ($graphique as $g)
                            <a href="{{ route('dashboard', ['mois' => $g['mois']]) }}" class="group flex flex-col items-center justify-end {{ $g['mois'] === $mois ? 'rounded-md bg-gray-50' : '' }}">
                                <div class="flex h-[calc(100%-1.5rem)] items-end gap-[3px]">
                                    <span class="w-1.5 rounded-t-sm bg-primary-600 sm:w-2.5" style="height: {{ round($g['locations'] / $maxGraphique * 100, 1) }}%" title="Location : {{ Format::usd($g['locations']) }} $"></span>
                                    <span class="w-1.5 rounded-t-sm bg-accent-500 sm:w-2.5" style="height: {{ round($g['autres'] / $maxGraphique * 100, 1) }}%" title="Autres revenus : {{ Format::usd($g['autres']) }} $"></span>
                                    <span class="w-1.5 rounded-t-sm bg-red-500 sm:w-2.5" style="height: {{ round($g['depenses'] / $maxGraphique * 100, 1) }}%" title="Dépenses : {{ Format::usd($g['depenses']) }} $"></span>
                                    <span class="w-1.5 rounded-t-sm bg-gray-600 sm:w-2.5" style="height: {{ round($g['paie'] / $maxGraphique * 100, 1) }}%" title="Paie : {{ Format::usd($g['paie']) }} $"></span>
                                </div>
                                <span class="flex h-6 items-end text-[11px] {{ $g['mois'] === $mois ? 'font-bold text-gray-900' : 'text-gray-500' }}">{{ $g['libelle'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Flotte --}}
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
            <div class="relative h-36">
                @if ($flotte['photo'])
                    <x-vehicule-photo :vehicule="$flotte['photo']" class="h-full w-full" />
                @else
                    <x-photo name="land-cruiser" alt="" :width="800" class="h-full w-full" />
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-primary-950/80 to-transparent"></div>
                <div class="absolute bottom-3 left-4 text-white">
                    <p class="text-xs font-medium text-primary-100">Véhicules en flotte</p>
                    <p class="text-3xl font-bold leading-none">{{ $flotte['total'] }}</p>
                </div>
                <a href="{{ route('vehicules.index') }}" class="absolute right-3 top-3 rounded-lg bg-white/90 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-white">Voir la flotte</a>
            </div>
            <div class="p-5">
                <div class="flex h-2 overflow-hidden rounded-full bg-gray-100" aria-hidden="true">
                    <span class="bg-primary-600" style="width: {{ $pct($flotte['disponibles']) }}%"></span><span class="bg-accent-500" style="width: {{ $pct($flotte['loues']) }}%"></span><span class="bg-red-500" style="width: {{ $pct($flotte['maintenance']) }}%"></span>
                </div>
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    <li><a href="{{ route('vehicules.index', ['statut' => 'Disponible']) }}" class="flex items-center gap-2.5 py-2.5 hover:text-primary-700"><i class="h-2.5 w-2.5 rounded-full bg-primary-600"></i>Disponibles<b class="ml-auto">{{ $flotte['disponibles'] }}</b></a></li>
                    <li><a href="{{ route('vehicules.index', ['statut' => 'Loué']) }}" class="flex items-center gap-2.5 py-2.5 hover:text-primary-700"><i class="h-2.5 w-2.5 rounded-full bg-accent-500"></i>Loués<b class="ml-auto">{{ $flotte['loues'] }}</b></a></li>
                    <li><a href="{{ route('vehicules.index', ['statut' => 'En maintenance']) }}" class="flex items-center gap-2.5 py-2.5 hover:text-primary-700"><i class="h-2.5 w-2.5 rounded-full bg-red-500"></i>En maintenance<b class="ml-auto">{{ $flotte['maintenance'] }}</b></a></li>
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
                @forelse ($locationsEnCours as $l)
                    @php
                        $retourJour = $l->date_fin->isToday();
                        [$statut, $couleur] = $l->estEnRetard() ? ['En retard', 'red'] : ($retourJour ? ['Retour ce jour', 'orange'] : ['Actif', 'green']);
                    @endphp
                    <tr class="hover:bg-cream">
                        <td class="px-4 py-3"><a href="{{ route('locations.show', $l) }}" class="font-mono text-xs font-semibold text-primary-700 hover:underline">{{ $l->numero_contrat }}</a></td>
                        <td class="px-4 py-3">{{ $l->client->nom }}</td>
                        <td class="px-4 py-3">
                            @if ($l->vehicule)
                                <div class="flex items-center gap-3"><x-vehicule-photo :vehicule="$l->vehicule" class="h-9 w-14 rounded-md" />{{ $l->vehicule->modele }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $l->date_fin->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::usd($l->total_usd) }}</td>
                        <td class="px-4 py-3"><x-badge :color="$couleur">{{ $statut }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucune location en cours.</td></tr>
                @endforelse
            </tbody>
            <x-slot:footer><span>{{ $nbEnCours }} location(s) en cours</span><a href="{{ route('locations.index', ['mois' => '', 'statut' => 'Actif']) }}" class="font-semibold text-primary-700 hover:underline">Voir toutes les locations</a></x-slot:footer>
        </x-table>

        <section class="flex flex-col gap-5">
            {{-- Trésorerie --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold">Argent disponible</h2>
                    <span class="text-lg font-bold tabular-nums {{ $tresorerie['total'] < 0 ? 'text-red-600' : 'text-primary-700' }}">{{ Format::usd($tresorerie['total']) }} $</span>
                </div>
                @foreach ([['Recettes de location', $tresorerie['locations'], 'bg-primary-600', route('depenses.index', ['mois' => '', 'source' => 'location'])], ['Autres revenus', $tresorerie['autres'], 'bg-accent-500', route('revenus.index', ['mois' => ''])]] as [$nom, $c, $barre, $lien])
                    <a href="{{ $lien }}" class="mt-3 block text-sm hover:opacity-80">
                        <div class="flex justify-between"><span class="text-gray-600">{{ $nom }}</span><span class="font-semibold tabular-nums {{ $c['solde'] < 0 ? 'text-red-600' : '' }}">{{ Format::usd($c['solde']) }} $</span></div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full {{ $barre }}" style="width: {{ $c['entrees'] > 0 ? min(100, round($c['sorties'] / $c['entrees'] * 100)) : 0 }}%"></div></div>
                        <p class="mt-0.5 text-xs text-gray-500">Reçu {{ Format::usd($c['entrees']) }} $ · utilisé {{ Format::usd($c['sorties']) }} $</p>
                        <p class="text-[11px] text-gray-400">dont paie (avances, salaires, crédits) {{ Format::usd($c['detail']['avances'] + $c['detail']['salaires'] + $c['detail']['credits']) }} $</p>
                    </a>
                @endforeach
            </div>

            {{-- À traiter --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <h2 class="text-base font-bold">À traiter</h2>
                <ul class="mt-2 divide-y divide-gray-100 text-sm">
                    @if ($aTraiter['retards'])
                        <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-red-500"></i>{{ $aTraiter['retards'] }} location(s) en retard<a href="{{ route('locations.index', ['mois' => '', 'statut' => 'En retard']) }}" class="ml-auto"><x-badge color="red">Urgent</x-badge></a></li>
                    @endif
                    @if ($aTraiter['retours_du_jour'])
                        <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-accent-500"></i>{{ $aTraiter['retours_du_jour'] }} retour(s) attendu(s) aujourd'hui<a href="{{ route('vehicules.index') }}" class="ml-auto font-semibold text-primary-700">Voir</a></li>
                    @endif
                    @if ($aTraiter['salaires_a_payer'])
                        <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-accent-500"></i>{{ $aTraiter['salaires_a_payer'] }} salaire(s) de {{ Format::mois(now()->format('Y-m')) }} à payer<a href="{{ route('paie.index', ['onglet' => 'bulletins']) }}" class="ml-auto font-semibold text-primary-700">Payer</a></li>
                    @endif
                    @if ($aTraiter['avances'])
                        <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-primary-600"></i>{{ $aTraiter['avances'] }} avance(s) à déduire : {{ Format::usd($aTraiter['avances_montant']) }} $<a href="{{ route('paie.index') }}" class="ml-auto font-semibold text-primary-700">Voir</a></li>
                    @endif
                    @if ($aTraiter['non_percues'])
                        <li class="flex items-center gap-2.5 py-3"><i class="h-2.5 w-2.5 rounded-full bg-primary-600"></i>{{ $aTraiter['non_percues'] }} location(s) sans date de perception<a href="{{ route('locations.index', ['mois' => '']) }}" class="ml-auto font-semibold text-primary-700">Voir</a></li>
                    @endif
                    @if (! array_filter([$aTraiter['retards'], $aTraiter['retours_du_jour'], $aTraiter['salaires_a_payer'], $aTraiter['avances'], $aTraiter['non_percues']]))
                        <li class="py-3 text-gray-500">Rien d'urgent pour l'instant.</li>
                    @endif
                </ul>
            </div>

            <div class="flex overflow-hidden rounded-xl bg-accent-500 text-white">
                <div class="flex-1 p-5">
                    <p class="text-lg font-bold leading-snug">{{ $flotte['disponibles'] }} véhicule(s) prêt(s) à partir aujourd'hui</p>
                    <p class="mt-1 text-sm text-accent-50">Créez un contrat en moins d'une minute.</p>
                    <a href="{{ route('locations.index', ['nouvelle' => 0, 'mois' => '']) }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-sm font-semibold text-accent-700 hover:bg-accent-50">Nouvelle location<x-icon name="arrow" class="h-4 w-4" /></a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>

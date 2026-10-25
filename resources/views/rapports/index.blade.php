@use('App\Http\Controllers\RapportController')
@use('App\Support\Periode')
@php
    [$titre, $icone, $description] = RapportController::RAPPORTS[$type];
    $valeur = fn ($v, $t) => RapportController::valeurTexte($v, $t);
    $droite = fn ($t) => in_array($t, ['usd', 'cdf', 'pct', 'nombre'], true);
    // Classes écrites en entier pour que Tailwind les détecte
    $colonnesCartes = [5 => 'xl:grid-cols-5', 6 => 'xl:grid-cols-6', 7 => 'xl:grid-cols-7'][count($rapport['cartes'])] ?? '';
@endphp
<x-layouts.app :title="'Rapport · '.$titre">
    <div class="print:hidden">
        <x-page-header title="Rapports" :subtitle="$titre.' · '.$periode->libelle()">
            <x-button variant="light" icon="download" :href="route('rapports.exporter', ['type' => $type] + $periode->parametres())">Exporter (Excel)</x-button>
            <x-button icon="file" onclick="window.print()">Imprimer / PDF</x-button>
        </x-page-header>
    </div>

    {{-- Période --}}
    <form method="GET" action="{{ route('rapports.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 print:hidden" id="form-periode">
        <input type="hidden" name="type" value="{{ $type }}">
        <div>
            <label for="periode" class="mb-1.5 block text-xs font-medium text-gray-600">Période</label>
            <select id="periode" name="periode" class="rounded-lg border border-gray-300 bg-white py-2.5 text-sm focus:border-primary-600 focus:ring-primary-600">
                @foreach (Periode::RACCOURCIS as $cle => $nom)<option value="{{ $cle }}" @selected($periode->raccourci === $cle)>{{ $nom }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="du" class="mb-1.5 block text-xs font-medium text-gray-600">Du</label>
            <input id="du" name="du" type="date" value="{{ $periode->du->format('Y-m-d') }}" class="rounded-lg border border-gray-300 bg-white py-2.5 text-sm focus:border-primary-600 focus:ring-primary-600">
        </div>
        <div>
            <label for="au" class="mb-1.5 block text-xs font-medium text-gray-600">Au</label>
            <input id="au" name="au" type="date" value="{{ $periode->au->format('Y-m-d') }}" class="rounded-lg border border-gray-300 bg-white py-2.5 text-sm focus:border-primary-600 focus:ring-primary-600">
        </div>
        <x-button type="submit" variant="light" icon="search">Afficher</x-button>
        <p class="text-xs text-gray-500">{{ $periode->jours() }} jour(s)</p>
    </form>

    {{-- Choix du rapport --}}
    <nav class="grid gap-3 sm:grid-cols-3 xl:grid-cols-6 print:hidden" aria-label="Rapports">
        @foreach (RapportController::RAPPORTS as $cle => [$nom, $ic, $desc])
            <a href="{{ route('rapports.index', ['type' => $cle] + $periode->parametres()) }}" title="{{ $desc }}" @if ($cle === $type) aria-current="page" @endif
                class="flex items-center gap-2.5 rounded-xl border px-3.5 py-3 text-sm font-semibold transition {{ $cle === $type ? 'border-primary-600 bg-primary-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-primary-200 hover:bg-primary-50' }}">
                <x-icon :name="$ic" class="h-4 w-4 shrink-0" />{{ $nom }}
            </a>
        @endforeach
    </nav>

    {{-- En-tête imprimé --}}
    <header class="hidden items-start justify-between border-b-2 border-primary-600 pb-4 print:flex">
        <x-logo variant="complet" class="h-20 w-auto" />
        <div class="text-right">
            <h1 class="text-xl font-bold uppercase">Rapport · {{ $titre }}</h1>
            <p class="text-sm text-gray-700">{{ ucfirst($periode->libelle()) }}</p>
            <p class="text-xs text-gray-500">Généré le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </header>

    <p class="text-sm text-gray-500 print:hidden">{{ $description }}.</p>

    {{-- Cartes --}}
    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 {{ $colonnesCartes }} print:grid-cols-4">
        @foreach ($rapport['cartes'] as [$libelle, $v, $format, $ton])
            <x-stat :label="$libelle" :value="$valeur($v, $format)" :tone="$ton" />
        @endforeach
    </section>

    {{-- Tableaux --}}
    @foreach ($rapport['tableaux'] as $t)
        <x-table :title="$t['titre']" class="break-inside-avoid">
            <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                @foreach ($t['colonnes'] as [$nom, $format])
                    <th class="px-4 py-3 {{ $droite($format) ? 'text-right' : '' }}">{{ $nom }}</th>
                @endforeach
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($t['lignes'] as $i => $ligne)
                    <tr class="hover:bg-cream">
                        @foreach ($t['colonnes'] as $c => [$nom, $format])
                            <td class="px-4 py-2.5 {{ $droite($format) ? 'text-right tabular-nums' : '' }} {{ $c === 0 ? 'font-medium text-gray-900' : '' }} {{ $format === 'usd' && (float) ($ligne[$c] ?? 0) < 0 ? 'text-red-600' : '' }}">
                                @if ($c === 0 && ! empty($t['liens'][$i]))
                                    <a href="{{ $t['liens'][$i] }}" class="text-primary-700 hover:underline print:text-gray-900">{{ $valeur($ligne[$c], $format) }}</a>
                                @else
                                    {{ $valeur($ligne[$c] ?? null, $format) }}
                                @endif
                                @if ($t['barre'] === $c && (float) ($ligne[$c] ?? 0) > 0)
                                    <span class="mt-1 ml-auto block h-1.5 w-24 overflow-hidden rounded-full bg-gray-100"><span class="block h-full rounded-full bg-primary-500" style="width: {{ min(100, round(abs((float) $ligne[$c]) / $t['max'] * 100)) }}%"></span></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($t['colonnes']) }}" class="px-4 py-8 text-center text-gray-500">Aucune donnée sur cette période.</td></tr>
                @endforelse
            </tbody>
            @if ($t['total'] && $t['lignes'])
                <tfoot class="border-t-2 border-gray-200 bg-gray-50 text-sm font-bold text-gray-900">
                    <tr>
                        @foreach ($t['colonnes'] as $c => [$nom, $format])
                            <td class="px-4 py-3 {{ $droite($format) ? 'text-right tabular-nums' : '' }} {{ $format === 'usd' && (float) ($t['total'][$c] ?? 0) < 0 ? 'text-red-600' : '' }}">{{ $valeur($t['total'][$c] ?? null, $format) }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
            @if ($t['note'])
                <x-slot:footer><span>{{ $t['note'] }}</span></x-slot:footer>
            @endif
        </x-table>
    @endforeach

    <script>
        // Choisir des dates bascule sur « Dates libres » ; un raccourci recharge directement
        document.addEventListener('DOMContentLoaded', () => {
            const f = document.getElementById('form-periode');
            f.periode.addEventListener('change', () => { if (f.periode.value !== 'personnalise') f.submit(); });
            ['du', 'au'].forEach((n) => f[n].addEventListener('change', () => { f.periode.value = 'personnalise'; }));
        });
    </script>
</x-layouts.app>

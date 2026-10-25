@php
    $revenus = [
        ['02/10/2026', 'Vente carburant', '600,00', '960 000'],
        ['05/10/2026', 'Location matériel', '450,00', '720 000'],
        ['10/10/2026', 'Service divers', '1 200,00', '1 920 000'],
        ['16/10/2026', 'Partenariat', '950,00', '1 520 000'],
    ];
@endphp
<x-layouts.app title="Autres revenus">
    <x-page-header title="Autres revenus" subtitle="Entrées d'argent hors location">
        <x-button icon="plus" data-modal-target="modal-revenu" data-modal-toggle="modal-revenu">Nouveau revenu</x-button>
    </x-page-header>

    <x-search-bar placeholder="Rechercher une source..." :filters="[['Tous les mois', 'Octobre 2026', 'Septembre 2026']]" />

    <section class="grid gap-3 sm:grid-cols-3">
        <x-stat label="Total USD" value="3 200,00" />
        <x-stat label="Total CDF" value="5 120 000" />
        <x-stat label="Sources actives" value="6" />
    </section>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="px-5 py-3">Date</th><th class="px-5 py-3">Source / Provenance</th><th class="px-5 py-3 text-right">Montant (USD)</th><th class="px-5 py-3 text-right">Montant (CDF)</th><th class="px-5 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($revenus as [$date, $source, $usd, $cdf])
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3">{{ $date }}</td>
                    <td class="px-5 py-3 font-medium">{{ $source }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $usd }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $cdf }}</td>
                    <td class="px-5 py-3"><x-row-actions modal="modal-revenu" /></td>
                </tr>
            @endforeach
        </tbody>
        <x-slot:footer><x-pagination text="Affichage de 1 à 4 sur 4 entrées" /></x-slot:footer>
    </x-table>

    <x-slot:modals>
        <x-modal id="modal-revenu" title="Nouveau revenu" size="max-w-xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Date" name="date" type="date" />
                <x-field label="Source / Provenance" name="source" placeholder="Ex. Location matériel" />
                <x-field label="Montant (USD)" name="montant_usd" value="0,00" />
                <x-field label="Taux de change (CDF/USD)" name="taux" value="1 600" />
                <div class="sm:col-span-2"><x-field label="Description" name="description" type="textarea" placeholder="Facultatif" /></div>
            </div>
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

@php
    $depenses = [
        ['03/10/2026', 'Carburant', 'orange', 'Achat carburant', '450,00'],
        ['07/10/2026', 'Maintenance', 'green', 'Révision véhicule', '320,00'],
        ['12/10/2026', 'Salaire', 'lime', 'Paie chauffeur', '1 200,00'],
        ['15/10/2026', 'Autre', 'red', 'Fournitures bureau', '150,00'],
    ];
@endphp
<x-layouts.app title="Dépenses">
    <x-page-header title="Dépenses" subtitle="Sorties de caisse, rattachables à un véhicule ou à un employé">
        <x-button icon="plus" data-modal-target="modal-depense" data-modal-toggle="modal-depense">Nouvelle dépense</x-button>
    </x-page-header>

    <x-search-bar placeholder="Rechercher une dépense..." :filters="[['Tous les mois', 'Octobre 2026'], ['Toutes les catégories', 'Carburant', 'Maintenance', 'Assurance', 'Salaire', 'Marketing', 'Autre']]" />

    <section class="grid gap-3 sm:grid-cols-3">
        <x-stat label="Total USD" value="7 850,00" />
        <x-stat label="Total CDF" value="12 560 000" />
        <x-stat label="Solde (USD)" value="7 850,00" tone="ok" />
    </section>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="px-5 py-3">Date</th><th class="px-5 py-3">Catégorie</th><th class="px-5 py-3">Description</th><th class="px-5 py-3 text-right">Montant (USD)</th><th class="px-5 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($depenses as [$date, $cat, $couleur, $desc, $montant])
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3">{{ $date }}</td>
                    <td class="px-5 py-3"><x-badge :color="$couleur">{{ $cat }}</x-badge></td>
                    <td class="px-5 py-3">{{ $desc }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $montant }}</td>
                    <td class="px-5 py-3"><x-row-actions modal="modal-depense" /></td>
                </tr>
            @endforeach
        </tbody>
        <x-slot:footer><x-pagination text="Affichage de 1 à 4 sur 4 entrées" /></x-slot:footer>
    </x-table>

    <x-slot:modals>
        <x-modal id="modal-depense" title="Nouvelle dépense" size="max-w-xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Date" name="date" type="date" />
                <x-field label="Catégorie" name="categorie" :options="['Carburant', 'Maintenance', 'Assurance', 'Salaire', 'Marketing', 'Autre']" />
                <x-field label="Montant (USD)" name="montant_usd" value="0,00" />
                <x-field label="Taux de change (CDF/USD)" name="taux" value="1 600" />
                <x-field label="Véhicule lié" name="vehicule" :options="['Aucun', 'Land Cruiser Prado', 'Land Cruiser V8', 'Land Cruiser 70', 'Toyota Coaster', 'Toyota Corolla']" />
                <x-field label="Employé lié" name="employe" :options="['Aucun', 'Kabasele M.', 'Tshibangu J.', 'Mbuyi S.']" />
                <div class="sm:col-span-2"><x-field label="Description" name="description" type="textarea" placeholder="Détail de la dépense" /></div>
            </div>
            <x-file-input />
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

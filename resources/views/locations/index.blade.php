@php
    $locations = [
        ['C-2026-001', '05/10/2026', 'M. Tshibangu', 'Land Cruiser Prado', 'prado', '800,00', 'Actif', 'green'],
        ['C-2026-002', '08/10/2026', 'Mme Lemaire', 'Land Cruiser 70', 'land-cruiser-70', '650,00', 'Terminé', 'teal'],
        ['C-2026-003', '12/10/2026', 'A. Kabasele', 'Toyota Coaster', null, '1 200,00', 'Actif', 'green'],
        ['C-2026-004', '15/10/2026', 'J. Mbuyi', 'Land Cruiser Prado', 'prado', '950,00', 'Annulé', 'red'],
        ['C-2026-005', '16/10/2026', 'ONG Mwinda', 'Land Cruiser V8', 'land-cruiser', '1 080,00', 'Actif', 'green'],
        ['C-2026-006', '18/10/2026', 'Hôtel Fleuve', 'Toyota Corolla', 'corolla', '520,00', 'Terminé', 'teal'],
    ];
@endphp
<x-layouts.app title="Budget location">
    <x-page-header title="Budget location" small="(Recettes de location)">
        <x-button variant="light" icon="download">Exporter</x-button>
        <x-button icon="plus" data-modal-target="modal-location" data-modal-toggle="modal-location">Nouvelle location</x-button>
    </x-page-header>

    <x-search-bar placeholder="Rechercher un client, contrat..." :filters="[['Tous les mois', 'Octobre 2026', 'Septembre 2026'], ['Tous les statuts', 'Actif', 'Terminé', 'Annulé']]" />

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Revenus USD" value="8 450,00" />
        <x-stat label="Commissions" value="1 250,00" />
        <x-stat label="Total CDF" value="13 600 000" />
        <x-stat label="Nombre d'entrées" value="12" />
    </section>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="px-5 py-3">N° contrat</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Client</th><th class="px-5 py-3">Véhicule</th><th class="px-5 py-3 text-right">Total (USD)</th><th class="px-5 py-3">Statut</th><th class="px-5 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($locations as [$num, $date, $client, $veh, $photo, $total, $statut, $couleur])
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3 font-medium">{{ $num }}</td>
                    <td class="px-5 py-3">{{ $date }}</td>
                    <td class="px-5 py-3">{{ $client }}</td>
                    <td class="px-5 py-3"><div class="flex items-center gap-3"><x-photo :name="$photo" :alt="$veh" :width="160" class="h-9 w-14 rounded-md" />{{ $veh }}</div></td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $total }}</td>
                    <td class="px-5 py-3"><x-badge :color="$couleur">{{ $statut }}</x-badge></td>
                    <td class="px-5 py-3"><x-row-actions modal="modal-location" /></td>
                </tr>
            @endforeach
        </tbody>
        <x-slot:footer><x-pagination text="Affichage de 1 à 6 sur 12 entrées" /></x-slot:footer>
    </x-table>

    <x-slot:modals>
        <x-modal id="modal-location" title="Nouveau budget location" size="max-w-3xl" form-id="form-location">
            <p class="text-sm font-bold text-gray-900">Informations générales</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Date de départ" name="date_depart" type="date" />
                <x-field label="Destination" name="destination" placeholder="Ex. Matadi" />
                <x-field label="Chauffeur" name="chauffeur" :options="['Sélectionner', 'Mukendi Kalala', 'Kabeya Tshibangu', 'Lukusa Mavungu', 'Ngalula D.']" />
                <x-field label="Véhicule (facultatif)" name="vehicule" :options="['Sélectionner', 'Land Cruiser Prado · CG 1234 AB', 'Land Cruiser V8 · CG 9012 AD', 'Toyota Corolla · CG 7788 AF']" />
                <x-field label="Montant journalier (USD)" name="montant_journalier" value="120" inputmode="decimal" />
                <x-field label="Nombre de jours" name="nombre_jours" type="number" value="3" min="1" />
            </div>
            <p class="text-sm font-bold text-gray-900">Client</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Nom" name="client_nom" placeholder="Nom du client" />
                <x-field label="Adresse" name="client_adresse" placeholder="Adresse" />
                <x-field label="Téléphone" name="client_telephone" type="tel" placeholder="+243 ..." />
            </div>
            <p class="text-sm font-bold text-gray-900">Commission et taux</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Pourcentage de commission" name="pourcentage" value="10" inputmode="decimal" />
                <x-field label="Taux de change (CDF/USD)" name="taux" value="2800" inputmode="decimal" />
                <x-field label="Date de perception" name="date_perception" type="date" />
            </div>
            <div class="grid grid-cols-2 gap-3 rounded-xl border border-primary-100 bg-primary-50 p-4 sm:grid-cols-4">
                <div><p class="text-xs text-gray-600">Sous-total</p><p class="mt-1 font-bold tabular-nums" data-calc="brut">0,00 $</p></div>
                <div><p class="text-xs text-gray-600">Commission</p><p class="mt-1 font-bold tabular-nums" data-calc="commission">0,00 $</p></div>
                <div><p class="text-xs text-gray-600">Total USD</p><p class="mt-1 font-bold tabular-nums text-primary-700" data-calc="total">0,00 $</p></div>
                <div><p class="text-xs text-gray-600">Total CDF</p><p class="mt-1 font-bold tabular-nums" data-calc="cdf">0 FC</p></div>
            </div>
            <x-file-input />
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

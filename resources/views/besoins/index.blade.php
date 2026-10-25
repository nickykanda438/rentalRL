@php
    $besoins = [
        ['04/10/2026', 'Fournitures', 'Pièces véhicule', 5, '500,00', 'Payé', 'green'],
        ['10/10/2026', 'Équipement', 'GPS', 2, '300,00', 'En attente', 'orange'],
        ['14/10/2026', 'Carburant', 'Bidon 20L', 10, '400,00', 'Payé', 'green'],
        ['18/10/2026', 'Bureau', 'Matériel informatique', 3, '1 250,00', 'En attente', 'orange'],
    ];
@endphp
<x-layouts.app title="Besoins">
    <x-page-header title="Besoins" small="(Demandes d'achat)">
        <x-button icon="plus" data-modal-target="modal-besoin" data-modal-toggle="modal-besoin">Nouveau besoin</x-button>
    </x-page-header>

    <x-search-bar placeholder="Rechercher un besoin..." :filters="[['Tous les mois', 'Octobre 2026'], ['Tous les statuts', 'Payé', 'En attente']]" />

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Total besoins" value="2 450,00" />
        <x-stat label="Payés" value="1 800,00" tone="ok" />
        <x-stat label="En attente" value="650,00" tone="warn" />
        <x-stat label="Nombre" value="5" />
    </section>

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="px-5 py-3">Date</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Description</th><th class="px-5 py-3 text-right">Quantité</th><th class="px-5 py-3 text-right">Total (USD)</th><th class="px-5 py-3">Statut</th><th class="px-5 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($besoins as [$date, $type, $desc, $qte, $total, $statut, $couleur])
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3">{{ $date }}</td>
                    <td class="px-5 py-3 font-medium">{{ $type }}</td>
                    <td class="px-5 py-3">{{ $desc }}</td>
                    <td class="px-5 py-3 text-right">{{ $qte }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $total }}</td>
                    <td class="px-5 py-3"><x-badge :color="$couleur">{{ $statut }}</x-badge></td>
                    <td class="px-5 py-3"><x-row-actions modal="modal-besoin" /></td>
                </tr>
            @endforeach
        </tbody>
        <x-slot:footer><x-pagination text="Affichage de 1 à 4 sur 5 entrées" /></x-slot:footer>
    </x-table>

    <x-slot:modals>
        <x-modal id="modal-besoin" title="Nouveau besoin" size="max-w-xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Date" name="date" type="date" />
                <x-field label="Requérant (service)" name="requerant" placeholder="Ex. Service maintenance" />
                <x-field label="Type de besoin" name="type" :options="['Fournitures', 'Équipement', 'Carburant', 'Bureau', 'Autre']" />
                <x-field label="Unité" name="unite" :options="['Pièces', 'Unités', 'Boîtes', 'Litres', 'Kits']" />
                <x-field label="Quantité" name="quantite" type="number" value="1" min="1" />
                <x-field label="Prix unitaire (USD)" name="prix_unitaire" value="0,00" />
                <x-field label="Total à payer (USD)" name="total" value="0,00" />
                <x-field label="Montant payé (USD)" name="paye" value="0,00" />
                <div class="sm:col-span-2"><x-field label="Description" name="description" type="textarea" placeholder="Détail du besoin" /></div>
            </div>
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

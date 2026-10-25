@php
    $employes = [
        ['Kabasele M.', 'Directeur', 'Administrateur', '+243 81 234 5678', '1 500,00', 'Actif', 'green'],
        ['Tshibangu J.', 'Logisticien', 'Manager', '+243 82 345 6789', '900,00', 'Actif', 'green'],
        ['Mbuyi S.', 'Comptable', 'Comptable', '+243 97 300 1124', '750,00', 'Actif', 'green'],
        ['Lusamba P.', 'Mécanicien', 'Maintenance', '+243 89 552 0347', '450,00', 'Suspendu', 'orange'],
        ['Ngalula D.', 'Chauffeur', 'Chauffeur', '+243 99 101 4456', '400,00', 'Actif', 'green'],
    ];
@endphp
<x-layouts.app title="Employés">
    <x-page-header title="Employés" subtitle="Fiches du personnel ; un changement de salaire est reporté sur la paie">
        <x-button variant="light" icon="settings" :href="route('parametres.index')">Gérer les rôles</x-button>
        <x-button icon="plus" data-modal-target="modal-employe" data-modal-toggle="modal-employe">Nouvel employé</x-button>
    </x-page-header>

    <x-search-bar placeholder="Rechercher un employé..." :filters="[['Tous les rôles', 'Administrateur', 'Manager', 'Comptable', 'Maintenance', 'Chauffeur'], ['Tous les statuts', 'Actif', 'Suspendu', 'Licencié']]" />

    <x-table>
        <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
            <th class="px-5 py-3">Nom &amp; prénom</th><th class="px-5 py-3">Poste</th><th class="px-5 py-3">Rôle</th><th class="px-5 py-3">Téléphone</th><th class="px-5 py-3 text-right">Salaire (USD)</th><th class="px-5 py-3">Statut</th><th class="px-5 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($employes as $i => [$nom, $poste, $role, $tel, $salaire, $statut, $couleur])
                <tr class="hover:bg-cream">
                    <td class="px-5 py-3"><x-avatar :name="$nom" :tone="$i" /></td>
                    <td class="px-5 py-3">{{ $poste }}</td>
                    <td class="px-5 py-3">{{ $role }}</td>
                    <td class="px-5 py-3 tabular-nums">{{ $tel }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $salaire }}</td>
                    <td class="px-5 py-3"><x-badge :color="$couleur">{{ $statut }}</x-badge></td>
                    <td class="px-5 py-3"><x-row-actions modal="modal-employe" /></td>
                </tr>
            @endforeach
        </tbody>
        <x-slot:footer><x-pagination text="Affichage de 1 à 5 sur 8 employés" /></x-slot:footer>
    </x-table>

    <x-slot:modals>
        <x-modal id="modal-employe" title="Nouvel employé" size="max-w-3xl">
            <p class="text-sm font-bold">Informations de base</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Nom complet" name="nom" placeholder="Nom et prénom" />
                <x-field label="Email" name="email" type="email" placeholder="nom@rental.cd" />
                <x-field label="Téléphone" name="telephone" type="tel" placeholder="+243 ..." />
                <x-field label="Poste" name="poste" placeholder="Ex. Chauffeur" />
                <x-field label="Rôle" name="role" :options="['Chauffeur', 'Manager', 'Comptable', 'Maintenance', 'Administrateur']" />
                <x-field label="Salaire (USD)" name="salaire" value="0,00" />
                <x-field label="Date d'embauche" name="date_embauche" type="date" />
                <x-field label="Statut" name="statut" :options="['Actif', 'Suspendu', 'Licencié']" />
                <x-field label="Lieu de travail" name="lieu_travail" placeholder="Siège, Kinshasa" />
            </div>
            <p class="text-sm font-bold">Informations personnelles</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field label="Genre" name="genre" :options="['Homme', 'Femme']" />
                <x-field label="Date de naissance" name="date_naissance" type="date" />
                <x-field label="Lieu de naissance" name="lieu_naissance" placeholder="Kinshasa" />
                <x-field label="État civil" name="etat_civil" :options="['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf(ve)']" />
                <x-field label="Niveau d'études" name="niveau_etudes" :options="['Diplôme d\'État', 'Gradué', 'Licence', 'Master', 'Doctorat']" />
                <x-field label="Domaine d'étude" name="domaine" placeholder="Ex. Mécanique" />
                <x-field label="Adresse physique" name="adresse" placeholder="Avenue, quartier, commune" wide />
            </div>
            <x-file-input name="photo" label="Photo et pièces jointes" />
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

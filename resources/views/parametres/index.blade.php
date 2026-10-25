@php
    $utilisateurs = [
        ['Admin', 'admin@rental.cd', 'Administrateur', '21/10/2026 08:12', 'Actif', 'green'],
        ['Tshibangu J.', 'j.tshibangu@rental.cd', 'Manager', '20/10/2026 09:40', 'Actif', 'green'],
        ['Mbuyi S.', 's.mbuyi@rental.cd', 'Comptable', '19/10/2026 17:05', 'Actif', 'green'],
        ['Lusamba P.', 'atelier@rental.cd', 'Maintenance', '14/10/2026 15:22', 'Inactif', 'gray'],
    ];
    $droits = [
        ['Budget location', 'Tout', 'Tout', 'Saisie', '—'],
        ['Flotte', 'Tout', 'Tout', 'Lecture', 'Maintenance'],
        ['Revenus et dépenses', 'Tout', 'Saisie', 'Tout', '—'],
        ['Besoins', 'Tout', 'Tout', 'Saisie', 'Saisie'],
        ['Employés', 'Tout', 'Lecture', 'Lecture', '—'],
        ['Paie', 'Tout', 'Approuver', 'Payer', '—'],
        ['Paramètres', 'Tout', '—', '—', '—'],
    ];
    $th = 'px-5 py-3';
@endphp
<x-layouts.app title="Paramètres">
    <x-page-header title="Paramètres" subtitle="Réservé au profil Administrateur" />

    <div class="grid items-start gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
        <nav class="flex flex-col gap-0.5 rounded-xl border border-gray-200 bg-white p-2 text-sm" aria-label="Rubriques">
            <a href="#utilisateurs" class="rounded-lg bg-primary-50 px-3 py-2.5 font-semibold text-primary-700">Utilisateurs</a>
            <a href="#droits" class="rounded-lg px-3 py-2.5 font-medium text-gray-700 hover:bg-gray-50">Rôles et droits</a>
            <a href="#taux" class="rounded-lg px-3 py-2.5 font-medium text-gray-700 hover:bg-gray-50">Taux de change</a>
            <a href="#taux" class="rounded-lg px-3 py-2.5 font-medium text-gray-700 hover:bg-gray-50">Flotte et alertes</a>
        </nav>

        <div class="flex min-w-0 flex-col gap-5">
            <x-table id="utilisateurs" title="Utilisateurs">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr><th class="{{ $th }}">Utilisateur</th><th class="{{ $th }}">Email</th><th class="{{ $th }}">Profil</th><th class="{{ $th }}">Dernière connexion</th><th class="{{ $th }}">État</th><th class="{{ $th }} text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($utilisateurs as $i => [$nom, $email, $profil, $connexion, $etat, $c])
                        <tr class="hover:bg-cream"><td class="{{ $th }}"><x-avatar :name="$nom" :tone="$i" /></td><td class="{{ $th }}">{{ $email }}</td><td class="{{ $th }}">{{ $profil }}</td><td class="{{ $th }} tabular-nums">{{ $connexion }}</td><td class="{{ $th }}"><x-badge :color="$c">{{ $etat }}</x-badge></td><td class="{{ $th }}"><x-row-actions modal="modal-utilisateur" /></td></tr>
                    @endforeach
                </tbody>
                <x-slot:footer><span>4 utilisateurs</span><x-button icon="plus" data-modal-target="modal-utilisateur" data-modal-toggle="modal-utilisateur">Inviter un utilisateur</x-button></x-slot:footer>
            </x-table>

            <x-table id="droits" title="Droits par profil">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr><th class="{{ $th }}">Module</th><th class="{{ $th }} text-center">Administrateur</th><th class="{{ $th }} text-center">Manager</th><th class="{{ $th }} text-center">Comptable</th><th class="{{ $th }} text-center">Maintenance</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($droits as [$module, $admin, $manager, $comptable, $maintenance])
                        <tr><td class="{{ $th }} font-semibold">{{ $module }}</td>
                            @foreach ([$admin, $manager, $comptable, $maintenance] as $d)
                                <td class="{{ $th }} text-center">@if ($d === 'Tout')<x-badge>Tout</x-badge>@elseif ($d === '—')<span class="text-gray-400">—</span>@else{{ $d }}@endif</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-table>

            <div id="taux" class="grid gap-5 md:grid-cols-2">
                <form class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                    <h2 class="text-base font-bold">Taux de change du jour</h2>
                    <div class="mt-4"><x-field label="1 USD = ? CDF" name="taux_jour" value="2 800,00" /></div>
                    <x-button type="submit" class="mt-4">Enregistrer</x-button>
                </form>
                <form class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                    <h2 class="text-base font-bold">Alerte flotte</h2>
                    <div class="mt-4"><x-field label="Seuil critique de disponibilité (%)" name="seuil" value="40" /></div>
                    <p class="mt-2 text-sm text-gray-500">Alerte quand moins de 40 % des véhicules sont disponibles.</p>
                </form>
            </div>
        </div>
    </div>

    <x-slot:modals>
        <x-modal id="modal-utilisateur" title="Inviter un utilisateur" size="max-w-xl" submit="Envoyer l'invitation">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Nom" name="nom" placeholder="Nom complet" />
                <x-field label="Email" name="email" type="email" placeholder="nom@rental.cd" />
                <x-field label="Profil" name="profil" :options="['Administrateur', 'Manager', 'Comptable', 'Maintenance']" />
                <x-field label="Employé lié" name="employe" :options="['Aucun', 'Kabasele M.', 'Tshibangu J.', 'Mbuyi S.']" />
            </div>
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

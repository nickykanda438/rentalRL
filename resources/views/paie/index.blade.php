@php
    $avances = [
        ['Kabasele M.', 'Avance', '300,00', '05/10/2026', 'Payé', 'green'],
        ['Mbuyi S.', 'Crédit', '500,00', '10/10/2026', 'Payé', 'green'],
        ['Lusamba P.', 'Avance', '200,00', '15/10/2026', 'En cours', 'orange'],
    ];
    $credits = [
        ['Mbuyi S.', '500,00', '10 mois', '10 %', '55,00', '385,00', 'Actif', 'teal'],
        ['Tshibangu J.', '1 000,00', '12 mois', '10 %', '91,67', '1 100,00', 'En attente', 'orange'],
    ];
    $bulletins = [
        ['Kabasele M.', '1 500,00', '300,00', '0,00', '1 200,00', 'Payé', 'green'],
        ['Tshibangu J.', '900,00', '0,00', '0,00', '900,00', 'Payé', 'green'],
        ['Mbuyi S.', '750,00', '0,00', '55,00', '695,00', 'En attente', 'orange'],
        ['Ngalula D.', '400,00', '0,00', '0,00', '400,00', 'En attente', 'orange'],
    ];
    $th = 'px-5 py-3';
@endphp
<x-layouts.app title="Paie">
    <x-page-header title="Paie" subtitle="Avances, crédits et bulletins · octobre 2026">
        <x-button variant="light" icon="download">Bulletins PDF</x-button>
        <x-button icon="plus" data-modal-target="modal-paiement" data-modal-toggle="modal-paiement">Nouveau paiement</x-button>
    </x-page-header>

    {{-- Onglets Flowbite --}}
    <div class="border-b border-gray-200">
        <ul class="-mb-px flex flex-wrap gap-2 text-sm font-semibold" id="onglets-paie" data-tabs-toggle="#contenu-paie"
            data-tabs-active-classes="text-primary-700 border-primary-600" data-tabs-inactive-classes="text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300" role="tablist">
            <li role="presentation"><button class="inline-flex items-center gap-2 border-b-2 px-4 py-3" id="tab-avances" data-tabs-target="#avances" type="button" role="tab" aria-controls="avances" aria-selected="true"><x-icon name="wallet" class="h-4 w-4" />Avances</button></li>
            <li role="presentation"><button class="inline-flex items-center gap-2 border-b-2 px-4 py-3" id="tab-credits" data-tabs-target="#credits" type="button" role="tab" aria-controls="credits" aria-selected="false"><x-icon name="credit" class="h-4 w-4" />Crédits</button></li>
            <li role="presentation"><button class="inline-flex items-center gap-2 border-b-2 px-4 py-3" id="tab-bulletins" data-tabs-target="#bulletins" type="button" role="tab" aria-controls="bulletins" aria-selected="false"><x-icon name="doc" class="h-4 w-4" />Bulletins</button></li>
        </ul>
    </div>

    <x-search-bar placeholder="Rechercher un employé..." :filters="[['Tous les mois', 'Octobre 2026', 'Septembre 2026']]" />

    <div id="contenu-paie">
        <div id="avances" role="tabpanel" aria-labelledby="tab-avances">
            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr><th class="{{ $th }}">Employé</th><th class="{{ $th }}">Type</th><th class="{{ $th }} text-right">Montant (USD)</th><th class="{{ $th }}">Date</th><th class="{{ $th }}">Statut</th><th class="{{ $th }} text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($avances as $i => [$nom, $type, $montant, $date, $statut, $c])
                        <tr class="hover:bg-cream"><td class="{{ $th }}"><x-avatar :name="$nom" :tone="$i" /></td><td class="{{ $th }}">{{ $type }}</td><td class="{{ $th }} text-right tabular-nums">{{ $montant }}</td><td class="{{ $th }}">{{ $date }}</td><td class="{{ $th }}"><x-badge :color="$c">{{ $statut }}</x-badge></td><td class="{{ $th }}"><x-row-actions modal="modal-paiement" /></td></tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
        <div id="credits" class="hidden" role="tabpanel" aria-labelledby="tab-credits">
            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr><th class="{{ $th }}">Employé</th><th class="{{ $th }} text-right">Accordé</th><th class="{{ $th }}">Durée</th><th class="{{ $th }}">Taux</th><th class="{{ $th }} text-right">Mensualité</th><th class="{{ $th }} text-right">Reste</th><th class="{{ $th }}">Statut</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($credits as $i => [$nom, $accorde, $duree, $taux, $mensualite, $reste, $statut, $c])
                        <tr class="hover:bg-cream"><td class="{{ $th }}"><x-avatar :name="$nom" :tone="$i + 2" /></td><td class="{{ $th }} text-right tabular-nums">{{ $accorde }}</td><td class="{{ $th }}">{{ $duree }}</td><td class="{{ $th }}">{{ $taux }}</td><td class="{{ $th }} text-right tabular-nums">{{ $mensualite }}</td><td class="{{ $th }} text-right font-semibold tabular-nums">{{ $reste }}</td><td class="{{ $th }}"><x-badge :color="$c">{{ $statut }}</x-badge></td></tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
        <div id="bulletins" class="hidden" role="tabpanel" aria-labelledby="tab-bulletins">
            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr><th class="{{ $th }}">Employé</th><th class="{{ $th }} text-right">Salaire de base</th><th class="{{ $th }} text-right">Avances</th><th class="{{ $th }} text-right">Crédits</th><th class="{{ $th }} text-right">Net à payer</th><th class="{{ $th }}">Statut</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($bulletins as $i => [$nom, $base, $av, $cr, $net, $statut, $c])
                        <tr class="hover:bg-cream"><td class="{{ $th }}"><x-avatar :name="$nom" :tone="$i" /></td><td class="{{ $th }} text-right tabular-nums">{{ $base }}</td><td class="{{ $th }} text-right tabular-nums">{{ $av }}</td><td class="{{ $th }} text-right tabular-nums">{{ $cr }}</td><td class="{{ $th }} text-right font-semibold tabular-nums">{{ $net }}</td><td class="{{ $th }}"><x-badge :color="$c">{{ $statut }}</x-badge></td></tr>
                    @endforeach
                </tbody>
            </x-table>
        </div>
    </div>

    <x-slot:modals>
        <x-modal id="modal-paiement" title="Nouveau paiement" size="max-w-xl">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Type de paiement" name="type" :options="['Avance', 'Remboursement de crédit', 'Salaire']" />
                <x-field label="Employé" name="employe" :options="['Kabasele M.', 'Tshibangu J.', 'Mbuyi S.', 'Lusamba P.', 'Ngalula D.']" />
                <x-field label="Montant (USD)" name="montant" value="0,00" />
                <x-field label="Date de paiement" name="date" type="date" />
                <x-field label="Mode de paiement" name="mode" :options="['Espèces', 'Mobile Money', 'Virement', 'Chèque']" />
                <x-field label="Référence" name="reference" placeholder="N° de transaction" />
                <div class="sm:col-span-2"><x-field label="Commentaire" name="commentaire" type="textarea" placeholder="Facultatif" /></div>
            </div>
        </x-modal>
    </x-slot:modals>
</x-layouts.app>

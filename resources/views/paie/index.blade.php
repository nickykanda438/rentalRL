@use('App\Support\Format')
@use('App\Models\Avance')
@php
    $th = 'px-5 py-3';
    $libelleMois = Format::mois($mois);
    $onglets = [
        'avances' => ['Avances', 'wallet', $avances->count()],
        'credits' => ['Crédits', 'credit', $credits->where('statut', 'Actif')->count()],
        'bulletins' => ['Bulletins', 'doc', $bulletins->count()],
    ];
    $select = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600';
@endphp
<x-layouts.app title="Paie">
    <x-page-header title="Paie" :subtitle="'Avances, crédits et bulletins · '.$libelleMois">
        @if ($bulletins->isNotEmpty())
            <x-button variant="light" icon="download" :href="route('paie.bulletins.imprimer', ['mois' => $mois])" target="_blank">Imprimer les bulletins</x-button>
        @endif
        <x-button variant="light" icon="credit" data-modal-target="modal-credit" data-modal-toggle="modal-credit">Nouveau crédit</x-button>
        <x-button variant="light" icon="plus" data-modal-target="modal-avance" data-modal-toggle="modal-avance">Nouvelle avance</x-button>
        @if ($aPayer['nombre'] > 0)
            <x-button icon="money" data-modal-target="modal-payer-tout" data-modal-toggle="modal-payer-tout">Payer tous les salaires ({{ $aPayer['nombre'] }})</x-button>
        @endif
    </x-page-header>

    <x-flash />

    {{-- Mois et recherche --}}
    <form method="GET" action="{{ route('paie.index') }}" class="flex flex-wrap gap-3">
        <div class="relative min-w-60 flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><x-icon name="search" class="h-4 w-4" /></span>
            <label for="recherche" class="sr-only">Rechercher</label>
            <input id="recherche" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher un employé ou un n° de référence (SAL-..., AV-...)" class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600">
        </div>
        <label for="filtre-mois" class="sr-only">Mois</label>
        <input id="filtre-mois" name="mois" type="month" value="{{ $mois }}" onchange="this.form.submit()" class="w-full rounded-lg border border-gray-300 bg-white py-2.5 text-sm text-gray-700 focus:border-primary-600 focus:ring-primary-600 sm:w-48">
    </form>

    {{-- Chiffres du mois --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Masse salariale (USD)" :value="Format::usd($stats['masse_salariale'])" />
        <x-stat label="Avances du mois (USD)" :value="Format::usd($stats['avances'])" tone="accent" />
        <x-stat label="Retenues crédits (USD)" :value="Format::usd($stats['credits'])" />
        <x-stat :label="'Net à payer (USD) · '.$stats['payes'].'/'.$bulletins->count().' payé(s)'" :value="Format::usd($stats['net'])" tone="ok" />
    </div>

    {{-- Onglets Flowbite --}}
    <div class="border-b border-gray-200">
        <ul class="-mb-px flex flex-wrap gap-2 text-sm font-semibold" id="onglets-paie" data-tabs-toggle="#contenu-paie"
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

    <div id="contenu-paie">
        {{-- ========== AVANCES ========== --}}
        <div id="avances" class="{{ $onglet === 'avances' ? '' : 'hidden' }}" role="tabpanel" aria-labelledby="tab-avances">
            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                    <th class="{{ $th }}">Employé</th><th class="{{ $th }} text-right">Montant (USD)</th><th class="{{ $th }}">Date</th><th class="{{ $th }}">N° référence</th><th class="{{ $th }}">Mode</th><th class="{{ $th }}">Statut</th><th class="{{ $th }} text-right">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($avances as $avance)
                        <tr class="hover:bg-cream">
                            <td class="{{ $th }}"><a href="{{ route('employes.show', $avance->employe) }}" class="hover:underline"><x-avatar :name="$avance->employe->nom" :tone="$loop->index" /></a></td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($avance->montant) }}</td>
                            <td class="{{ $th }}">{{ $avance->date_avance->format('d/m/Y') }}</td>
                            <td class="{{ $th }} font-mono text-xs font-semibold text-gray-700">{{ $avance->numero }}</td>
                            <td class="{{ $th }}">{{ $avance->mode_paiement }}@if ($avance->reference)<span class="block text-xs text-gray-500">Trans. {{ $avance->reference }}</span>@endif</td>
                            <td class="{{ $th }}"><x-badge :color="$avance->couleurStatut()">{{ $avance->statut }}</x-badge></td>
                            <td class="{{ $th }}">
                                @if ($avance->statut === 'En cours')
                                    <x-row-actions :delete="route('paie.avances.destroy', $avance)" :confirm="'Supprimer l\'avance de '.Format::usd($avance->montant).' $ de '.$avance->employe->nom.' ?'" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">Aucune avance en {{ $libelleMois }}.</td></tr>
                    @endforelse
                </tbody>
            </x-table>
        </div>

        {{-- ========== CRÉDITS ========== --}}
        <div id="credits" class="{{ $onglet === 'credits' ? '' : 'hidden' }}" role="tabpanel" aria-labelledby="tab-credits">
            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                    <th class="{{ $th }}">Employé</th><th class="{{ $th }} text-right">Accordé</th><th class="{{ $th }}">Durée</th><th class="{{ $th }}">Taux</th><th class="{{ $th }} text-right">Total dû</th><th class="{{ $th }} text-right">Mensualité</th><th class="{{ $th }}">Début</th><th class="{{ $th }} text-right">Reste</th><th class="{{ $th }}">Statut</th><th class="{{ $th }} text-right">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($credits as $credit)
                        @php $reste = $credit->reste(); @endphp
                        <tr class="hover:bg-cream">
                            <td class="{{ $th }}"><a href="{{ route('employes.show', $credit->employe) }}" class="hover:underline"><x-avatar :name="$credit->employe->nom" :tone="$loop->index + 2" /></a></td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($credit->montant) }}</td>
                            <td class="{{ $th }}">{{ $credit->duree_mois }} mois</td>
                            <td class="{{ $th }}">{{ rtrim(rtrim(number_format((float) $credit->taux, 2, ',', ''), '0'), ',') }} %</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($credit->montant_total) }}</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($credit->mensualite) }}</td>
                            <td class="{{ $th }}">{{ Format::mois($credit->mois_debut) }}</td>
                            <td class="{{ $th }} text-right font-semibold tabular-nums">{{ Format::usd($reste) }}</td>
                            <td class="{{ $th }}"><x-badge :color="$credit->couleurStatut()">{{ $credit->statut }}</x-badge></td>
                            <td class="{{ $th }}">
                                @unless ($credit->aDesRemboursements())
                                    <x-row-actions :delete="route('paie.credits.destroy', $credit)" :confirm="'Supprimer le crédit de '.$credit->employe->nom.' ?'" />
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-10 text-center text-gray-500">Aucun crédit accordé.</td></tr>
                    @endforelse
                </tbody>
            </x-table>
        </div>

        {{-- ========== BULLETINS ========== --}}
        <div id="bulletins" class="{{ $onglet === 'bulletins' ? '' : 'hidden' }} space-y-4" role="tabpanel" aria-labelledby="tab-bulletins">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 text-sm">
                <p class="text-gray-600">
                    Les bulletins reprennent le salaire de la fiche employé, les avances du mois et les mensualités de crédit.
                    <span class="text-gray-500">Un bulletin payé n'est plus modifié.</span>
                </p>
                <form method="POST" action="{{ route('paie.bulletins.generer') }}">
                    @csrf
                    <input type="hidden" name="mois" value="{{ $mois }}">
                    <x-button type="submit" variant="light" icon="calc">{{ $bulletins->isEmpty() ? 'Générer' : 'Mettre à jour' }} les bulletins de {{ $libelleMois }}</x-button>
                </form>
                @if ($aPayer['nombre'] > 0)
                    <x-button icon="money" data-modal-target="modal-payer-tout" data-modal-toggle="modal-payer-tout">Payer tous les salaires</x-button>
                @endif
            </div>

            <x-table>
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600"><tr>
                    <th class="{{ $th }}">Employé</th><th class="{{ $th }}">N° référence</th><th class="{{ $th }} text-right">Salaire de base</th><th class="{{ $th }} text-right">Primes</th><th class="{{ $th }} text-right">Avances</th><th class="{{ $th }} text-right">Crédits</th><th class="{{ $th }} text-right">Net à payer</th><th class="{{ $th }}">Statut</th><th class="{{ $th }} text-right">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($bulletins as $bulletin)
                        <tr class="hover:bg-cream">
                            <td class="{{ $th }}"><a href="{{ route('employes.show', $bulletin->employe) }}" class="hover:underline"><x-avatar :name="$bulletin->employe->nom" :tone="$loop->index" /></a></td>
                            <td class="{{ $th }} font-mono text-xs font-semibold text-gray-700">{{ $bulletin->numero }}</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($bulletin->salaire_base) }}</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($bulletin->primes) }}</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($bulletin->total_avances) }}</td>
                            <td class="{{ $th }} text-right tabular-nums">{{ Format::usd($bulletin->total_credits) }}</td>
                            <td class="{{ $th }} text-right font-semibold tabular-nums {{ $bulletin->net_a_payer < 0 ? 'text-red-600' : '' }}">{{ Format::usd($bulletin->net_a_payer) }}</td>
                            <td class="{{ $th }}">
                                <x-badge :color="$bulletin->couleurStatut()">{{ $bulletin->statut }}</x-badge>
                                @if ($bulletin->estPaye())<span class="ml-1 text-xs text-gray-500">le {{ $bulletin->date_paiement->format('d/m') }}</span>@endif
                            </td>
                            <td class="{{ $th }}">
                                <div class="flex justify-end gap-1">
                                    @unless ($bulletin->estPaye())
                                        <button type="button" data-modal-target="modal-payer-{{ $bulletin->id }}" data-modal-toggle="modal-payer-{{ $bulletin->id }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">
                                            <x-icon name="check" class="h-3.5 w-3.5" />Payer
                                        </button>
                                    @endunless
                                    <a href="{{ route('paie.bulletins.imprimer', ['bulletin' => $bulletin->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900" title="Imprimer le bulletin"><x-icon name="download" class="h-4 w-4" /><span class="sr-only">Imprimer</span></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-gray-500">Aucun bulletin pour {{ $libelleMois }}. Cliquez sur « Générer les bulletins ».</td></tr>
                    @endforelse
                </tbody>
            </x-table>
        </div>
    </div>

    <x-slot:modals>
        {{-- Payer tous les salaires du mois en une fois --}}
        @if ($aPayer['nombre'] > 0)
            <x-modal id="modal-payer-tout" :title="'Payer tous les salaires de '.$libelleMois" size="max-w-xl" :action="route('paie.bulletins.payer-tout')" :submit="'Payer '.Format::usd($aPayer['total']).' $'">
                <input type="hidden" name="_form" value="payer-tout">
                <input type="hidden" name="mois" value="{{ $mois }}">
                <div class="rounded-xl border border-primary-100 bg-primary-50 p-4 text-sm text-primary-900">
                    <p><strong>{{ $aPayer['nombre'] }}</strong> employé(s) actif(s) à payer pour {{ $libelleMois }}.</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ Format::usd($aPayer['total']) }} $</p>
                    <p class="mt-1 text-xs text-primary-800">Net total : salaires + primes − avances du mois − mensualités de crédit.</p>
                </div>
                <ul class="list-inside list-disc space-y-1 text-sm text-gray-600">
                    <li>Les bulletins manquants sont générés automatiquement.</li>
                    <li>Chaque salaire reçoit son numéro de référence (SAL-…) et les avances passent en « Déduite ».</li>
                    <li>Les salaires déjà payés ne sont pas touchés.</li>
                    @if ($aPayer['negatifs'] > 0)
                        <li class="text-red-700">{{ $aPayer['negatifs'] }} employé(s) avec un net négatif ne seront pas payés : à régler à la main.</li>
                    @endif
                </ul>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field prefix="payer-tout" label="Date de paiement *" name="date_paiement" type="date" :value="now()->format('Y-m-d')" required />
                    <x-field prefix="payer-tout" label="Mode de paiement" name="mode_paiement" :options="Avance::MODES_PAIEMENT" />
                </div>
            </x-modal>
        @endif

        {{-- Nouvelle avance --}}
        <x-modal id="modal-avance" title="Nouvelle avance sur salaire" size="max-w-xl" :action="route('paie.avances.store')">
            <input type="hidden" name="_form" value="avance">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="avance-employe" class="mb-1.5 block text-sm font-medium text-gray-700">Employé *</label>
                    <select id="avance-employe" name="employe_id" required class="{{ $select }}">
                        <option value="">— Choisir —</option>
                        @foreach ($employes as $e)
                            <option value="{{ $e->id }}" @selected(old('_form') === 'avance' && (int) old('employe_id') === $e->id)>{{ $e->nom }} (salaire {{ Format::usd($e->salaire) }} $)</option>
                        @endforeach
                    </select>
                </div>
                <x-field prefix="avance" label="Montant (USD) *" name="montant" placeholder="0,00" required />
                <x-field prefix="avance" label="Date de l'avance *" name="date_avance" type="date" :value="now()->format('Y-m-d')" required />
                <x-field prefix="avance" label="Mode de paiement" name="mode_paiement" :options="Avance::MODES_PAIEMENT" />
                <x-field prefix="avance" label="N° de transaction (facultatif)" name="reference" placeholder="Mobile Money, virement..." />
                <div class="sm:col-span-2"><x-field prefix="avance" label="Commentaire" name="commentaire" type="textarea" placeholder="Facultatif" /></div>
            </div>
            <p class="text-xs text-gray-500">Un numéro de référence (AV-…) est attribué automatiquement. L'avance sera déduite du salaire du mois de sa date.</p>
        </x-modal>

        {{-- Nouveau crédit --}}
        <x-modal id="modal-credit" title="Nouveau crédit" size="max-w-xl" :action="route('paie.credits.store')" submit="Accorder le crédit">
            <input type="hidden" name="_form" value="credit">
            <input type="hidden" name="retour_mois" value="{{ $mois }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="credit-employe" class="mb-1.5 block text-sm font-medium text-gray-700">Employé *</label>
                    <select id="credit-employe" name="employe_id" required class="{{ $select }}">
                        <option value="">— Choisir —</option>
                        @foreach ($employes as $e)
                            <option value="{{ $e->id }}" @selected(old('_form') === 'credit' && (int) old('employe_id') === $e->id)>{{ $e->nom }} (salaire {{ Format::usd($e->salaire) }} $)</option>
                        @endforeach
                    </select>
                </div>
                <x-field prefix="credit" label="Montant accordé (USD) *" name="montant" placeholder="0,00" required data-credit-calc />
                <x-field prefix="credit" label="Taux d'intérêt (%)" name="taux" value="0" data-credit-calc />
                <x-field prefix="credit" label="Durée (mois) *" name="duree_mois" type="number" value="6" min="1" max="60" required data-credit-calc />
                <x-field prefix="credit" label="Date d'octroi *" name="date_octroi" type="date" :value="now()->format('Y-m-d')" required />
                <x-field prefix="credit" label="Premier mois de retenue *" name="mois_debut" type="month" :value="now()->addMonth()->format('Y-m')" required />
                <div class="sm:col-span-2"><x-field prefix="credit" label="Commentaire" name="commentaire" type="textarea" placeholder="Motif du crédit (facultatif)" /></div>
            </div>
            <p id="credit-apercu" class="rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-800">Saisissez le montant, le taux et la durée pour voir la mensualité.</p>
        </x-modal>

        {{-- Paiement d'un bulletin --}}
        @foreach ($bulletins->reject->estPaye() as $bulletin)
            <x-modal :id="'modal-payer-'.$bulletin->id" :title="'Payer le salaire : '.$bulletin->employe->nom" size="max-w-xl" :action="route('paie.bulletins.payer', $bulletin)" method="PUT" submit="Confirmer le paiement">
                <input type="hidden" name="_form" value="payer{{ $bulletin->id }}">
                <dl class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50 px-4 py-3 text-sm">
                    <dt class="text-gray-500">N° de référence</dt><dd class="text-right font-mono font-semibold">{{ $bulletin->numero }}</dd>
                    <dt class="text-gray-500">Salaire de base</dt><dd class="text-right tabular-nums">{{ Format::usd($bulletin->salaire_base) }}</dd>
                    <dt class="text-gray-500">− Avances</dt><dd class="text-right tabular-nums">{{ Format::usd($bulletin->total_avances) }}</dd>
                    <dt class="text-gray-500">− Retenues crédit</dt><dd class="text-right tabular-nums">{{ Format::usd($bulletin->total_credits) }}</dd>
                    <dt class="font-semibold">Net (hors primes)</dt><dd class="text-right font-semibold tabular-nums">{{ Format::usd((float) $bulletin->salaire_base - (float) $bulletin->total_avances - (float) $bulletin->total_credits) }} $</dd>
                </dl>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field :prefix="'payer'.$bulletin->id" label="Primes (USD)" name="primes" :value="Format::usd($bulletin->primes)" />
                    <x-field :prefix="'payer'.$bulletin->id" label="Date de paiement *" name="date_paiement" type="date" :value="now()->format('Y-m-d')" required />
                    <x-field :prefix="'payer'.$bulletin->id" label="Mode de paiement" name="mode_paiement" :options="Avance::MODES_PAIEMENT" />
                    <x-field :prefix="'payer'.$bulletin->id" label="N° de transaction (facultatif)" name="reference" placeholder="Mobile Money, virement..." />
                </div>
            </x-modal>
        @endforeach

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Aperçu de la mensualité du crédit (même calcul que Credit::calculer)
                const champ = (nom) => document.getElementById('credit-' + nom);
                const apercu = document.getElementById('credit-apercu');
                const nombre = (v) => parseFloat(String(v).replace(/\s/g, '').replace(',', '.')) || 0;
                const usd = (n) => n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const calculer = () => {
                    const montant = nombre(champ('montant').value), taux = nombre(champ('taux').value), duree = parseInt(champ('duree_mois').value) || 0;
                    if (montant <= 0 || duree <= 0) return;
                    const total = Math.round(montant * (1 + taux / 100) * 100) / 100;
                    apercu.textContent = `Total à rembourser : ${usd(total)} $ · Mensualité : ${usd(Math.round(total / duree * 100) / 100)} $ pendant ${duree} mois`;
                };
                document.querySelectorAll('[data-credit-calc]').forEach((el) => el.addEventListener('input', calculer));
                calculer();

                // Rouvre la bonne fenêtre si la validation a échoué
                @if ($errors->any() && old('_form'))
                    const form = @js(old('_form'));
                    const id = form === 'payer-tout' ? 'modal-payer-tout' : (form.startsWith('payer') ? 'modal-payer-' + form.slice(5) : 'modal-' + form);
                    document.querySelector(`[data-modal-toggle="${id}"]`)?.click();
                @endif
            });
        </script>
    </x-slot:modals>
</x-layouts.app>

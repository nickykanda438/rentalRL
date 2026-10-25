@use('App\Models\Avance')
@use('App\Models\Bulletin')
@use('App\Support\Format')
@php
    // Paie rapide : mois proposé = mois en cours, ou le suivant s'il est déjà payé
    $estActif = $employe->statut === 'Actif';
    $moisSalaire = now()->format('Y-m');
    $apercu = Bulletin::apercu($employe, $moisSalaire);
    if ($apercu['deja_paye']) {
        $moisSalaire = now()->addMonthNoOverflow()->format('Y-m');
        $apercu = Bulletin::apercu($employe, $moisSalaire);
    }
    $avanceMois = (float) $employe->avances()->where('mois', now()->format('Y-m'))->sum('montant');
    $select = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600';

    // Affiche « — » quand une information n'est pas renseignée
    $v = fn ($valeur) => filled($valeur) ? $valeur : '—';
    $date = fn ($d) => $d ? $d->format('d/m/Y') : '—';

    $sections = [
        'Informations professionnelles' => [
            'Poste' => $v($employe->poste),
            'Rôle' => $v($employe->role),
            'Salaire mensuel' => $employe->salaireFormate().' USD',
            "Date d'embauche" => $date($employe->date_embauche),
            'Ancienneté' => $employe->date_embauche ? $employe->date_embauche->copy()->locale('fr')->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) : '—',
            'Lieu de travail' => $v($employe->lieu_travail),
        ],
        'Informations personnelles' => [
            'Genre' => $v($employe->genre),
            'Date de naissance' => $date($employe->date_naissance).($employe->date_naissance ? ' ('.$employe->date_naissance->age.' ans)' : ''),
            'Lieu de naissance' => $v($employe->lieu_naissance),
            'État civil' => $v($employe->etat_civil),
            "Niveau d'études" => $v($employe->niveau_etudes),
            "Domaine d'étude" => $v($employe->domaine),
        ],
        'Origine et adresse' => [
            'Province' => $v($employe->province),
            'District' => $v($employe->district),
            'Territoire' => $v($employe->territoire),
            'Adresse physique' => $v($employe->adresse),
        ],
        'Personne de référence' => [
            'Nom' => $v($employe->personne_reference),
            'Contact' => $v($employe->contact_reference),
        ],
    ];
@endphp
<x-layouts.app :title="'Fiche : '.$employe->nom">
    <x-page-header :title="$employe->nom" :subtitle="'Fiche employé n° '.str_pad($employe->id, 4, '0', STR_PAD_LEFT).' · enregistrée le '.$employe->created_at->format('d/m/Y')">
        <x-button variant="light" icon="arrow" :href="route('employes.index')" class="print:hidden">Retour à la liste</x-button>
        <x-button variant="light" icon="download" onclick="window.print()" class="print:hidden">Imprimer</x-button>
        <x-button variant="light" icon="edit" data-modal-target="modal-employe-{{ $employe->id }}" data-modal-toggle="modal-employe-{{ $employe->id }}" class="print:hidden">Modifier</x-button>
        @if ($estActif && ! $apercu['deja_paye'])
            <x-button icon="money" data-modal-target="modal-salaire" data-modal-toggle="modal-salaire" class="print:hidden">Payer le salaire</x-button>
        @endif
    </x-page-header>

    <x-flash />

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Carte d'identité --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-xs">
            @if ($employe->photo)
                <img src="{{ asset('storage/'.$employe->photo) }}" alt="Photo de {{ $employe->nom }}" class="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-primary-50">
            @else
                @php $initiales = collect(explode(' ', $employe->nom))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''); @endphp
                <span class="mx-auto inline-flex h-32 w-32 items-center justify-center rounded-full bg-primary-50 text-4xl font-bold text-primary-700">{{ mb_strtoupper($initiales) }}</span>
            @endif
            <h2 class="mt-4 text-lg font-bold text-gray-900">{{ $employe->nom }}</h2>
            <p class="text-sm text-gray-500">{{ $employe->poste }}</p>
            <div class="mt-3"><x-badge :color="$employe->couleurStatut()">{{ $employe->statut }}</x-badge></div>

            <dl class="mt-6 space-y-3 border-t border-gray-100 pt-5 text-left text-sm">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Téléphone</dt><dd class="font-medium tabular-nums text-gray-900">{{ $v($employe->telephone) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Email</dt><dd class="truncate font-medium text-gray-900">{{ $v($employe->email) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Rôle</dt><dd class="font-medium text-gray-900">{{ $employe->role }}</dd></div>
            </dl>
        </section>

        {{-- Détails --}}
        <div class="space-y-6 lg:col-span-2">
            @foreach ($sections as $titre => $lignes)
                <section class="rounded-xl border border-gray-200 bg-white shadow-xs">
                    <h3 class="border-b border-gray-100 px-6 py-4 text-base font-bold text-gray-900">{{ $titre }}</h3>
                    <dl class="grid gap-x-6 gap-y-4 px-6 py-5 text-sm sm:grid-cols-2">
                        @foreach ($lignes as $libelle => $valeur)
                            <div>
                                <dt class="text-gray-500">{{ $libelle }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900">{{ $valeur }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            {{-- Paie --}}
            @php
                $derniersBulletins = $employe->bulletins()->orderByDesc('mois')->take(6)->get();
                $creditsActifs = $employe->credits()->with('remboursements.bulletin')->where('statut', 'Actif')->get();
            @endphp
            <section class="rounded-xl border border-gray-200 bg-white shadow-xs">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-bold text-gray-900">Paie</h3>
                    <a href="{{ route('paie.index', ['q' => $employe->nom]) }}" class="text-sm font-semibold text-primary-700 hover:underline print:hidden">Voir dans la paie →</a>
                </div>
                <div class="space-y-4 px-6 py-5 text-sm">
                    @if ($estActif)
                        <div class="grid gap-3 sm:grid-cols-3 print:hidden">
                            <button type="button" data-modal-target="modal-salaire" data-modal-toggle="modal-salaire" @disabled($apercu['deja_paye'])
                                class="rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-left hover:bg-primary-100 disabled:cursor-not-allowed disabled:opacity-60">
                                <span class="flex items-center gap-2 font-semibold text-primary-800"><x-icon name="money" class="h-4 w-4" />Payer le salaire</span>
                                <span class="mt-1 block text-xs text-primary-700">{{ Format::mois($moisSalaire) }} · net {{ Format::usd($apercu['net']) }} $</span>
                            </button>
                            <button type="button" data-modal-target="modal-avance" data-modal-toggle="modal-avance"
                                class="rounded-xl border border-gray-200 px-4 py-3 text-left hover:bg-gray-50">
                                <span class="flex items-center gap-2 font-semibold text-gray-900"><x-icon name="wallet" class="h-4 w-4" />Donner une avance</span>
                                <span class="mt-1 block text-xs text-gray-500">Déjà avancé ce mois : {{ Format::usd($avanceMois) }} $</span>
                            </button>
                            <button type="button" data-modal-target="modal-credit" data-modal-toggle="modal-credit"
                                class="rounded-xl border border-gray-200 px-4 py-3 text-left hover:bg-gray-50">
                                <span class="flex items-center gap-2 font-semibold text-gray-900"><x-icon name="credit" class="h-4 w-4" />Accorder un crédit</span>
                                <span class="mt-1 block text-xs text-gray-500">Remboursé par mensualités sur le salaire</span>
                            </button>
                        </div>
                    @else
                        <p class="rounded-lg bg-gray-50 px-4 py-2.5 text-gray-600">Employé {{ mb_strtolower($employe->statut) }} : les paiements sont désactivés.</p>
                    @endif
                    @foreach ($creditsActifs as $credit)
                        <p class="rounded-lg bg-teal-50 px-4 py-2.5 text-teal-800">
                            Crédit en cours : reste <strong>{{ \App\Support\Format::usd($credit->reste()) }} $</strong>
                            sur {{ \App\Support\Format::usd($credit->montant_total) }} $ (mensualité {{ \App\Support\Format::usd($credit->mensualite) }} $).
                        </p>
                    @endforeach
                    @if ($derniersBulletins->isEmpty())
                        <p class="text-gray-500">Aucun bulletin de paie pour l'instant.</p>
                    @else
                        <table class="w-full text-left">
                            <thead class="text-xs text-gray-500"><tr><th class="py-1.5">N° référence</th><th class="py-1.5">Mois</th><th class="py-1.5 text-right">Net à payer (USD)</th><th class="py-1.5">Statut</th><th class="py-1.5 print:hidden"></th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($derniersBulletins as $b)
                                    <tr>
                                        <td class="py-2 font-mono text-xs font-semibold">{{ $b->numero }}</td>
                                        <td class="py-2">{{ \App\Support\Format::mois($b->mois) }}</td>
                                        <td class="py-2 text-right font-medium tabular-nums">{{ \App\Support\Format::usd($b->net_a_payer) }}</td>
                                        <td class="py-2 pl-4"><x-badge :color="$b->couleurStatut()">{{ $b->statut }}</x-badge></td>
                                        <td class="py-2 text-right print:hidden"><a href="{{ route('paie.bulletins.imprimer', ['bulletin' => $b->id]) }}" target="_blank" class="text-primary-700 hover:underline">Bulletin</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </section>

            <div class="flex justify-end print:hidden">
                <form method="POST" action="{{ route('employes.destroy', $employe) }}" onsubmit="return confirm(@js('Supprimer définitivement la fiche de '.$employe->nom.' ?'))">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                        <x-icon name="trash" class="h-4 w-4" /> Supprimer cet employé
                    </button>
                </form>
            </div>
        </div>
    </div>

    <x-slot:modals>
        <x-modal :id="'modal-employe-'.$employe->id" :title="'Modifier : '.$employe->nom" size="max-w-3xl" :action="route('employes.update', $employe)" method="PUT" submit="Mettre à jour">
            @include('employes._form', ['e' => $employe, 'p' => 'emp'.$employe->id])
        </x-modal>

        @if ($estActif)
            {{-- Payer le salaire --}}
            <x-modal id="modal-salaire" :title="'Payer le salaire de '.$employe->nom" size="max-w-xl" :action="route('paie.employes.payer-salaire', $employe)" :submit="'Payer '.Format::usd($apercu['net']).' $'">
                <input type="hidden" name="_form" value="salaire">
                <dl class="grid grid-cols-2 gap-2 rounded-xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm">
                    <dt class="text-gray-600">Salaire de base</dt><dd class="text-right tabular-nums">{{ Format::usd($apercu['salaire']) }} $</dd>
                    <dt class="text-gray-600">− Avances du mois</dt><dd class="text-right tabular-nums">{{ Format::usd($apercu['avances']) }} $</dd>
                    <dt class="text-gray-600">− Mensualités de crédit</dt><dd class="text-right tabular-nums">{{ Format::usd($apercu['credits']) }} $</dd>
                    <dt class="font-bold text-primary-800">Net à payer</dt><dd class="text-right text-lg font-bold tabular-nums text-primary-800">{{ Format::usd($apercu['net']) }} $</dd>
                </dl>
                <p class="text-xs text-gray-500">Calcul pour {{ Format::mois($moisSalaire) }}, hors primes. Un numéro de référence (SAL-…) est attribué automatiquement.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field prefix="salaire" label="Mois payé *" name="mois" type="month" :value="$moisSalaire" required />
                    <x-field prefix="salaire" label="Primes (USD)" name="primes" :value="Format::usd($apercu['primes'])" />
                    <x-field prefix="salaire" label="Date de paiement *" name="date_paiement" type="date" :value="now()->format('Y-m-d')" required />
                    <x-field prefix="salaire" label="Mode de paiement" name="mode_paiement" :options="Avance::MODES_PAIEMENT" />
                    <x-field prefix="salaire" label="N° de transaction (facultatif)" name="reference" placeholder="Mobile Money, virement..." />
                </div>
            </x-modal>

            {{-- Donner une avance --}}
            <x-modal id="modal-avance" :title="'Avance sur salaire : '.$employe->nom" size="max-w-xl" :action="route('paie.avances.store')" submit="Enregistrer l'avance">
                <input type="hidden" name="_form" value="avance">
                <input type="hidden" name="employe_id" value="{{ $employe->id }}">
                <input type="hidden" name="retour" value="employe">
                <p class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">
                    Salaire : <strong>{{ $employe->salaireFormate() }} $</strong> · déjà avancé ce mois : <strong>{{ Format::usd($avanceMois) }} $</strong>
                    · maximum possible : <strong>{{ Format::usd(max(0, (float) $employe->salaire - $avanceMois)) }} $</strong>
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field prefix="avance" label="Montant (USD) *" name="montant" placeholder="0,00" required />
                    <x-field prefix="avance" label="Date de l'avance *" name="date_avance" type="date" :value="now()->format('Y-m-d')" required />
                    <x-field prefix="avance" label="Mode de paiement" name="mode_paiement" :options="Avance::MODES_PAIEMENT" />
                    <x-field prefix="avance" label="N° de transaction (facultatif)" name="reference" placeholder="Mobile Money, virement..." />
                    <div class="sm:col-span-2"><x-field prefix="avance" label="Commentaire" name="commentaire" type="textarea" placeholder="Facultatif" /></div>
                </div>
            </x-modal>

            {{-- Accorder un crédit --}}
            <x-modal id="modal-credit" :title="'Crédit : '.$employe->nom" size="max-w-xl" :action="route('paie.credits.store')" submit="Accorder le crédit">
                <input type="hidden" name="_form" value="credit">
                <input type="hidden" name="employe_id" value="{{ $employe->id }}">
                <input type="hidden" name="retour" value="employe">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field prefix="credit" label="Montant accordé (USD) *" name="montant" placeholder="0,00" required data-credit-calc />
                    <x-field prefix="credit" label="Taux d'intérêt (%)" name="taux" value="0" data-credit-calc />
                    <x-field prefix="credit" label="Durée (mois) *" name="duree_mois" type="number" value="6" min="1" max="60" required data-credit-calc />
                    <x-field prefix="credit" label="Date d'octroi *" name="date_octroi" type="date" :value="now()->format('Y-m-d')" required />
                    <x-field prefix="credit" label="Premier mois de retenue *" name="mois_debut" type="month" :value="now()->addMonthNoOverflow()->format('Y-m')" required />
                    <div class="sm:col-span-2"><x-field prefix="credit" label="Commentaire" name="commentaire" type="textarea" placeholder="Motif du crédit (facultatif)" /></div>
                </div>
                <p id="credit-apercu" class="rounded-lg bg-primary-50 px-4 py-3 text-sm text-primary-800">Saisissez le montant, le taux et la durée pour voir la mensualité.</p>
            </x-modal>
        @endif

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Aperçu de la mensualité du crédit
                const champ = (nom) => document.getElementById('credit-' + nom);
                const apercu = document.getElementById('credit-apercu');
                const nombre = (v) => parseFloat(String(v).replace(/\s/g, '').replace(',', '.')) || 0;
                const usd = (n) => n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const calculer = () => {
                    const montant = nombre(champ('montant')?.value), taux = nombre(champ('taux')?.value), duree = parseInt(champ('duree_mois')?.value) || 0;
                    if (!apercu || montant <= 0 || duree <= 0) return;
                    const total = Math.round(montant * (1 + taux / 100) * 100) / 100;
                    apercu.textContent = `Total à rembourser : ${usd(total)} $ · Mensualité : ${usd(Math.round(total / duree * 100) / 100)} $ pendant ${duree} mois`;
                };
                document.querySelectorAll('[data-credit-calc]').forEach((el) => el.addEventListener('input', calculer));

                // Ouvre une fenêtre : après une erreur de saisie, ou via ?ouvrir=salaire|avance|credit
                const correspondance = { salaire: 'modal-salaire', avance: 'modal-avance', credit: 'modal-credit' };
                @if ($errors->any())
                    const cible = correspondance[@js(old('_form'))] ?? 'modal-employe-{{ $employe->id }}';
                @else
                    const cible = correspondance[@js(request('ouvrir'))] ?? null;
                @endif
                if (cible) document.querySelector(`[data-modal-toggle="${cible}"]:not([disabled])`)?.click();
            });
        </script>
    </x-slot:modals>
</x-layouts.app>

<?php

namespace App\Http\Controllers;

use App\Models\AutreRevenu;
use App\Models\Avance;
use App\Models\Bulletin;
use App\Models\CategorieDepense;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Depense;
use App\Models\Employe;
use App\Models\Location;
use App\Models\Vehicule;
use App\Support\Caisse;
use App\Support\Format;
use App\Support\Periode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapports sur une période. Chaque rapport renvoie la même structure :
 * ['cartes' => [[libellé, valeur, type, ton]], 'tableaux' => [tableau…]]
 * qui sert à l'affichage, à l'export Excel (CSV) et à l'impression.
 * Les totaux sont calculés en base par des requêtes groupées (SUM … GROUP BY), sans tout charger en mémoire.
 */
class RapportController extends Controller
{
    public const RAPPORTS = [
        'synthese' => ['Synthèse financière', 'chart', 'Entrées, sorties, solde et évolution mensuelle'],
        'locations' => ['Locations', 'calc', 'Par véhicule, par chauffeur, meilleurs clients'],
        'depenses' => ['Dépenses', 'receipt', 'Par catégorie, véhicule, source et bénéficiaire'],
        'revenus' => ['Autres revenus', 'trend', 'Reçu, utilisé et reste de chaque revenu'],
        'paie' => ['Paie', 'money', 'Salaires, primes, avances et crédits par employé'],
        'flotte' => ['Rentabilité flotte', 'car', 'Occupation, chiffre d\'affaires et marge par véhicule'],
    ];

    /** Nombre maximum de lignes dans les listes détaillées. */
    private const LIMITE_LISTE = 500;

    public function index(Request $request): View
    {
        [$type, $periode] = $this->contexte($request);
        $rapport = $this->{$type}($periode);

        return view('rapports.index', compact('type', 'periode', 'rapport'));
    }

    /**
     * Export CSV (s'ouvre dans Excel) : toutes les cartes et tous les tableaux du rapport.
     */
    public function exporter(Request $request): StreamedResponse
    {
        [$type, $periode] = $this->contexte($request);
        $rapport = $this->{$type}($periode);
        $nom = 'rapport-'.$type.'-'.$periode->du->format('Ymd').'-'.$periode->au->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rapport, $type, $periode) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Rental — '.self::RAPPORTS[$type][0], ucfirst($periode->libelle())], ';');
            fputcsv($f, ['Généré le '.now()->format('d/m/Y H:i')], ';');
            fputcsv($f, [], ';');

            foreach ($rapport['cartes'] as [$libelle, $valeur, $format]) {
                fputcsv($f, [$libelle, self::valeurTexte($valeur, $format)], ';');
            }

            foreach ($rapport['tableaux'] as $t) {
                fputcsv($f, [], ';');
                fputcsv($f, [mb_strtoupper($t['titre'])], ';');
                fputcsv($f, array_column($t['colonnes'], 0), ';');
                foreach ($t['lignes'] as $ligne) {
                    fputcsv($f, self::ligneTexte($ligne, $t['colonnes']), ';');
                }
                if ($t['total']) {
                    fputcsv($f, self::ligneTexte($t['total'], $t['colonnes']), ';');
                }
            }
            fclose($f);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // =====================================================================
    // Rapports
    // =====================================================================

    /** Synthèse : entrées, sorties, solde, par source et évolution mensuelle. */
    private function synthese(Periode $p): array
    {
        $b = $p->bornes();

        $locations = Location::where('statut', '!=', 'Annulé')->whereBetween('date_depart', $b)
            ->selectRaw('count(*) as n, coalesce(sum(total_usd), 0) as total, coalesce(sum(commission), 0) as commission')->first();
        $autres = AutreRevenu::whereBetween('date_revenu', $b)->selectRaw('count(*) as n, coalesce(sum(montant_usd), 0) as total')->first();

        $depensesParCategorie = Depense::whereBetween('date_depense', $b)
            ->selectRaw('categorie_id, count(*) as n, sum(montant_usd) as total')->groupBy('categorie_id')->get();
        $categories = CategorieDepense::pluck('nom', 'id');

        $avances = Avance::whereBetween('date_avance', $b)->selectRaw('count(*) as n, coalesce(sum(montant), 0) as total')->first();
        $salaires = Bulletin::where('statut', 'Payé')->whereBetween('date_paiement', $b)->selectRaw('count(*) as n, coalesce(sum(net_a_payer), 0) as total')->first();
        $credits = Credit::whereBetween('date_octroi', $b)->selectRaw('count(*) as n, coalesce(sum(montant), 0) as total')->first();

        $entrees = (float) $locations->total + (float) $autres->total;
        $totalDepenses = (float) $depensesParCategorie->sum('total');
        $sorties = $totalDepenses + (float) $avances->total + (float) $salaires->total + (float) $credits->total;
        $part = fn ($v, $total) => $total > 0 ? $v / $total * 100 : 0;

        // Sorties par poste
        $lignesSorties = $depensesParCategorie->sortByDesc('total')
            ->map(fn ($d) => ['Dépenses · '.($categories[$d->categorie_id] ?? '?'), (int) $d->n, (float) $d->total, $part($d->total, $sorties)])->values()->all();
        foreach ([['Avances sur salaire', $avances], ['Salaires payés (net)', $salaires], ['Crédits accordés au personnel', $credits]] as [$nom, $r]) {
            if ((float) $r->total > 0) {
                $lignesSorties[] = [$nom, (int) $r->n, (float) $r->total, $part($r->total, $sorties)];
            }
        }

        // Par source d'argent (sorties de la période + disponible aujourd'hui)
        $sortiesSource = fn (string $s) => (float) Depense::where('source', $s)->whereBetween('date_depense', $b)->sum('montant_usd')
            + (float) Avance::where('source', $s)->whereBetween('date_avance', $b)->sum('montant')
            + (float) Bulletin::where('source', $s)->where('statut', 'Payé')->whereBetween('date_paiement', $b)->sum('net_a_payer')
            + (float) Credit::where('source', $s)->whereBetween('date_octroi', $b)->sum('montant');
        $sortiesLocation = $sortiesSource('location');
        $sortiesRevenu = $sortiesSource('revenu');

        // Évolution mensuelle : une requête groupée par table (SUBSTR fonctionne sous MySQL et SQLite)
        $parMois = fn (Builder $q, string $colDate, string $colMontant) => $q->whereBetween($colDate, $b)
            ->selectRaw("substr({$colDate}, 1, 7) as m, sum({$colMontant}) as total")->groupBy('m')->pluck('total', 'm');
        $mLoc = $parMois(Location::where('statut', '!=', 'Annulé'), 'date_depart', 'total_usd');
        $mAut = $parMois(AutreRevenu::query(), 'date_revenu', 'montant_usd');
        $mDep = $parMois(Depense::query(), 'date_depense', 'montant_usd');
        $mAv = $parMois(Avance::query(), 'date_avance', 'montant');
        $mSal = $parMois(Bulletin::where('statut', 'Payé'), 'date_paiement', 'net_a_payer');
        $mCre = $parMois(Credit::query(), 'date_octroi', 'montant');

        $evolution = collect($p->mois())->map(function (string $m) use ($mLoc, $mAut, $mDep, $mAv, $mSal, $mCre) {
            $paie = (float) ($mAv[$m] ?? 0) + (float) ($mSal[$m] ?? 0) + (float) ($mCre[$m] ?? 0);
            $entrees = (float) ($mLoc[$m] ?? 0) + (float) ($mAut[$m] ?? 0);

            return [Format::mois($m), (float) ($mLoc[$m] ?? 0), (float) ($mAut[$m] ?? 0), (float) ($mDep[$m] ?? 0), $paie, $entrees - (float) ($mDep[$m] ?? 0) - $paie];
        })->reject(fn ($l) => $l[1] == 0 && $l[2] == 0 && $l[3] == 0 && $l[4] == 0)->values(); // mois sans aucune activité masqués

        return [
            'cartes' => [
                ['Entrées', $entrees, 'usd', 'ok'],
                ['Sorties', $sorties, 'usd', 'default'],
                ['Solde de la période', $entrees - $sorties, 'usd', $entrees - $sorties < 0 ? 'warn' : 'ok'],
                ['Commissions sur locations', (float) $locations->commission, 'usd', 'default'],
            ],
            'tableaux' => [
                self::tableau('Entrées', [['Source', 'texte'], ['Opérations', 'nombre'], ['Montant (USD)', 'usd'], ['Part', 'pct']], [
                    ['Recettes de location', (int) $locations->n, (float) $locations->total, $part($locations->total, $entrees)],
                    ['Autres revenus', (int) $autres->n, (float) $autres->total, $part($autres->total, $entrees)],
                ], ['Total', (int) $locations->n + (int) $autres->n, $entrees, 100], barre: 2),
                self::tableau('Sorties', [['Poste', 'texte'], ['Opérations', 'nombre'], ['Montant (USD)', 'usd'], ['Part', 'pct']],
                    $lignesSorties, ['Total', array_sum(array_column($lignesSorties, 1)), $sorties, 100], barre: 2),
                self::tableau("Par source d'argent", [['Source', 'texte'], ['Entrées de la période', 'usd'], ['Sorties de la période', 'usd'], ['Solde de la période', 'usd'], ["Disponible aujourd'hui", 'usd']], [
                    ['Recettes de location', (float) $locations->total, $sortiesLocation, (float) $locations->total - $sortiesLocation, Caisse::locations()['solde']],
                    ['Autres revenus', (float) $autres->total, $sortiesRevenu, (float) $autres->total - $sortiesRevenu, Caisse::autresRevenus()['solde']],
                ]),
                self::tableau('Évolution mensuelle', [['Mois', 'texte'], ['Locations', 'usd'], ['Autres revenus', 'usd'], ['Dépenses', 'usd'], ['Paie', 'usd'], ['Solde', 'usd']],
                    $evolution->all(), ['Total', $evolution->sum(1), $evolution->sum(2), $evolution->sum(3), $evolution->sum(4), $evolution->sum(5)], barre: 1),
            ],
        ];
    }

    /** Locations : par véhicule, par chauffeur, meilleurs clients, liste des contrats. */
    private function locations(Periode $p): array
    {
        $base = fn () => Location::where('statut', '!=', 'Annulé')->whereBetween('date_depart', $p->bornes());
        $agg = 'count(*) as n, sum(nombre_jours) as jours, sum(total_usd) as ca, sum(commission) as commission';

        $totaux = $base()->selectRaw($agg.', sum(total_cdf) as cdf')->first();
        $annulees = Location::where('statut', 'Annulé')->whereBetween('date_depart', $p->bornes())->count();
        $ca = (float) $totaux->ca;
        $part = fn ($v) => $ca > 0 ? $v / $ca * 100 : 0;

        // Par véhicule
        $parVehicule = $base()->selectRaw('vehicule_id, '.$agg)->groupBy('vehicule_id')->orderByDesc('ca')->get();
        $vehicules = Vehicule::whereIn('id', $parVehicule->pluck('vehicule_id')->filter())->get()->keyBy('id');
        $lignesV = $parVehicule->map(fn ($r) => [$vehicules->get($r->vehicule_id)?->libelle() ?? 'Sans véhicule', (int) $r->n, (int) $r->jours, (float) $r->ca, (float) $r->commission, $part($r->ca)]);
        $liensV = $parVehicule->map(fn ($r) => $r->vehicule_id ? route('vehicules.show', $r->vehicule_id) : null);

        // Par chauffeur
        $parChauffeur = $base()->selectRaw('chauffeur_id, '.$agg)->groupBy('chauffeur_id')->orderByDesc('ca')->get();
        $chauffeurs = Employe::whereIn('id', $parChauffeur->pluck('chauffeur_id')->filter())->pluck('nom', 'id');
        $lignesC = $parChauffeur->map(fn ($r) => [$chauffeurs[$r->chauffeur_id] ?? 'Sans chauffeur', (int) $r->n, (int) $r->jours, (float) $r->ca, $part($r->ca)]);
        $liensC = $parChauffeur->map(fn ($r) => $r->chauffeur_id ? route('employes.show', $r->chauffeur_id) : null);

        // Meilleurs clients
        $parClient = $base()->selectRaw('client_id, '.$agg)->groupBy('client_id')->orderByDesc('ca')->limit(10)->get();
        $clients = Client::whereIn('id', $parClient->pluck('client_id'))->get()->keyBy('id');
        $lignesCl = $parClient->map(fn ($r) => [$clients->get($r->client_id)?->nom ?? '?', $clients->get($r->client_id)?->telephone, (int) $r->n, (int) $r->jours, (float) $r->ca]);

        // Liste des contrats
        $liste = Location::with(['client:id,nom', 'vehicule:id,marque,modele,plaque', 'chauffeur:id,nom'])
            ->whereBetween('date_depart', $p->bornes())->orderBy('date_depart')->limit(self::LIMITE_LISTE)->get();

        return [
            'cartes' => [
                ['Locations', (int) $totaux->n, 'nombre', 'default'],
                ['Jours loués', (int) $totaux->jours, 'nombre', 'default'],
                ['Chiffre d\'affaires net (USD)', $ca, 'usd', 'ok'],
                ['Commissions (USD)', (float) $totaux->commission, 'usd', 'default'],
                ['Total CDF', (float) $totaux->cdf, 'cdf', 'default'],
                ['Revenu moyen / location', $totaux->n ? $ca / $totaux->n : 0, 'usd', 'default'],
                ['Locations annulées', $annulees, 'nombre', $annulees ? 'accent' : 'default'],
            ],
            'tableaux' => [
                self::tableau('Par véhicule', [['Véhicule', 'texte'], ['Locations', 'nombre'], ['Jours', 'nombre'], ['CA net (USD)', 'usd'], ['Commission', 'usd'], ['Part', 'pct']],
                    $lignesV->all(), ['Total', (int) $totaux->n, (int) $totaux->jours, $ca, (float) $totaux->commission, 100], barre: 3, liens: $liensV->all()),
                self::tableau('Par chauffeur', [['Chauffeur', 'texte'], ['Locations', 'nombre'], ['Jours', 'nombre'], ['CA net (USD)', 'usd'], ['Part', 'pct']],
                    $lignesC->all(), null, barre: 3, liens: $liensC->all()),
                self::tableau('Meilleurs clients (10)', [['Client', 'texte'], ['Téléphone', 'texte'], ['Locations', 'nombre'], ['Jours', 'nombre'], ['CA net (USD)', 'usd']],
                    $lignesCl->all(), null, barre: 4),
                self::tableau('Liste des contrats', [['N° contrat', 'texte'], ['Départ', 'date'], ['Client', 'texte'], ['Véhicule', 'texte'], ['Chauffeur', 'texte'], ['Jours', 'nombre'], ['Total (USD)', 'usd'], ['Statut', 'texte']],
                    $liste->map(fn ($l) => [$l->numero_contrat, $l->date_depart, $l->client->nom, $l->vehicule?->libelle() ?? '—', $l->chauffeur?->nom ?? '—', $l->nombre_jours, (float) $l->total_usd, $l->statutAffiche()])->all(),
                    null, liens: $liste->map(fn ($l) => route('locations.show', $l))->all(), note: $liste->count() === self::LIMITE_LISTE ? 'Liste limitée à '.self::LIMITE_LISTE.' lignes : utilisez l\'export ou réduisez la période.' : null),
            ],
        ];
    }

    /** Dépenses : par catégorie, par véhicule, par source, par bénéficiaire, liste. */
    private function depenses(Periode $p): array
    {
        $base = fn () => Depense::whereBetween('date_depense', $p->bornes());
        $totaux = $base()->selectRaw('count(*) as n, coalesce(sum(montant_usd), 0) as usd, coalesce(sum(montant_cdf), 0) as cdf')->first();
        $total = (float) $totaux->usd;
        $part = fn ($v) => $total > 0 ? $v / $total * 100 : 0;

        $parCategorie = $base()->selectRaw('categorie_id, count(*) as n, sum(montant_usd) as total')->groupBy('categorie_id')->orderByDesc('total')->get();
        $categories = CategorieDepense::pluck('nom', 'id');

        $parVehicule = $base()->whereNotNull('vehicule_id')->selectRaw('vehicule_id, count(*) as n, sum(montant_usd) as total')->groupBy('vehicule_id')->orderByDesc('total')->get();
        $vehicules = Vehicule::whereIn('id', $parVehicule->pluck('vehicule_id'))->get()->keyBy('id');

        $parSource = $base()->selectRaw('source, autre_revenu_id, count(*) as n, sum(montant_usd) as total')->groupBy('source', 'autre_revenu_id')->orderByDesc('total')->get();
        $revenus = AutreRevenu::whereIn('id', $parSource->pluck('autre_revenu_id')->filter())->get()->keyBy('id');

        $parBeneficiaire = $base()->whereNotNull('beneficiaire')->selectRaw('beneficiaire, count(*) as n, sum(montant_usd) as total')
            ->groupBy('beneficiaire')->orderByDesc('total')->limit(10)->get();

        $liste = Depense::with(['categorie:id,nom', 'vehicule:id,plaque', 'employe:id,nom', 'autreRevenu:id,numero,source'])
            ->whereBetween('date_depense', $p->bornes())->orderBy('date_depense')->limit(self::LIMITE_LISTE)->get();
        $surLocations = (float) $parSource->where('source', 'location')->sum('total');

        return [
            'cartes' => [
                ['Total dépenses (USD)', $total, 'usd', 'default'],
                ['Total (CDF)', (float) $totaux->cdf, 'cdf', 'default'],
                ['Nombre de dépenses', (int) $totaux->n, 'nombre', 'default'],
                ['Payé avec les locations', $surLocations, 'usd', 'default'],
                ['Payé avec autres revenus', $total - $surLocations, 'usd', 'accent'],
            ],
            'tableaux' => [
                self::tableau('Par catégorie', [['Catégorie', 'texte'], ['Dépenses', 'nombre'], ['Montant (USD)', 'usd'], ['Part', 'pct']],
                    $parCategorie->map(fn ($r) => [$categories[$r->categorie_id] ?? '?', (int) $r->n, (float) $r->total, $part($r->total)])->all(),
                    ['Total', (int) $totaux->n, $total, 100], barre: 2),
                self::tableau("Par source d'argent", [['Source', 'texte'], ['Dépenses', 'nombre'], ['Montant (USD)', 'usd'], ['Part', 'pct']],
                    $parSource->map(fn ($r) => [$r->source === 'revenu' ? 'Revenu '.($revenus->get($r->autre_revenu_id)?->numero ?? '?').' · '.($revenus->get($r->autre_revenu_id)?->source ?? '') : 'Recettes de location', (int) $r->n, (float) $r->total, $part($r->total)])->all(),
                    null, barre: 2, liens: $parSource->map(fn ($r) => $r->autre_revenu_id ? route('revenus.show', $r->autre_revenu_id) : null)->all()),
                self::tableau('Par véhicule', [['Véhicule', 'texte'], ['Dépenses', 'nombre'], ['Montant (USD)', 'usd'], ['Part', 'pct']],
                    $parVehicule->map(fn ($r) => [$vehicules->get($r->vehicule_id)?->libelle() ?? '?', (int) $r->n, (float) $r->total, $part($r->total)])->all(),
                    null, barre: 2, liens: $parVehicule->map(fn ($r) => route('vehicules.show', $r->vehicule_id))->all()),
                self::tableau('Principaux bénéficiaires (10)', [['Payé à', 'texte'], ['Dépenses', 'nombre'], ['Montant (USD)', 'usd'], ['Part', 'pct']],
                    $parBeneficiaire->map(fn ($r) => [$r->beneficiaire, (int) $r->n, (float) $r->total, $part($r->total)])->all(), null, barre: 2),
                self::tableau('Liste des dépenses', [['N°', 'texte'], ['Date', 'date'], ['Description', 'texte'], ['Catégorie', 'texte'], ['Payé à', 'texte'], ['Par', 'texte'], ['Source', 'texte'], ['Montant (USD)', 'usd']],
                    $liste->map(fn ($d) => [$d->numero, $d->date_depense, $d->description, $d->categorie->nom, $d->beneficiaire ?? '—', $d->employe?->nom ?? '—', $d->sourceLibelle(), (float) $d->montant_usd])->all(),
                    null, note: $liste->count() === self::LIMITE_LISTE ? 'Liste limitée à '.self::LIMITE_LISTE.' lignes.' : null),
            ],
        ];
    }

    /** Autres revenus : reçu, utilisé, reste de chaque revenu de la période. */
    private function revenus(Periode $p): array
    {
        $revenus = AutreRevenu::avecUtilisation()->whereBetween('date_revenu', $p->bornes())->orderBy('date_revenu')->get();
        $recu = (float) $revenus->sum('montant_usd');
        $utilise = (float) $revenus->sum(fn ($r) => $r->montantUtilise());

        $parSource = $revenus->groupBy('source')->map(fn (Collection $g, $s) => [$s, $g->count(), (float) $g->sum('montant_usd'), (float) $g->sum(fn ($r) => $r->reste())])
            ->sortByDesc(2)->values();

        return [
            'cartes' => [
                ['Reçu (USD)', $recu, 'usd', 'ok'],
                ['Utilisé (USD)', $utilise, 'usd', 'accent'],
                ['Reste (USD)', $recu - $utilise, 'usd', 'default'],
                ['Revenus enregistrés', $revenus->count(), 'nombre', 'default'],
            ],
            'tableaux' => [
                self::tableau('Détail des revenus', [['N°', 'texte'], ['Date', 'date'], ['Source', 'texte'], ['Reçu (USD)', 'usd'], ['Utilisé', 'usd'], ['Reste', 'usd'], ['% utilisé', 'pct'], ['Statut', 'texte']],
                    $revenus->map(fn ($r) => [$r->numero, $r->date_revenu, $r->source, (float) $r->montant_usd, $r->montantUtilise(), $r->reste(), $r->pourcentageUtilise(), $r->statut()])->all(),
                    ['Total', null, null, $recu, $utilise, $recu - $utilise, $recu > 0 ? $utilise / $recu * 100 : 0, null],
                    barre: 5, liens: $revenus->map(fn ($r) => route('revenus.show', $r))->all()),
                self::tableau('Par source', [['Source', 'texte'], ['Revenus', 'nombre'], ['Reçu (USD)', 'usd'], ['Reste', 'usd']], $parSource->all(), null, barre: 2),
            ],
        ];
    }

    /** Paie : salaires, primes, avances, crédits et retenues par employé. */
    private function paie(Periode $p): array
    {
        $b = $p->bornes();

        $salaires = Bulletin::where('statut', 'Payé')->whereBetween('date_paiement', $b)
            ->selectRaw('employe_id, count(*) as n, sum(net_a_payer) as net, sum(primes) as primes, sum(total_credits) as retenues')
            ->groupBy('employe_id')->get()->keyBy('employe_id');
        $avances = Avance::whereBetween('date_avance', $b)->selectRaw('employe_id, sum(montant) as total')->groupBy('employe_id')->pluck('total', 'employe_id');
        $credits = Credit::whereBetween('date_octroi', $b)->selectRaw('employe_id, sum(montant) as total')->groupBy('employe_id')->pluck('total', 'employe_id');

        $ids = $salaires->keys()->merge($avances->keys())->merge($credits->keys())->unique();
        $employes = Employe::whereIn('id', $ids)->orderBy('nom')->get(['id', 'nom', 'poste']);

        $lignes = $employes->map(function ($e) use ($salaires, $avances, $credits) {
            $s = $salaires[$e->id] ?? null;
            $net = (float) ($s->net ?? 0);
            $av = (float) ($avances[$e->id] ?? 0);
            $cr = (float) ($credits[$e->id] ?? 0);

            return [$e->nom, $e->poste, (int) ($s->n ?? 0), $net, (float) ($s->primes ?? 0), $av, $cr, (float) ($s->retenues ?? 0), $net + $av + $cr];
        });
        $somme = fn (int $i) => (float) $lignes->sum($i);

        $creditsEnCours = Credit::with(['employe:id,nom', 'remboursements.bulletin'])->where('statut', 'Actif')->get();

        return [
            'cartes' => [
                ['Salaires nets payés', $somme(3), 'usd', 'default'],
                ['Primes', $somme(4), 'usd', 'default'],
                ['Avances données', $somme(5), 'usd', 'accent'],
                ['Crédits accordés', $somme(6), 'usd', 'accent'],
                ['Retenues de crédit', $somme(7), 'usd', 'default'],
                ['Total versé au personnel', $somme(8), 'usd', 'ok'],
            ],
            'tableaux' => [
                self::tableau('Par employé', [['Employé', 'texte'], ['Poste', 'texte'], ['Salaires payés', 'nombre'], ['Net payé', 'usd'], ['dont primes', 'usd'], ['Avances', 'usd'], ['Crédits accordés', 'usd'], ['Retenues crédit', 'usd'], ['Total versé', 'usd']],
                    $lignes->all(), ['Total', null, $somme(2), $somme(3), $somme(4), $somme(5), $somme(6), $somme(7), $somme(8)],
                    barre: 8, liens: $employes->map(fn ($e) => route('employes.show', $e))->all()),
                self::tableau("Crédits en cours (aujourd'hui)", [['Employé', 'texte'], ['Accordé le', 'date'], ['Total dû', 'usd'], ['Mensualité', 'usd'], ['Reste', 'usd']],
                    $creditsEnCours->map(fn ($c) => [$c->employe->nom, $c->date_octroi, (float) $c->montant_total, (float) $c->mensualite, $c->reste()])->all(),
                    ['Total', null, (float) $creditsEnCours->sum('montant_total'), null, (float) $creditsEnCours->sum(fn ($c) => $c->reste())], barre: 4),
            ],
        ];
    }

    /** Flotte : occupation, chiffre d'affaires, dépenses et marge par véhicule. */
    private function flotte(Periode $p): array
    {
        $b = $p->bornes();
        $jours = $p->jours();
        $finExclue = $p->au->copy()->addDay();

        $vehicules = Vehicule::orderBy('marque')->orderBy('modele')->get();
        $ca = Location::where('statut', '!=', 'Annulé')->whereBetween('date_depart', $b)->whereNotNull('vehicule_id')
            ->selectRaw('vehicule_id, count(*) as n, sum(total_usd) as ca')->groupBy('vehicule_id')->get()->keyBy('vehicule_id');
        $depenses = Depense::whereBetween('date_depense', $b)->whereNotNull('vehicule_id')
            ->selectRaw('vehicule_id, sum(montant_usd) as total')->groupBy('vehicule_id')->pluck('total', 'vehicule_id');

        // Jours d'occupation dans la période : chevauchement [départ, retour réel ou fin prévue[ ∩ [du, au]
        $occupation = Location::where('statut', '!=', 'Annulé')->whereNotNull('vehicule_id')
            ->where('date_depart', '<=', $b[1])->where('date_fin', '>', $b[0])
            ->get(['vehicule_id', 'date_depart', 'date_fin', 'date_retour'])
            ->groupBy('vehicule_id')
            ->map(fn (Collection $ls) => $ls->sum(function ($l) use ($p, $finExclue) {
                $debut = $l->date_depart->max($p->du);
                $fin = ($l->date_retour ? $l->date_retour->copy()->addDay() : $l->date_fin)->min($finExclue);

                return max(0, (int) $debut->diffInDays($fin, false));
            }));

        // Une ligne par véhicule, triée par marge décroissante (le lien suit sa ligne)
        $rangs = $vehicules->map(function ($v) use ($ca, $depenses, $occupation, $jours) {
            $j = min($jours, (int) ($occupation[$v->id] ?? 0));
            $c = (float) ($ca[$v->id]->ca ?? 0);
            $d = (float) ($depenses[$v->id] ?? 0);

            return ['ligne' => [$v->libelle(), $v->statut, (int) ($ca[$v->id]->n ?? 0), $j, $jours ? $j / $jours * 100 : 0, $c, $d, $c - $d],
                'lien' => route('vehicules.show', $v)];
        })->sortByDesc(fn ($r) => $r['ligne'][7])->values();
        $lignes = $rangs->pluck('ligne');
        $somme = fn (int $i) => (float) $lignes->sum($i);
        $tauxMoyen = $vehicules->count() ? $somme(3) / ($vehicules->count() * $jours) * 100 : 0;

        return [
            'cartes' => [
                ['Véhicules', $vehicules->count(), 'nombre', 'default'],
                ['Taux d\'occupation moyen', $tauxMoyen, 'pct', $tauxMoyen < config('rental.seuil_disponibilite') ? 'accent' : 'ok'],
                ['Chiffre d\'affaires (USD)', $somme(5), 'usd', 'ok'],
                ['Dépenses véhicules (USD)', $somme(6), 'usd', 'default'],
                ['Marge (USD)', $somme(7), 'usd', $somme(7) < 0 ? 'warn' : 'ok'],
            ],
            'tableaux' => [
                self::tableau("Rentabilité par véhicule ({$jours} jours)", [['Véhicule', 'texte'], ['Statut actuel', 'texte'], ['Locations', 'nombre'], ['Jours loués', 'nombre'], ['Occupation', 'pct'], ['CA net (USD)', 'usd'], ['Dépenses', 'usd'], ['Marge', 'usd']],
                    $lignes->all(),
                    ['Total', null, $somme(2), $somme(3), $tauxMoyen, $somme(5), $somme(6), $somme(7)],
                    barre: 4, liens: $rangs->pluck('lien')->all()),
            ],
        ];
    }

    // =====================================================================
    // Outils
    // =====================================================================

    /** @return array{0: string, 1: Periode} */
    private function contexte(Request $request): array
    {
        $type = array_key_exists($request->query('type'), self::RAPPORTS) ? $request->query('type') : 'synthese';

        return [$type, Periode::depuisRequete($request)];
    }

    /**
     * Décrit un tableau. Colonnes : [libellé, type] avec type texte|nombre|usd|cdf|pct|date.
     * $barre : index de la colonne dessinée en barre proportionnelle ; $liens : URL de la 1re colonne, ligne par ligne.
     */
    private static function tableau(string $titre, array $colonnes, array $lignes, ?array $total = null, ?int $barre = null, array $liens = [], ?string $note = null): array
    {
        $max = $barre !== null ? max([0.0001, ...array_map(fn ($l) => abs((float) ($l[$barre] ?? 0)), $lignes ?: [[$barre => 0]])]) : null;

        return compact('titre', 'colonnes', 'lignes', 'total', 'barre', 'liens', 'note', 'max');
    }

    public static function valeurTexte(mixed $valeur, string $type): string
    {
        if ($valeur === null) {
            return '';
        }

        return match ($type) {
            'usd' => Format::usd($valeur),
            'cdf' => Format::cdf($valeur),
            'pct' => number_format((float) $valeur, 1, ',', ' ').' %',
            'nombre' => number_format((float) $valeur, 0, ',', ' '),
            'date' => $valeur instanceof \DateTimeInterface ? $valeur->format('d/m/Y') : (string) $valeur,
            default => (string) $valeur,
        };
    }

    private static function ligneTexte(array $ligne, array $colonnes): array
    {
        return array_map(fn ($v, $c) => self::valeurTexte($v, $c[1]), $ligne, $colonnes);
    }
}

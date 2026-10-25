<?php

namespace App\Http\Controllers;

use App\Models\AutreRevenu;
use App\Models\Avance;
use App\Models\Bulletin;
use App\Models\Depense;
use App\Models\Employe;
use App\Models\Location;
use App\Models\Vehicule;
use App\Support\Caisse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Tableau de bord (TB-01, TB-02) : chiffres du mois choisi, évolution sur 10 mois, flotte, locations en cours, alertes.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $mois = PaieController::moisDemande($request);
        $debut = Carbon::createFromFormat('!Y-m', $mois);
        $moisPrecedent = $debut->copy()->subMonthNoOverflow()->format('Y-m');

        // --- Indicateurs du mois (et du mois précédent pour la tendance) ---
        $chiffres = fn (string $m) => [
            'locations' => self::totalLocations($m),
            'autres' => (float) AutreRevenu::where('mois', $m)->sum('montant_usd'),
            'depenses' => (float) Depense::where('mois', $m)->sum('montant_usd'),
            'paie' => self::paieDuMois($m),
        ];
        $actuel = $chiffres($mois);
        $precedent = $chiffres($moisPrecedent);
        // Solde du mois = entrées − dépenses − paie (avances données, salaires payés, crédits accordés)
        $actuel['solde'] = $actuel['locations'] + $actuel['autres'] - $actuel['depenses'] - $actuel['paie'];
        $precedent['solde'] = $precedent['locations'] + $precedent['autres'] - $precedent['depenses'] - $precedent['paie'];

        $tendance = fn (string $cle) => $precedent[$cle] != 0
            ? round(($actuel[$cle] - $precedent[$cle]) / abs($precedent[$cle]) * 100)
            : null;

        $locationsDuMois = Location::where('statut', '!=', 'Annulé')->where('date_depart', '>=', $debut->format('Y-m-d'))
            ->where('date_depart', '<=', $debut->copy()->endOfMonth()->format('Y-m-d'));
        $nbLocations = (clone $locationsDuMois)->count();

        $complementaires = [
            'commissions' => (float) (clone $locationsDuMois)->sum('commission'),
            'revenu_moyen' => $nbLocations ? $actuel['locations'] / $nbLocations : 0,
            'taux_commission' => (float) (clone $locationsDuMois)->avg('pourcentage_commission'),
            'nb_locations' => $nbLocations,
            'chauffeurs_actifs' => Employe::where('statut', 'Actif')->where('role', 'Chauffeur')->count(),
            'salaires_payes' => (float) Bulletin::where('mois', $mois)->where('statut', 'Payé')->sum('net_a_payer'),
        ];

        // --- Évolution sur 10 mois (TB-02) ---
        $graphique = collect(range(9, 0))->map(function (int $i) use ($debut) {
            $m = $debut->copy()->subMonthsNoOverflow($i);
            $cle = $m->format('Y-m');

            return [
                'libelle' => ucfirst(rtrim($m->locale('fr')->translatedFormat('M'), '.')),
                'mois' => $cle,
                'locations' => self::totalLocations($cle),
                'autres' => (float) AutreRevenu::where('mois', $cle)->sum('montant_usd'),
                'depenses' => (float) Depense::where('mois', $cle)->sum('montant_usd'),
                'paie' => self::paieDuMois($cle),
            ];
        });
        $maxGraphique = max(1, $graphique->max(fn ($g) => max($g['locations'], $g['autres'], $g['depenses'], $g['paie'])));

        // --- Flotte ---
        $parStatut = Vehicule::selectRaw('statut, count(*) as nombre')->groupBy('statut')->pluck('nombre', 'statut');
        $flotte = [
            'total' => (int) $parStatut->sum(),
            'disponibles' => (int) ($parStatut['Disponible'] ?? 0),
            'loues' => (int) ($parStatut['Loué'] ?? 0),
            'maintenance' => (int) ($parStatut['En maintenance'] ?? 0),
            'photo' => Vehicule::whereNotNull('photo')->inRandomOrder()->first(),
        ];

        // --- Locations en cours ---
        $enCoursRequete = Location::where('statut', 'Actif')->whereNull('date_retour');
        $locationsEnCours = (clone $enCoursRequete)->with(['client', 'vehicule'])->orderBy('date_fin')->take(6)->get();
        $nbEnCours = (clone $enCoursRequete)->count();

        // --- À traiter ---
        $employesActifs = Employe::where('statut', 'Actif')->count();
        $aTraiter = [
            'retards' => (clone $enCoursRequete)->whereDate('date_fin', '<', today())->count(),
            'retours_du_jour' => (clone $enCoursRequete)->whereDate('date_fin', today())->count(),
            'avances' => Avance::where('statut', 'En cours')->count(),
            'avances_montant' => (float) Avance::where('statut', 'En cours')->sum('montant'),
            'salaires_a_payer' => max(0, $employesActifs - Bulletin::where('mois', now()->format('Y-m'))->where('statut', 'Payé')->count()),
            'non_percues' => Location::where('statut', '!=', 'Annulé')->whereNull('date_perception')->count(),
        ];

        // --- Trésorerie disponible (par source) ---
        $tresorerie = [
            'locations' => Caisse::locations(),
            'autres' => Caisse::autresRevenus(),
        ];
        $tresorerie['total'] = $tresorerie['locations']['solde'] + $tresorerie['autres']['solde'];

        return view('dashboard', compact(
            'mois', 'actuel', 'tendance', 'complementaires', 'graphique', 'maxGraphique',
            'flotte', 'locationsEnCours', 'nbEnCours', 'aTraiter', 'tresorerie'
        ));
    }

    /**
     * Argent sorti pour le personnel pendant un mois : avances données, salaires payés (net), crédits accordés.
     */
    private static function paieDuMois(string $mois): float
    {
        $debut = Carbon::createFromFormat('!Y-m', $mois);
        $periode = [$debut->format('Y-m-d'), $debut->copy()->endOfMonth()->format('Y-m-d')];

        return (float) \App\Models\Avance::whereBetween('date_avance', $periode)->sum('montant')
            + (float) Bulletin::where('statut', 'Payé')->whereBetween('date_paiement', $periode)->sum('net_a_payer')
            + (float) \App\Models\Credit::whereBetween('date_octroi', $periode)->sum('montant');
    }

    /** Revenus de location d'un mois (date de départ, hors annulées). */
    private static function totalLocations(string $mois): float
    {
        $debut = Carbon::createFromFormat('!Y-m', $mois);

        return (float) Location::where('statut', '!=', 'Annulé')
            ->whereBetween('date_depart', [$debut->format('Y-m-d'), $debut->copy()->endOfMonth()->format('Y-m-d')])
            ->sum('total_usd');
    }
}

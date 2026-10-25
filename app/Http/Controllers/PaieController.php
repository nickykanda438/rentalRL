<?php

namespace App\Http\Controllers;

use App\Models\Avance;
use App\Models\Bulletin;
use App\Models\Credit;
use App\Models\Employe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaieController extends Controller
{
    /**
     * Page Paie : avances et bulletins du mois choisi, crédits en cours.
     */
    public function index(Request $request): View
    {
        $mois = self::moisDemande($request);
        $recherche = trim((string) $request->query('q'));

        // Filtre « nom de l'employé » (ou numéro de référence) réutilisé pour les trois listes
        $parEmploye = fn (Builder $q) => $q->when($recherche, fn (Builder $q) => $q->where(function (Builder $q) use ($recherche) {
            $q->whereHas('employe', fn (Builder $e) => $e->where('nom', 'like', "%{$recherche}%"));
            if (! $q->getModel() instanceof Credit) { // les crédits n'ont pas de numéro
                $q->orWhere('numero', 'like', "%{$recherche}%");
            }
        }));

        $avances = Avance::with(['employe', 'autreRevenu'])->where('mois', $mois)->tap($parEmploye)
            ->orderByDesc('date_avance')->get();

        $credits = Credit::with(['employe', 'remboursements.bulletin', 'autreRevenu'])->tap($parEmploye)
            ->orderByRaw("statut = 'Soldé'")   // crédits actifs d'abord
            ->orderByDesc('date_octroi')->get();

        $bulletins = Bulletin::with(['employe', 'autreRevenu'])->where('mois', $mois)->tap($parEmploye)->get()
            ->sortBy('employe.nom')->values();

        $employes = Employe::where('statut', 'Actif')->orderBy('nom')->get(['id', 'nom', 'salaire']);

        $stats = [
            'masse_salariale' => $bulletins->isNotEmpty() ? $bulletins->sum('salaire_base') : $employes->sum('salaire'),
            'avances' => $avances->sum('montant'),
            'credits' => $bulletins->sum('total_credits'),
            'net' => $bulletins->sum('net_a_payer'),
            'payes' => $bulletins->where('statut', 'Payé')->count(),
        ];

        // Aperçu du bouton « Payer tous les salaires »
        $aPayer = ['nombre' => 0, 'total' => 0.0, 'negatifs' => 0];
        foreach ($employes as $employe) {
            $apercu = Bulletin::apercu($employe, $mois);
            if ($apercu['deja_paye']) {
                continue;
            }
            if ($apercu['net'] < 0) {
                $aPayer['negatifs']++;

                continue;
            }
            $aPayer['nombre']++;
            $aPayer['total'] += $apercu['net'];
        }

        $onglet = session('onglet', $request->query('onglet', 'avances'));

        return view('paie.index', array_merge(
            compact('mois', 'avances', 'credits', 'bulletins', 'employes', 'stats', 'onglet', 'aPayer'),
            \App\Support\SourceArgent::options()
        ));
    }

    /**
     * Mois demandé dans l'URL (?mois=2026-10), sinon le mois en cours.
     */
    public static function moisDemande(Request $request): string
    {
        $mois = (string) $request->input('mois');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois) ? $mois : now()->format('Y-m');
    }
}

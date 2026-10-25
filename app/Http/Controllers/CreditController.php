<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Credit;
use App\Models\Employe;
use App\Support\Format;
use App\Support\SourceArgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreditController extends Controller
{
    /**
     * Accorde un crédit à un employé.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'montant' => Format::nombre($request->input('montant')),
            'taux' => Format::nombre($request->input('taux', '0')),
        ]);

        $donnees = $request->validate([
            'employe_id' => ['required', Rule::exists('employes', 'id')->where('statut', 'Actif')],
            'montant' => ['required', 'numeric', 'gt:0'],
            'taux' => ['required', 'numeric', 'min:0', 'max:100'],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:60'],
            'date_octroi' => ['required', 'date'],
            'mois_debut' => ['required', 'date_format:Y-m'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ], [
            'employe_id.required' => 'Choisissez un employé.',
            'employe_id.exists' => "Cet employé n'existe pas ou n'est pas actif.",
            'montant.gt' => 'Le montant doit être supérieur à 0.',
            'taux.max' => 'Le taux ne peut pas dépasser 100 %.',
            'duree_mois.min' => 'La durée doit être d\'au moins 1 mois.',
            'duree_mois.max' => 'La durée ne peut pas dépasser 60 mois.',
            'mois_debut.date_format' => 'Le premier mois de retenue est invalide.',
            'required' => 'Le champ :attribute est obligatoire.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
        ], [
            'montant' => 'montant',
            'taux' => 'taux',
            'duree_mois' => 'durée',
            'date_octroi' => "date d'octroi",
            'mois_debut' => 'premier mois de retenue',
        ]);

        // Le montant accordé sort de la source choisie
        $source = SourceArgent::valider($request);
        SourceArgent::verifierReste($source, (float) $donnees['montant']);

        $calcul = Credit::calculer((float) $donnees['montant'], (float) $donnees['taux'], (int) $donnees['duree_mois']);

        $credit = Credit::create($donnees + $calcul + $source + ['statut' => 'Actif']);
        $avertissement = SourceArgent::avertissement($source);
        Bulletin::recalculerPour($credit->employe, $credit->mois_debut);

        if ($request->input('retour') === 'employe') {
            return redirect()->route('employes.show', $credit->employe)->with(array_filter([
                'success' => 'Crédit de '.Format::usd($credit->montant)." $ accordé avec « {$credit->sourceLibelle()} » : ".Format::usd($credit->mensualite).' $ par mois à partir de '.Format::mois($credit->mois_debut).'.',
                'error' => $avertissement,
            ]));
        }

        return redirect()->route('paie.index', ['mois' => $request->input('retour_mois')])
            ->with(array_filter([
                'success' => "Crédit accordé avec « {$credit->sourceLibelle()} » : {$credit->employe->nom} remboursera ".Format::usd($credit->mensualite).' $ par mois à partir de '.Format::mois($credit->mois_debut).'.',
                'error' => $avertissement,
                'onglet' => 'credits',
            ]));
    }

    /**
     * Supprime un crédit (seulement s'il n'a encore rien été remboursé).
     */
    public function destroy(Credit $credit): RedirectResponse
    {
        if ($credit->aDesRemboursements()) {
            return back()->with(['error' => 'Ce crédit a déjà des remboursements sur des salaires payés, il ne peut plus être supprimé.', 'onglet' => 'credits']);
        }

        $employe = $credit->employe;
        $credit->delete(); // les retenues prévues sur les bulletins non payés sont supprimées aussi
        Bulletin::recalculerPour($employe, $credit->mois_debut);

        return back()->with(['success' => 'Crédit supprimé.', 'onglet' => 'credits']);
    }
}

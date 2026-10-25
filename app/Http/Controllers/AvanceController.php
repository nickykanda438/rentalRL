<?php

namespace App\Http\Controllers;

use App\Models\Avance;
use App\Models\Bulletin;
use App\Models\Employe;
use App\Support\Format;
use App\Support\SourceArgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AvanceController extends Controller
{
    /**
     * Enregistre une avance sur salaire.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['montant' => Format::nombre($request->input('montant'))]);

        $donnees = $request->validate([
            'employe_id' => ['required', Rule::exists('employes', 'id')->where('statut', 'Actif')],
            'montant' => ['required', 'numeric', 'gt:0'],
            'date_avance' => ['required', 'date'],
            'mode_paiement' => ['required', Rule::in(Avance::MODES_PAIEMENT)],
            'reference' => ['nullable', 'string', 'max:255'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ], [
            'employe_id.required' => 'Choisissez un employé.',
            'employe_id.exists' => "Cet employé n'existe pas ou n'est pas actif.",
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.gt' => 'Le montant doit être supérieur à 0.',
            'date_avance.required' => "La date de l'avance est obligatoire.",
            'mode_paiement.in' => 'Mode de paiement invalide.',
        ]);

        $source = SourceArgent::valider($request);
        SourceArgent::verifierReste($source, (float) $donnees['montant']);

        $employe = Employe::findOrFail($donnees['employe_id']);
        $mois = Carbon::parse($donnees['date_avance'])->format('Y-m');

        // Le bulletin du mois est-il déjà payé ?
        if ($employe->bulletins()->where('mois', $mois)->where('statut', 'Payé')->exists()) {
            throw ValidationException::withMessages([
                'date_avance' => "Le salaire de {$employe->nom} pour ".Format::mois($mois).' est déjà payé : choisissez une date du mois suivant.',
            ]);
        }

        // Les avances du mois ne peuvent pas dépasser le salaire
        $dejaAvance = (float) $employe->avances()->where('mois', $mois)->sum('montant');
        if ($dejaAvance + (float) $donnees['montant'] > (float) $employe->salaire) {
            throw ValidationException::withMessages([
                'montant' => "Avance trop élevée : {$employe->nom} a déjà reçu ".Format::usd($dejaAvance).' $ ce mois-ci pour un salaire de '.Format::usd($employe->salaire).' $.',
            ]);
        }

        $avance = Avance::create($donnees + $source + ['mois' => $mois, 'statut' => 'En cours']);
        Bulletin::recalculerPour($employe, $mois);
        $avertissement = SourceArgent::avertissement($source);

        // Avance donnée depuis la fiche employé : on y retourne
        if ($request->input('retour') === 'employe') {
            return redirect()->route('employes.show', $employe)->with(array_filter([
                'success' => 'Avance de '.Format::usd($avance->montant)." $ enregistrée (réf. {$avance->numero}), payée avec « {$avance->sourceLibelle()} », déduite du salaire de ".Format::mois($mois).'.',
                'error' => $avertissement,
            ]));
        }

        return redirect()->route('paie.index', ['mois' => $mois])
            ->with(array_filter(['success' => "Avance {$avance->numero} enregistrée, payée avec « {$avance->sourceLibelle()} ».", 'error' => $avertissement, 'onglet' => 'avances']));
    }

    /**
     * Supprime une avance (seulement si elle n'a pas encore été déduite).
     */
    public function destroy(Avance $avance): RedirectResponse
    {
        if ($avance->statut === 'Déduite') {
            return back()->with(['error' => 'Cette avance a déjà été déduite d\'un salaire payé, elle ne peut plus être supprimée.', 'onglet' => 'avances']);
        }

        $avance->delete();
        Bulletin::recalculerPour($avance->employe, $avance->mois);

        return back()->with(['success' => 'Avance supprimée.', 'onglet' => 'avances']);
    }
}

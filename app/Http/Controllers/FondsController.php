<?php

namespace App\Http\Controllers;

use App\Models\CategorieDepense;
use App\Models\Employe;
use App\Models\Fonds;
use App\Models\Vehicule;
use App\Support\Devise;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sommes reçues (argent remis à l'entreprise en dehors des recettes de location).
 */
class FondsController extends Controller
{
    /**
     * Fiche d'une somme : d'où elle vient, pourquoi, et tout ce qui a été dépensé dessus.
     */
    public function show(Fonds $fonds): View
    {
        $fonds->loadSum('depenses', 'montant_usd');

        $depenses = $fonds->depenses()->with(['categorie', 'vehicule', 'employe'])
            ->orderBy('date_depense')->orderBy('id')->get();

        // Récapitulatif par catégorie : « à quoi a servi cet argent »
        $parCategorie = $depenses->groupBy(fn ($d) => $d->categorie->nom)
            ->map(fn ($groupe) => ['montant' => (float) $groupe->sum('montant_usd'), 'nombre' => $groupe->count(), 'couleur' => $groupe->first()->categorie->couleur])
            ->sortByDesc('montant');

        return view('fonds.show', [
            'fonds' => $fonds,
            'depenses' => $depenses,
            'parCategorie' => $parCategorie,
            'categories' => CategorieDepense::where('active', true)->orderByDesc('par_defaut')->orderBy('nom')->get(),
            'vehicules' => Vehicule::orderBy('marque')->get(),
            'employes' => Employe::where('statut', 'Actif')->orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('piece_jointe')) {
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('fonds', 'public');
        }

        $fonds = Fonds::create($donnees);

        return redirect()->route('fonds.show', $fonds)
            ->with('success', "Somme {$fonds->numero} enregistrée : ".Format::usd($fonds->montant_usd)." $ reçus de {$fonds->provenance}.");
    }

    public function update(Request $request, Fonds $fonds): RedirectResponse
    {
        $donnees = $this->valider($request, $fonds);

        if ($request->hasFile('piece_jointe')) {
            if ($fonds->piece_jointe) {
                Storage::disk('public')->delete($fonds->piece_jointe);
            }
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('fonds', 'public');
        }

        $fonds->update($donnees);

        return redirect()->route('fonds.show', $fonds)->with('success', 'Somme mise à jour.');
    }

    public function destroy(Fonds $fonds): RedirectResponse
    {
        if ($fonds->depenses()->exists()) {
            return back()->with('error', "Impossible de supprimer {$fonds->numero} : des dépenses ont été faites sur cette somme.");
        }

        if ($fonds->piece_jointe) {
            Storage::disk('public')->delete($fonds->piece_jointe);
        }
        $fonds->delete();

        return redirect()->route('depenses.index', ['onglet' => 'fonds'])->with('success', "Somme {$fonds->numero} supprimée.");
    }

    private function valider(Request $request, ?Fonds $fonds = null): array
    {
        $request->merge([
            'montant_saisi' => Format::nombre($request->input('montant_saisi')),
            'taux_change' => Format::nombre($request->input('taux_change')),
        ]);

        $d = $request->validate([
            'date_reception' => ['required', 'date', 'before_or_equal:today'],
            'provenance' => ['required', 'string', 'max:255'],
            'motif' => ['required', 'string', 'max:255'],
            'devise' => ['required', Rule::in(Devise::LISTE)],
            'montant_saisi' => ['required', 'numeric', 'gt:0'],
            'taux_change' => ['required', 'numeric', 'gt:0'],
            'mode_reception' => ['required', Rule::in(Fonds::MODES)],
            'reference' => ['nullable', 'string', 'max:255'],
            'piece_jointe' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'date_reception.before_or_equal' => 'La date de réception ne peut pas être dans le futur.',
            'gt' => 'Le champ :attribute doit être supérieur à 0.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'piece_jointe.mimes' => 'La pièce jointe doit être un PDF, JPG ou PNG.',
        ], [
            'date_reception' => 'date de réception',
            'provenance' => 'provenance (qui a donné)',
            'motif' => 'motif',
            'montant_saisi' => 'montant',
            'taux_change' => 'taux de change',
        ]);

        $conversion = Devise::convertir($d['devise'], (float) $d['montant_saisi'], (float) $d['taux_change']);

        // On ne peut pas réduire une somme en dessous de ce qui a déjà été dépensé dessus
        if ($fonds && $conversion['montant_usd'] < $fonds->montantDepense()) {
            throw ValidationException::withMessages([
                'montant_saisi' => 'Déjà '.Format::usd($fonds->montantDepense()).' $ dépensés sur cette somme : le montant ne peut pas être plus petit.',
            ]);
        }

        unset($d['piece_jointe']);

        return $d + $conversion + ['mois' => Carbon::parse($d['date_reception'])->format('Y-m')];
    }
}

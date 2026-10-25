<?php

namespace App\Http\Controllers;

use App\Models\CategorieDepense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * DE-02 : catégories de dépenses personnalisées.
 */
class CategorieDepenseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:50', Rule::unique('categories_depense', 'nom')],
            'couleur' => ['required', Rule::in(array_keys(CategorieDepense::COULEURS))],
        ], [
            'nom.required' => 'Le nom de la catégorie est obligatoire.',
            'nom.unique' => 'Cette catégorie existe déjà.',
            'nom.max' => 'Le nom ne doit pas dépasser 50 caractères.',
        ]);

        CategorieDepense::create($donnees + ['par_defaut' => false, 'active' => true]);

        return back()->with(['success' => "Catégorie « {$donnees['nom']} » ajoutée.", 'onglet' => 'categories']);
    }

    /** Active / désactive (une catégorie désactivée n'est plus proposée, l'historique reste). */
    public function basculer(CategorieDepense $categorie): RedirectResponse
    {
        $categorie->update(['active' => ! $categorie->active]);

        return back()->with(['success' => "Catégorie « {$categorie->nom} » ".($categorie->active ? 'activée' : 'désactivée').'.', 'onglet' => 'categories']);
    }

    public function destroy(CategorieDepense $categorie): RedirectResponse
    {
        if ($categorie->par_defaut) {
            return back()->with(['error' => 'Les catégories par défaut ne se suppriment pas : vous pouvez les désactiver.', 'onglet' => 'categories']);
        }
        if ($categorie->depenses()->exists()) {
            return back()->with(['error' => "« {$categorie->nom} » est utilisée par des dépenses : désactivez-la plutôt.", 'onglet' => 'categories']);
        }

        $categorie->delete();

        return back()->with(['success' => "Catégorie « {$categorie->nom} » supprimée.", 'onglet' => 'categories']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Employe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeController extends Controller
{
    /**
     * Liste des employés (recherche, filtres, pagination).
     */
    public function index(Request $request): View
    {
        $employes = Employe::query()
            ->filtrer($request->query('q'), $request->query('role'), $request->query('statut'))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('employes.index', compact('employes'));
    }

    /**
     * Fiche détaillée d'un employé.
     */
    public function show(Employe $employe): View
    {
        return view('employes.show', compact('employe'));
    }

    /**
     * Enregistre un nouvel employé.
     */
    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('employes', 'public');
        }

        $employe = Employe::create($donnees);

        return redirect()->route('employes.show', $employe)->with('success', 'Employé enregistré avec succès.');
    }

    /**
     * Met à jour la fiche d'un employé.
     */
    public function update(Request $request, Employe $employe): RedirectResponse
    {
        $donnees = $this->valider($request, $employe);

        if ($request->hasFile('photo')) {
            if ($employe->photo) {
                Storage::disk('public')->delete($employe->photo);
            }
            $donnees['photo'] = $request->file('photo')->store('employes', 'public');
        }

        $employe->update($donnees);

        return redirect()->route('employes.show', $employe)->with('success', 'Fiche de l\'employé mise à jour.');
    }

    /**
     * Supprime un employé.
     */
    public function destroy(Employe $employe): RedirectResponse
    {
        if ($employe->aUnHistoriqueDePaie()) {
            return back()->with('error', "Impossible de supprimer {$employe->nom} : il a un historique de paie. Passez plutôt son statut à « Licencié ».");
        }

        if ($employe->locations()->exists()) {
            return back()->with('error', "Impossible de supprimer {$employe->nom} : il est chauffeur sur des locations. Passez plutôt son statut à « Licencié ».");
        }

        if ($employe->photo) {
            Storage::disk('public')->delete($employe->photo);
        }

        $employe->delete();

        return redirect()->route('employes.index')->with('success', 'Employé supprimé.');
    }

    /**
     * Règles de validation communes à l'ajout et à la modification.
     */
    private function valider(Request $request, ?Employe $employe = null): array
    {
        // « 1 500,00 » → « 1500.00 »
        $request->merge([
            'salaire' => str_replace([' ', ','], ['', '.'], (string) $request->input('salaire', '0')),
        ]);

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employes', 'email')->ignore($employe)],
            'telephone' => ['nullable', 'string', 'max:30'],
            'poste' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(Employe::ROLES)],
            'salaire' => ['required', 'numeric', 'min:0'],
            'date_embauche' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(Employe::STATUTS)],
            'lieu_travail' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', Rule::in(Employe::GENRES)],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'etat_civil' => ['nullable', Rule::in(Employe::ETATS_CIVILS)],
            'niveau_etudes' => ['nullable', Rule::in(Employe::NIVEAUX_ETUDES)],
            'domaine' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'territoire' => ['nullable', 'string', 'max:100'],
            'personne_reference' => ['nullable', 'string', 'max:255'],
            'contact_reference' => ['nullable', 'string', 'max:30', 'required_with:personne_reference'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'email' => "L'adresse email n'est pas valide.",
            'email.unique' => 'Cet email est déjà utilisé par un autre employé.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'min' => 'Le champ :attribute ne peut pas être négatif.',
            'in' => 'La valeur choisie pour :attribute est invalide.',
            'date' => "Le champ :attribute n'est pas une date valide.",
            'contact_reference.required_with' => 'Indiquez le contact de la personne de référence.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',
            'image' => 'La photo doit être une image (JPG, PNG…).',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ], [
            'nom' => 'nom complet',
            'poste' => 'poste',
            'role' => 'rôle',
            'salaire' => 'salaire',
            'statut' => 'statut',
            'date_embauche' => "date d'embauche",
            'date_naissance' => 'date de naissance',
        ]);

        unset($donnees['photo']); // géré à part (upload)

        return $donnees;
    }
}

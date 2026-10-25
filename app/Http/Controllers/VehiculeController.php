<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Vehicule;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    /**
     * Flotte : cartes des véhicules, compteurs et alertes (VE-01, VE-04, RG-06).
     */
    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('q'));
        $statut = $request->query('statut');
        $categorie = $request->query('categorie');

        $vehicules = Vehicule::with('locationEnCours.client')
            ->when($recherche, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('plaque', 'like', "%{$recherche}%")
                ->orWhere('marque', 'like', "%{$recherche}%")
                ->orWhere('modele', 'like', "%{$recherche}%")))
            ->when(in_array($statut, Vehicule::STATUTS, true), fn (Builder $q) => $q->where('statut', $statut))
            ->when(in_array($categorie, Vehicule::CATEGORIES, true), fn (Builder $q) => $q->where('categorie', $categorie))
            ->orderBy('marque')->orderBy('modele')
            ->paginate(12)->withQueryString();

        $parStatut = Vehicule::selectRaw('statut, count(*) as nombre')->groupBy('statut')->pluck('nombre', 'statut');
        $total = (int) $parStatut->sum();
        $stats = [
            'total' => $total,
            'disponibles' => (int) ($parStatut['Disponible'] ?? 0),
            'loues' => (int) ($parStatut['Loué'] ?? 0),
            'maintenance' => (int) ($parStatut['En maintenance'] ?? 0),
        ];
        $stats['taux'] = $total ? round($stats['disponibles'] / $total * 100) : 0;

        $alertes = [
            'seuil' => config('rental.seuil_disponibilite'),
            'sous_seuil' => $total > 0 && $stats['taux'] < config('rental.seuil_disponibilite'),
            'retours_du_jour' => Location::with(['vehicule', 'client'])->where('statut', 'Actif')->whereNull('date_retour')
                ->whereDate('date_fin', today())->get(),
            'retards' => Location::with(['vehicule', 'client'])->where('statut', 'Actif')->whereNull('date_retour')
                ->whereDate('date_fin', '<', today())->orderBy('date_fin')->get(),
        ];

        return view('vehicules.index', compact('vehicules', 'stats', 'alertes', 'statut', 'categorie'));
    }

    /**
     * Fiche véhicule : location en cours et historique complet (VE-02).
     */
    public function show(Vehicule $vehicule): View
    {
        $vehicule->load('locationEnCours.client', 'locationEnCours.chauffeur');

        $historique = $vehicule->locations()->with(['client', 'chauffeur'])
            ->orderByDesc('date_depart')->paginate(10);

        $valides = $vehicule->locations()->where('statut', '!=', 'Annulé');
        $bilan = [
            'locations' => (clone $valides)->count(),
            'jours' => (int) (clone $valides)->sum('nombre_jours'),
            'revenus' => (float) (clone $valides)->sum('total_usd'),
        ];

        return view('vehicules.show', compact('vehicule', 'historique', 'bilan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('vehicules', 'public');
        }

        $vehicule = Vehicule::create($donnees);

        return redirect()->route('vehicules.show', $vehicule)->with('success', "Véhicule {$vehicule->plaque} ajouté à la flotte.");
    }

    public function update(Request $request, Vehicule $vehicule): RedirectResponse
    {
        $donnees = $this->valider($request, $vehicule);

        if ($request->hasFile('photo')) {
            if ($vehicule->photo) {
                Storage::disk('public')->delete($vehicule->photo);
            }
            $donnees['photo'] = $request->file('photo')->store('vehicules', 'public');
        }

        $vehicule->update($donnees);

        return redirect()->route('vehicules.show', $vehicule)->with('success', 'Véhicule mis à jour.');
    }

    public function destroy(Vehicule $vehicule): RedirectResponse
    {
        if ($vehicule->locations()->exists()) {
            return back()->with('error', "Impossible de supprimer {$vehicule->plaque} : il a un historique de locations.");
        }

        if ($vehicule->photo) {
            Storage::disk('public')->delete($vehicule->photo);
        }
        $vehicule->delete();

        return redirect()->route('vehicules.index')->with('success', "Véhicule {$vehicule->plaque} retiré de la flotte.");
    }

    /**
     * Met le véhicule en maintenance, ou le remet en service.
     */
    public function maintenance(Vehicule $vehicule): RedirectResponse
    {
        if ($vehicule->statut === 'En maintenance') {
            $vehicule->update(['statut' => 'Disponible']);
            $vehicule->synchroniserStatut();

            return back()->with('success', "{$vehicule->plaque} est remis en service.");
        }

        if ($vehicule->locationEnCours()->exists()) {
            return back()->with('error', "{$vehicule->plaque} est en location : clôturez d'abord la location avant la maintenance.");
        }

        $vehicule->update(['statut' => 'En maintenance']);

        return back()->with('success', "{$vehicule->plaque} est en maintenance : il ne peut plus être assigné à une location.");
    }

    /**
     * Le statut « Loué » n'est jamais saisi à la main : il suit les locations.
     */
    private function valider(Request $request, ?Vehicule $vehicule = null): array
    {
        $request->merge([
            'tarif_journalier' => Format::nombre($request->input('tarif_journalier', '0')),
            'plaque' => mb_strtoupper(trim((string) $request->input('plaque'))),
        ]);

        return $request->validate([
            'marque' => ['required', 'string', 'max:50'],
            'modele' => ['required', 'string', 'max:100'],
            'annee' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'plaque' => ['required', 'string', 'max:20', Rule::unique('vehicules', 'plaque')->ignore($vehicule)],
            'tarif_journalier' => ['required', 'numeric', 'min:0'],
            'categorie' => ['required', Rule::in(Vehicule::CATEGORIES)],
            'photo' => ['nullable', 'image', 'max:5120'],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'plaque.unique' => 'Cette immatriculation existe déjà dans la flotte.',
            'annee.min' => "L'année doit être 1980 ou plus.",
            'annee.max' => "L'année n'est pas valide.",
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'in' => 'La valeur choisie pour :attribute est invalide.',
            'image' => 'La photo doit être une image (JPG, PNG…).',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ], [
            'marque' => 'marque',
            'modele' => 'modèle',
            'plaque' => 'immatriculation',
            'tarif_journalier' => 'tarif journalier',
            'categorie' => 'catégorie',
        ]);
    }
}

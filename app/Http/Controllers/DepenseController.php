<?php

namespace App\Http\Controllers;

use App\Models\AutreRevenu;
use App\Models\CategorieDepense;
use App\Models\Depense;
use App\Models\Employe;
use App\Models\Vehicule;
use App\Support\Caisse;
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
 * Dépenses : chaque dépense est payée soit avec les recettes de location, soit avec un autre revenu précis.
 */
class DepenseController extends Controller
{
    public function index(Request $request): View
    {
        // Sans « mois » dans l'URL : mois en cours ; « mois= » (vide) : tous les mois
        $mois = $request->has('mois') ? (string) $request->query('mois') : now()->format('Y-m');
        $mois = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois) ? $mois : null;
        $categorie = $request->query('categorie') ?: null;
        $source = $request->query('source') ?: null;
        $recherche = trim((string) $request->query('q')) ?: null;

        $requete = Depense::query()->filtrer($recherche, $mois, $categorie, $source);

        $stats = [
            'total_usd' => (float) (clone $requete)->sum('montant_usd'),
            'total_cdf' => (float) (clone $requete)->sum('montant_cdf'),
            'sur_locations' => (float) (clone $requete)->where('source', 'location')->sum('montant_usd'),
            'sur_revenus' => (float) (clone $requete)->where('source', 'revenu')->sum('montant_usd'),
            'nombre' => (clone $requete)->count(),
        ];

        $depenses = $requete->with(['categorie', 'autreRevenu', 'vehicule', 'employe'])
            ->orderByDesc('date_depense')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $caisse = Caisse::locations();
        $caisseRevenus = Caisse::autresRevenus();
        $tousLesRevenus = AutreRevenu::avecUtilisation()->orderByDesc('date_revenu')->orderByDesc('id')->get();
        $revenusDisponibles = $tousLesRevenus->filter(fn (AutreRevenu $r) => $r->reste() > 0)->values();

        $onglet = session('onglet', $request->query('onglet', 'depenses'));

        return view('depenses.index', array_merge(
            compact('depenses', 'stats', 'mois', 'categorie', 'source', 'caisse', 'caisseRevenus', 'tousLesRevenus', 'revenusDisponibles', 'onglet'),
            self::listes()
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('piece_jointe')) {
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('depenses', 'public');
        }

        $depense = Depense::create($donnees);

        return $this->retour($request, $depense, "Dépense {$depense->numero} enregistrée : ".Format::usd($depense->montant_usd).' $ payés avec « '.$depense->sourceLibelle().' ».');
    }

    public function update(Request $request, Depense $depense): RedirectResponse
    {
        $donnees = $this->valider($request, $depense);

        if ($request->hasFile('piece_jointe')) {
            if ($depense->piece_jointe) {
                Storage::disk('public')->delete($depense->piece_jointe);
            }
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('depenses', 'public');
        }

        $depense->update($donnees);

        return $this->retour($request, $depense, "Dépense {$depense->numero} mise à jour.");
    }

    public function destroy(Depense $depense): RedirectResponse
    {
        if ($depense->piece_jointe) {
            Storage::disk('public')->delete($depense->piece_jointe);
        }
        $depense->delete();

        return back()->with(['success' => "Dépense {$depense->numero} supprimée : le montant revient sur sa source.", 'onglet' => 'depenses']);
    }

    /** Menus déroulants du formulaire (aussi utilisés par la fiche d'un revenu). */
    public static function listes(): array
    {
        return [
            'categories' => CategorieDepense::orderByDesc('par_defaut')->orderBy('nom')->get(),
            'vehicules' => Vehicule::orderBy('marque')->orderBy('modele')->get(),
            'employes' => Employe::where('statut', 'Actif')->orderBy('nom')->get(['id', 'nom']),
        ];
    }

    // ------------------------------------------------------------------

    /**
     * Après l'enregistrement : retour sur la fiche du revenu si on venait de là, sinon sur la liste.
     * Avertit si la caisse des locations devient négative (RG-07).
     */
    private function retour(Request $request, Depense $depense, string $message): RedirectResponse
    {
        $flash = ['success' => $message, 'onglet' => 'depenses'];

        if ($depense->source === 'location' && ($solde = Caisse::locations()['solde']) < 0) {
            $flash['error'] = 'Attention : la caisse des recettes de location est négative ('.Format::usd($solde).' $). Les dépenses dépassent les recettes.';
        }

        if ($request->input('retour') === 'revenu' && $depense->autre_revenu_id) {
            return redirect()->route('revenus.show', $depense->autre_revenu_id)->with($flash);
        }

        return redirect()->route('depenses.index', ['mois' => $depense->mois])->with($flash);
    }

    private function valider(Request $request, ?Depense $depense = null): array
    {
        $request->merge([
            'montant_saisi' => Format::nombre($request->input('montant_saisi')),
            'taux_change' => Format::nombre($request->input('taux_change')),
            'autre_revenu_id' => $request->input('source') === 'revenu' ? ($request->input('autre_revenu_id') ?: null) : null,
            'vehicule_id' => $request->input('vehicule_id') ?: null,
            'employe_id' => $request->input('employe_id') ?: null,
        ]);

        $d = $request->validate([
            'date_depense' => ['required', 'date', 'before_or_equal:today'],
            'categorie_id' => ['required', Rule::exists('categories_depense', 'id')->where(fn ($q) => $q->where('active', true)->orWhere('id', $depense?->categorie_id ?? 0))],
            'description' => ['required', 'string', 'max:255'],
            'beneficiaire' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::in(array_keys(Depense::SOURCES))],
            'autre_revenu_id' => ['nullable', 'required_if:source,revenu', Rule::exists('autres_revenus', 'id')],
            'vehicule_id' => ['nullable', Rule::exists('vehicules', 'id')],
            'employe_id' => ['nullable', Rule::exists('employes', 'id')],
            'devise' => ['required', Rule::in(Devise::LISTE)],
            'montant_saisi' => ['required', 'numeric', 'gt:0'],
            'taux_change' => ['required', 'numeric', 'gt:0'],
            'mode_paiement' => ['required', Rule::in(Depense::MODES)],
            'piece_jointe' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'autre_revenu_id.required_if' => 'Choisissez le revenu avec lequel la dépense est payée.',
            'date_depense.before_or_equal' => 'La date de la dépense ne peut pas être dans le futur.',
            'gt' => 'Le champ :attribute doit être supérieur à 0.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'exists' => 'La valeur choisie pour :attribute est invalide.',
            'in' => 'La valeur choisie pour :attribute est invalide.',
            'piece_jointe.mimes' => 'La pièce justificative doit être un PDF, JPG ou PNG.',
            'piece_jointe.max' => 'La pièce justificative ne doit pas dépasser 5 Mo.',
        ], [
            'date_depense' => 'date',
            'categorie_id' => 'catégorie',
            'description' => 'description',
            'source' => "source de l'argent",
            'montant_saisi' => 'montant',
            'taux_change' => 'taux de change',
            'mode_paiement' => 'mode de paiement',
        ]);

        $conversion = Devise::convertir($d['devise'], (float) $d['montant_saisi'], (float) $d['taux_change']);

        // Dépense sur un autre revenu : impossible de dépasser ce qu'il en reste
        if ($d['source'] === 'revenu') {
            $revenu = AutreRevenu::findOrFail($d['autre_revenu_id']);
            $reste = $revenu->reste($depense?->id);
            if ($conversion['montant_usd'] > $reste) {
                throw ValidationException::withMessages([
                    'montant_saisi' => 'Il ne reste que '.Format::usd($reste)." $ sur le revenu {$revenu->numero} ({$revenu->source}) : impossible de dépenser ".Format::usd($conversion['montant_usd']).' $.',
                ]);
            }
        }

        unset($d['piece_jointe']);

        return $d + $conversion + ['mois' => Carbon::parse($d['date_depense'])->format('Y-m')];
    }
}

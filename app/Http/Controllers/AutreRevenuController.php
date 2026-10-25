<?php

namespace App\Http\Controllers;

use App\Models\AutreRevenu;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Autres revenus (AR-01 à AR-03) : entrées d'argent hors location.
 * Chaque revenu garde sa trace : reçu, utilisé par des dépenses, reste.
 */
class AutreRevenuController extends Controller
{
    public function index(Request $request): View
    {
        [$recherche, $mois] = $this->filtres($request);

        $requete = AutreRevenu::query()->filtrer($recherche, $mois);

        $stats = [
            'utilise' => (float) (clone $requete)->avecUtilisation()->get()->sum(fn (AutreRevenu $r) => $r->montantUtilise()),
            'usd' => (float) (clone $requete)->sum('montant_usd'),
            'cdf' => (float) (clone $requete)->sum('montant_cdf'),
            'sources' => (clone $requete)->distinct()->count('source'),
            'nombre' => (clone $requete)->count(),
        ];

        $revenus = $requete->avecUtilisation()->orderByDesc('date_revenu')->orderByDesc('id')->paginate(15)->withQueryString();
        $sources = AutreRevenu::select('source')->distinct()->orderBy('source')->pluck('source');
        $caisse = Caisse::autresRevenus();

        return view('revenus.index', compact('revenus', 'stats', 'mois', 'sources', 'caisse'));
    }

    /**
     * Fiche de trace d'un revenu : d'où vient l'argent, ce qui a été payé avec, ce qu'il reste.
     */
    public function show(AutreRevenu $revenu): View
    {
        // Toutes les sorties : dépenses, avances, salaires, crédits (dans l'ordre des dates)
        $mouvements = $revenu->mouvements();

        $parType = $mouvements->groupBy('type')
            ->map(fn ($groupe) => ['montant' => (float) $groupe->sum('montant'), 'nombre' => $groupe->count(), 'couleur' => $groupe->first()['couleur']])
            ->sortByDesc('montant');

        return view('revenus.show', array_merge(compact('revenu', 'mouvements', 'parType'), DepenseController::listes()));
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        if ($request->hasFile('piece_jointe')) {
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('revenus', 'public');
        }

        $revenu = AutreRevenu::create($donnees);

        return redirect()->route('revenus.show', $revenu)
            ->with('success', "Revenu {$revenu->numero} enregistré : ".Format::usd($revenu->montant_usd)." $ de « {$revenu->source} ».");
    }

    public function update(Request $request, AutreRevenu $revenu): RedirectResponse
    {
        $donnees = $this->valider($request);

        // On ne peut pas descendre en dessous de ce qui a déjà été utilisé
        if ($donnees['montant_usd'] < $revenu->montantUtilise()) {
            throw ValidationException::withMessages([
                'montant_saisi' => 'Déjà '.Format::usd($revenu->montantUtilise()).' $ utilisés sur ce revenu : le montant ne peut pas être plus petit.',
            ]);
        }

        if ($request->hasFile('piece_jointe')) {
            if ($revenu->piece_jointe) {
                Storage::disk('public')->delete($revenu->piece_jointe);
            }
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('revenus', 'public');
        }

        $revenu->update($donnees);

        return redirect()->route('revenus.show', $revenu)->with('success', "Revenu {$revenu->numero} mis à jour.");
    }

    public function destroy(AutreRevenu $revenu): RedirectResponse
    {
        if ($revenu->montantUtilise() > 0) {
            return back()->with('error', "Impossible de supprimer {$revenu->numero} : de l'argent a déjà été utilisé (dépenses, avances, salaires ou crédits).");
        }

        if ($revenu->piece_jointe) {
            Storage::disk('public')->delete($revenu->piece_jointe);
        }
        $revenu->delete();

        return redirect()->route('revenus.index')->with('success', "Revenu {$revenu->numero} supprimé.");
    }

    /** AR-03 : export CSV (s'ouvre dans Excel) avec les filtres en cours. */
    public function exporter(Request $request): StreamedResponse
    {
        [$recherche, $mois] = $this->filtres($request);
        $revenus = AutreRevenu::filtrer($recherche, $mois)->orderBy('date_revenu')->get();

        return response()->streamDownload(function () use ($revenus) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['N°', 'Date', 'Source', 'Description', 'Devise', 'Montant saisi', 'Taux', 'Montant USD', 'Montant CDF', 'Reçu par', 'N° transaction'], ';');
            foreach ($revenus as $r) {
                fputcsv($f, [$r->numero, $r->date_revenu->format('d/m/Y'), $r->source, $r->description, $r->devise,
                    Format::usd($r->montant_saisi), Format::usd($r->taux_change), Format::usd($r->montant_usd), Format::usd($r->montant_cdf),
                    $r->mode_reception, $r->reference], ';');
            }
            fclose($f);
        }, 'autres-revenus-'.($mois ?: 'tous').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Sans « mois » dans l'URL : mois en cours ; « mois= » (vide) : tous les mois. */
    private function filtres(Request $request): array
    {
        $mois = $request->has('mois') ? (string) $request->query('mois') : now()->format('Y-m');

        return [
            trim((string) $request->query('q')) ?: null,
            preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois) ? $mois : null,
        ];
    }

    private function valider(Request $request): array
    {
        $request->merge([
            'montant_saisi' => Format::nombre($request->input('montant_saisi')),
            'taux_change' => Format::nombre($request->input('taux_change')),
        ]);

        $d = $request->validate([
            'date_revenu' => ['required', 'date', 'before_or_equal:today'],
            'source' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'devise' => ['required', Rule::in(Devise::LISTE)],
            'montant_saisi' => ['required', 'numeric', 'gt:0'],
            'taux_change' => ['required', 'numeric', 'gt:0'],
            'mode_reception' => ['required', Rule::in(AutreRevenu::MODES)],
            'reference' => ['nullable', 'string', 'max:255'],
            'piece_jointe' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'date_revenu.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'gt' => 'Le champ :attribute doit être supérieur à 0.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'piece_jointe.mimes' => 'La pièce jointe doit être un PDF, JPG ou PNG.',
        ], [
            'date_revenu' => 'date',
            'source' => 'source / provenance',
            'montant_saisi' => 'montant',
            'taux_change' => 'taux de change',
        ]);

        unset($d['piece_jointe']);

        return $d
            + Devise::convertir($d['devise'], (float) $d['montant_saisi'], (float) $d['taux_change'])
            + ['mois' => Carbon::parse($d['date_revenu'])->format('Y-m')];
    }
}

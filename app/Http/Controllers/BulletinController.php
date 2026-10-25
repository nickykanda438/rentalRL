<?php

namespace App\Http\Controllers;

use App\Models\Avance;
use App\Models\Bulletin;
use App\Models\Employe;
use App\Support\Format;
use App\Support\SourceArgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BulletinController extends Controller
{
    /**
     * Génère (ou met à jour) les bulletins du mois pour tous les employés actifs.
     * Les bulletins déjà payés ne sont pas modifiés.
     */
    public function generer(Request $request): RedirectResponse
    {
        $mois = PaieController::moisDemande($request);

        $prepares = 0;
        $dejaPayes = 0;
        foreach (Employe::where('statut', 'Actif')->get() as $employe) {
            Bulletin::preparer($employe, $mois) ? $prepares++ : $dejaPayes++;
        }

        if ($prepares + $dejaPayes === 0) {
            return back()->with(['error' => 'Aucun employé actif : ajoutez d\'abord des employés.', 'onglet' => 'bulletins']);
        }

        $message = "{$prepares} bulletin(s) prêt(s) pour ".Format::mois($mois).'.';
        if ($dejaPayes) {
            $message .= " {$dejaPayes} bulletin(s) déjà payé(s) non modifié(s).";
        }

        return redirect()->route('paie.index', ['mois' => $mois])->with(['success' => $message, 'onglet' => 'bulletins']);
    }

    /**
     * Marque un bulletin comme payé : les avances du mois sont déduites
     * et les retenues de crédit deviennent des remboursements.
     */
    public function payer(Request $request, Bulletin $bulletin): RedirectResponse
    {
        if ($bulletin->estPaye()) {
            return back()->with(['error' => 'Ce bulletin est déjà payé.', 'onglet' => 'bulletins']);
        }

        $request->merge(['primes' => Format::nombre($request->input('primes', '0'))]);

        $donnees = $request->validate([
            'primes' => ['required', 'numeric', 'min:0'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', Rule::in(Avance::MODES_PAIEMENT)],
            'reference' => ['nullable', 'string', 'max:255'],
        ], [
            'primes.numeric' => 'Les primes doivent être un nombre.',
            'primes.min' => 'Les primes ne peuvent pas être négatives.',
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'mode_paiement.in' => 'Mode de paiement invalide.',
        ]);

        $source = SourceArgent::valider($request);
        $paye = Bulletin::payerPour($bulletin->employe, $bulletin->mois, $donnees + $source);

        return redirect()->route('paie.index', ['mois' => $bulletin->mois])->with(array_filter([
            'success' => "Salaire de {$bulletin->employe->nom} payé : ".Format::usd($paye?->net_a_payer).' $ avec « '.$paye?->sourceLibelle().' ».',
            'error' => SourceArgent::avertissement($source),
            'onglet' => 'bulletins',
        ]));
    }

    /**
     * Bouton « Payer tous les salaires » : génère les bulletins manquants du mois
     * et paie tous ceux qui sont en attente (net positif), en une seule fois.
     */
    public function payerTout(Request $request): RedirectResponse
    {
        $mois = PaieController::moisDemande($request);

        $donnees = $request->validate([
            'date_paiement' => ['required', 'date', 'before_or_equal:'.now()->addDays(30)->format('Y-m-d')],
            'mode_paiement' => ['required', Rule::in(Avance::MODES_PAIEMENT)],
        ], [
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'date_paiement.before_or_equal' => 'La date de paiement ne peut pas dépasser 30 jours dans le futur.',
            'mode_paiement.in' => 'Mode de paiement invalide.',
        ]);

        $source = SourceArgent::valider($request);
        $employes = Employe::where('statut', 'Actif')->orderBy('nom')->get();

        // Le revenu choisi doit couvrir le total avant de commencer (tout ou rien)
        $totalPrevu = $employes->map(fn ($e) => Bulletin::apercu($e, $mois))
            ->reject(fn ($a) => $a['deja_paye'] || $a['net'] < 0)->sum('net');
        SourceArgent::verifierReste($source, (float) $totalPrevu);

        $donnees += $source;
        $payes = 0;
        $total = 0.0;
        $ignores = [];

        foreach ($employes as $employe) {
            $apercu = Bulletin::apercu($employe, $mois);
            if ($apercu['deja_paye']) {
                continue;
            }
            if ($apercu['net'] < 0) {
                $ignores[] = $employe->nom;
                Bulletin::preparer($employe, $mois); // le bulletin reste visible, à régler à la main

                continue;
            }

            $bulletin = Bulletin::payerPour($employe, $mois, $donnees);
            if ($bulletin) {
                $payes++;
                $total += (float) $bulletin->net_a_payer;
            }
        }

        if ($payes === 0 && ! $ignores) {
            return back()->with(['error' => 'Aucun salaire à payer : tous les salaires de '.Format::mois($mois).' sont déjà payés.', 'onglet' => 'bulletins']);
        }

        $message = "{$payes} salaire(s) payé(s) pour ".Format::mois($mois).' : '.Format::usd($total).' $ au total, avec « '.($source['source'] === 'revenu' ? \App\Models\AutreRevenu::find($source['autre_revenu_id'])?->numero : 'Recettes de location').' ».';
        if ($ignores) {
            $message .= ' Non payé(s) car le net est négatif : '.implode(', ', $ignores).'.';
        }

        return redirect()->route('paie.index', ['mois' => $mois])
            ->with(array_filter(['success' => $message, 'error' => SourceArgent::avertissement($source), 'onglet' => 'bulletins']));
    }

    /**
     * Bouton « Payer le salaire » de la fiche employé.
     */
    public function payerEmploye(Request $request, Employe $employe): RedirectResponse
    {
        if ($employe->statut !== 'Actif') {
            return back()->with('error', "{$employe->nom} n'est pas actif : son salaire ne peut pas être payé.");
        }

        $request->merge(['primes' => Format::nombre($request->input('primes', '0'))]);

        $donnees = $request->validate([
            'mois' => ['required', 'date_format:Y-m'],
            'primes' => ['required', 'numeric', 'min:0'],
            'date_paiement' => ['required', 'date', 'before_or_equal:'.now()->addDays(30)->format('Y-m-d')],
            'mode_paiement' => ['required', Rule::in(Avance::MODES_PAIEMENT)],
            'reference' => ['nullable', 'string', 'max:255'],
        ], [
            'mois.required' => 'Choisissez le mois à payer.',
            'primes.numeric' => 'Les primes doivent être un nombre.',
            'primes.min' => 'Les primes ne peuvent pas être négatives.',
            'date_paiement.required' => 'La date de paiement est obligatoire.',
            'date_paiement.before_or_equal' => 'La date de paiement ne peut pas dépasser 30 jours dans le futur.',
        ]);

        $apercu = Bulletin::apercu($employe, $donnees['mois']);
        if ($apercu['net'] + (float) $donnees['primes'] - $apercu['primes'] < 0) {
            throw ValidationException::withMessages(['mois' => 'Le net à payer serait négatif : les retenues dépassent le salaire.']);
        }

        $source = SourceArgent::valider($request);
        $bulletin = Bulletin::payerPour($employe, $donnees['mois'], $donnees + $source);

        if (! $bulletin) {
            return back()->with('error', "Le salaire de {$employe->nom} pour ".Format::mois($donnees['mois']).' est déjà payé.');
        }

        return redirect()->route('employes.show', $employe)->with(array_filter([
            'success' => 'Salaire de '.Format::mois($bulletin->mois).' payé : '.Format::usd($bulletin->net_a_payer)." $ (réf. {$bulletin->numero}) avec « {$bulletin->sourceLibelle()} ».",
            'error' => SourceArgent::avertissement($source),
        ]));
    }

    /**
     * Version imprimable : un bulletin (?bulletin=ID) ou tous ceux du mois (?mois=AAAA-MM).
     */
    public function imprimer(Request $request): View
    {
        $bulletins = Bulletin::with(['employe', 'remboursements.credit'])
            ->when(
                $request->filled('bulletin'),
                fn ($q) => $q->whereKey($request->integer('bulletin')),
                fn ($q) => $q->where('mois', PaieController::moisDemande($request)),
            )
            ->get()
            ->sortBy('employe.nom')->values();

        abort_if($bulletins->isEmpty(), 404, 'Aucun bulletin à imprimer pour ce mois.');

        return view('paie.imprimer', compact('bulletins'));
    }
}

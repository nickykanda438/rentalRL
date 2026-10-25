<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employe;
use App\Models\Location;
use App\Models\Vehicule;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationController extends Controller
{
    /**
     * Liste des locations (recettes) avec filtres et totaux.
     */
    public function index(Request $request): View
    {
        [$recherche, $mois, $statut] = $this->filtres($request);

        $requete = Location::query()->filtrer($recherche, $mois, $statut);

        // Totaux (BL-05) : les locations annulées ne comptent pas
        $valides = (clone $requete)->where('statut', '!=', 'Annulé');
        $stats = [
            'revenus' => (clone $valides)->sum('total_usd'),
            'commissions' => (clone $valides)->sum('commission'),
            'cdf' => (clone $valides)->sum('total_cdf'),
            'entrees' => (clone $requete)->count(),
            'retards' => Location::where('statut', 'Actif')->whereNull('date_retour')->whereDate('date_fin', '<', today())->count(),
        ];

        $locations = $requete->with(['client', 'vehicule', 'chauffeur'])
            ->orderByDesc('date_depart')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('locations.index', array_merge(
            compact('locations', 'stats', 'mois', 'statut'),
            $this->listesFormulaire()
        ));
    }

    /**
     * Détail d'une location.
     */
    public function show(Location $location): View
    {
        $location->load(['client', 'vehicule', 'chauffeur']);

        return view('locations.show', array_merge(compact('location'), $this->listesFormulaire()));
    }

    /**
     * Contrat / reçu imprimable.
     */
    public function contrat(Location $location): View
    {
        $location->load(['client', 'vehicule', 'chauffeur']);

        return view('locations.contrat', compact('location'));
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        $location = DB::transaction(function () use ($request, $donnees) {
            if ($request->hasFile('piece_jointe')) {
                $donnees['piece_jointe'] = $request->file('piece_jointe')->store('locations', 'public');
            }

            $location = Location::create($donnees + ['statut' => 'Actif']);
            $location->vehicule?->synchroniserStatut();

            return $location;
        });

        return redirect()->route('locations.show', $location)
            ->with('success', "Location enregistrée : contrat {$location->numero_contrat}.");
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $donnees = $this->valider($request, $location);
        $ancienVehicule = $location->vehicule;

        DB::transaction(function () use ($request, $donnees, $location, $ancienVehicule) {
            if ($request->hasFile('piece_jointe')) {
                if ($location->piece_jointe) {
                    Storage::disk('public')->delete($location->piece_jointe);
                }
                $donnees['piece_jointe'] = $request->file('piece_jointe')->store('locations', 'public');
            }

            $location->update($donnees);
            $ancienVehicule?->synchroniserStatut();
            $location->refresh()->vehicule?->synchroniserStatut();
        });

        return redirect()->route('locations.show', $location)->with('success', 'Location mise à jour.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $vehicule = $location->vehicule;

        if ($location->piece_jointe) {
            Storage::disk('public')->delete($location->piece_jointe);
        }
        $location->delete();
        $vehicule?->synchroniserStatut();

        return redirect()->route('locations.index')->with('success', "Location {$location->numero_contrat} supprimée.");
    }

    /**
     * BL / VE-05 : clôture (retour réel du véhicule) → Terminé, véhicule de nouveau disponible.
     */
    public function cloturer(Request $request, Location $location): RedirectResponse
    {
        abort_unless($location->statut === 'Actif', 403, 'Seule une location active peut être clôturée.');

        $donnees = $request->validate([
            'date_retour' => ['required', 'date', 'after_or_equal:'.$location->date_depart->format('Y-m-d'), 'before_or_equal:today'],
            'date_perception' => ['nullable', 'date'],
        ], [
            'date_retour.required' => 'La date de retour est obligatoire.',
            'date_retour.after_or_equal' => 'Le retour ne peut pas être avant le départ.',
            'date_retour.before_or_equal' => 'La date de retour ne peut pas être dans le futur.',
        ]);

        $location->update([
            'date_retour' => $donnees['date_retour'],
            'date_perception' => $donnees['date_perception'] ?? $location->date_perception,
            'statut' => 'Terminé',
        ]);
        $location->vehicule?->synchroniserStatut();

        return back()->with('success', "Location {$location->numero_contrat} clôturée.");
    }

    public function annuler(Location $location): RedirectResponse
    {
        abort_unless($location->statut === 'Actif', 403, 'Seule une location active peut être annulée.');

        $location->update(['statut' => 'Annulé']);
        $location->vehicule?->synchroniserStatut();

        return back()->with('success', "Location {$location->numero_contrat} annulée.");
    }

    /**
     * Export CSV (s'ouvre dans Excel) avec les filtres en cours.
     */
    public function exporter(Request $request): StreamedResponse
    {
        [$recherche, $mois, $statut] = $this->filtres($request);

        $locations = Location::with(['client', 'vehicule', 'chauffeur'])
            ->filtrer($recherche, $mois, $statut)->orderBy('date_depart')->get();

        $nom = 'locations-'.($mois ?: 'toutes').'.csv';

        return response()->streamDownload(function () use ($locations) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF"); // BOM : accents corrects dans Excel
            fputcsv($f, ['N° contrat', 'Date départ', 'Date fin', 'Retour', 'Client', 'Téléphone', 'Destination', 'Véhicule', 'Chauffeur',
                'Journalier USD', 'Jours', 'Sous-total USD', '% commission', 'Commission USD', 'Total USD', 'Taux', 'Total CDF', 'Perçu le', 'Statut'], ';');
            foreach ($locations as $l) {
                fputcsv($f, [
                    $l->numero_contrat, $l->date_depart->format('d/m/Y'), $l->date_fin->format('d/m/Y'), $l->date_retour?->format('d/m/Y'),
                    $l->client->nom, $l->client->telephone, $l->destination, $l->vehicule?->libelle(), $l->chauffeur?->nom,
                    Format::usd($l->montant_journalier), $l->nombre_jours, Format::usd($l->sous_total), Format::usd($l->pourcentage_commission),
                    Format::usd($l->commission), Format::usd($l->total_usd), Format::usd($l->taux_change), Format::usd($l->total_cdf),
                    $l->date_perception?->format('d/m/Y'), $l->statutAffiche(),
                ], ';');
            }
            fclose($f);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------------

    /**
     * Filtres de l'URL. Sans paramètre « mois », on affiche le mois en cours ; « mois= » (vide) affiche tout.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function filtres(Request $request): array
    {
        $mois = $request->has('mois') ? (string) $request->query('mois') : now()->format('Y-m');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois)) {
            $mois = null;
        }

        return [trim((string) $request->query('q')) ?: null, $mois, $request->query('statut') ?: null];
    }

    /**
     * Listes des menus déroulants du formulaire.
     */
    private function listesFormulaire(): array
    {
        return [
            'vehicules' => Vehicule::where('statut', '!=', 'En maintenance')->orderBy('marque')->orderBy('modele')->get(),
            'chauffeurs' => Employe::where('statut', 'Actif')->where('role', 'Chauffeur')->orderBy('nom')->get(['id', 'nom']),
            'clients' => Client::orderBy('nom')->get(['nom', 'telephone', 'adresse']),
        ];
    }

    /**
     * Validation + règles de gestion (RG-01 à RG-04, RG-08) + calculs.
     */
    private function valider(Request $request, ?Location $location = null): array
    {
        $request->merge([
            'montant_journalier' => Format::nombre($request->input('montant_journalier')),
            'pourcentage_commission' => Format::nombre($request->input('pourcentage_commission', '0')),
            'taux_change' => Format::nombre($request->input('taux_change')),
            'vehicule_id' => $request->input('vehicule_id') ?: null,
            'chauffeur_id' => $request->input('chauffeur_id') ?: null,
        ]);

        $d = $request->validate([
            'date_depart' => ['required', 'date'],
            'nombre_jours' => ['required', 'integer', 'min:1', 'max:365'],
            'destination' => ['nullable', 'required_with:vehicule_id', 'string', 'max:255'],
            'chauffeur_id' => ['nullable', 'required_with:vehicule_id', Rule::exists('employes', 'id')],
            'vehicule_id' => ['nullable', Rule::exists('vehicules', 'id')],
            'montant_journalier' => ['required', 'numeric', 'gt:0'],
            'pourcentage_commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'taux_change' => ['required', 'numeric', 'gt:0'],
            'date_perception' => ['nullable', 'date'],
            'client_nom' => ['required', 'string', 'max:255'],
            'client_telephone' => ['nullable', 'string', 'max:30'],
            'client_adresse' => ['nullable', 'string', 'max:255'],
            'piece_jointe' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observation' => ['nullable', 'string', 'max:2000'],
        ], [
            'required' => 'Le champ :attribute est obligatoire.',
            'required_with' => 'Le champ :attribute est obligatoire quand un véhicule est assigné.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
            'gt' => 'Le champ :attribute doit être supérieur à 0.',
            'nombre_jours.min' => 'La durée doit être d\'au moins 1 jour.',
            'pourcentage_commission.max' => 'La commission ne peut pas dépasser 100 %.',
            'date' => "Le champ :attribute n'est pas une date valide.",
            'exists' => 'La valeur choisie pour :attribute est invalide.',
            'piece_jointe.mimes' => 'La pièce jointe doit être un PDF, JPG ou PNG.',
            'piece_jointe.max' => 'La pièce jointe ne doit pas dépasser 5 Mo.',
        ], [
            'date_depart' => 'date de départ',
            'nombre_jours' => 'nombre de jours',
            'destination' => 'destination',
            'chauffeur_id' => 'chauffeur',
            'vehicule_id' => 'véhicule',
            'montant_journalier' => 'montant journalier',
            'pourcentage_commission' => 'pourcentage de commission',
            'taux_change' => 'taux de change',
            'date_perception' => 'date de perception',
            'client_nom' => 'nom du client',
        ]);

        // RG-01 : date de fin = départ + nombre de jours
        $depart = Carbon::parse($d['date_depart'])->startOfDay();
        $fin = $depart->copy()->addDays((int) $d['nombre_jours']);

        if ($d['vehicule_id']) {
            $vehicule = Vehicule::findOrFail($d['vehicule_id']);
            $vehiculeChange = ! $location || (int) $location->vehicule_id !== (int) $d['vehicule_id']
                || ! $location->date_depart->equalTo($depart);

            // RG-03 : à l'assignation, la date de départ ne peut pas être passée
            if ($vehiculeChange && $depart->isBefore(today())) {
                throw ValidationException::withMessages(['date_depart' => 'Pour assigner un véhicule, la date de départ ne peut pas être passée.']);
            }

            if ($vehicule->statut === 'En maintenance') {
                throw ValidationException::withMessages(['vehicule_id' => "Le véhicule {$vehicule->libelle()} est en maintenance."]);
            }

            // RG-04 : pas deux locations du même véhicule sur des périodes qui se chevauchent
            $conflit = Location::chevauchements($vehicule->id, $depart->format('Y-m-d'), $fin->format('Y-m-d'), $location?->id)->first();
            if ($conflit) {
                throw ValidationException::withMessages([
                    'vehicule_id' => "Ce véhicule est déjà loué du {$conflit->date_depart->format('d/m/Y')} au {$conflit->date_fin->format('d/m/Y')} (contrat {$conflit->numero_contrat}).",
                ]);
            }
        }

        // Client : retrouvé par nom + téléphone, sinon créé ; l'adresse est mise à jour
        $client = Client::firstOrNew(['nom' => trim($d['client_nom']), 'telephone' => ($d['client_telephone'] ?? null) ?: null]);
        if (filled($d['client_adresse'] ?? null)) {
            $client->adresse = $d['client_adresse'];
        }
        $client->save();

        $calcul = Location::calculer((float) $d['montant_journalier'], (int) $d['nombre_jours'], (float) $d['pourcentage_commission'], (float) $d['taux_change']);

        return [
            'client_id' => $client->id,
            'chauffeur_id' => $d['chauffeur_id'],
            'vehicule_id' => $d['vehicule_id'],
            'destination' => $d['destination'] ?? null,
            'date_depart' => $depart->format('Y-m-d'),
            'nombre_jours' => (int) $d['nombre_jours'],
            'date_fin' => $fin->format('Y-m-d'),
            'montant_journalier' => $d['montant_journalier'],
            'pourcentage_commission' => $d['pourcentage_commission'],
            'taux_change' => $d['taux_change'],
            'date_perception' => $d['date_perception'] ?? null,
            'observation' => $d['observation'] ?? null,
        ] + $calcul;
    }
}

<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AutreRevenuController;
use App\Http\Controllers\AvanceController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\CategorieDepenseController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PaieController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\VehiculeController;
use Illuminate\Support\Facades\Route;

// L'application démarre sur la page de connexion
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Tableau de bord (dynamique). Besoins, Rapports et Paramètres : encore des maquettes
    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');
    // Budget location (dynamique)
    Route::get('/locations/exporter', [LocationController::class, 'exporter'])->name('locations.exporter');
    Route::get('/locations/{location}/contrat', [LocationController::class, 'contrat'])->name('locations.contrat');
    Route::patch('/locations/{location}/cloturer', [LocationController::class, 'cloturer'])->name('locations.cloturer');
    Route::patch('/locations/{location}/annuler', [LocationController::class, 'annuler'])->name('locations.annuler');
    Route::resource('locations', LocationController::class)->except(['create', 'edit']);

    // Autres revenus (dynamique) : URL /autres-revenus, noms de routes revenus.*
    Route::get('/autres-revenus/exporter', [AutreRevenuController::class, 'exporter'])->name('revenus.exporter');
    Route::resource('autres-revenus', AutreRevenuController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->names('revenus')
        ->parameters(['autres-revenus' => 'revenu']);
    // Dépenses (dynamique) : chaque dépense est payée avec les recettes de location ou avec une somme reçue
    Route::post('/depenses/categories', [CategorieDepenseController::class, 'store'])->name('depenses.categories.store');
    Route::patch('/depenses/categories/{categorie}/basculer', [CategorieDepenseController::class, 'basculer'])->name('depenses.categories.basculer');
    Route::delete('/depenses/categories/{categorie}', [CategorieDepenseController::class, 'destroy'])->name('depenses.categories.destroy');
    Route::resource('depenses', DepenseController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::view('/besoins', 'besoins.index')->name('besoins.index');
    // Flotte (dynamique) : URL /flotte, noms de routes vehicules.*
    Route::patch('/flotte/{vehicule}/maintenance', [VehiculeController::class, 'maintenance'])->name('vehicules.maintenance');
    Route::resource('flotte', VehiculeController::class)
        ->except(['create', 'edit'])
        ->names('vehicules')
        ->parameters(['flotte' => 'vehicule']);
    // Employés (dynamique)
    Route::resource('employes', EmployeController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->parameters(['employes' => 'employe']);

    // Paie (dynamique)
    Route::prefix('paie')->name('paie.')->group(function () {
        Route::get('/', [PaieController::class, 'index'])->name('index');
        Route::post('/avances', [AvanceController::class, 'store'])->name('avances.store');
        Route::delete('/avances/{avance}', [AvanceController::class, 'destroy'])->name('avances.destroy');
        Route::post('/credits', [CreditController::class, 'store'])->name('credits.store');
        Route::delete('/credits/{credit}', [CreditController::class, 'destroy'])->name('credits.destroy');
        Route::post('/bulletins/generer', [BulletinController::class, 'generer'])->name('bulletins.generer');
        Route::put('/bulletins/{bulletin}/payer', [BulletinController::class, 'payer'])->name('bulletins.payer');
        Route::post('/bulletins/payer-tout', [BulletinController::class, 'payerTout'])->name('bulletins.payer-tout');
        Route::post('/employes/{employe}/payer-salaire', [BulletinController::class, 'payerEmploye'])->name('employes.payer-salaire');
        Route::get('/bulletins/imprimer', [BulletinController::class, 'imprimer'])->name('bulletins.imprimer');
    });

    // Rapports (dynamique)
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/exporter', [RapportController::class, 'exporter'])->name('rapports.exporter');
    Route::view('/parametres', 'parametres.index')->name('parametres.index');
});

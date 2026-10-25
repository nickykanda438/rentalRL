<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// L'application démarre sur la page de connexion
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Écrans (données d'exemple pour l'instant, à brancher sur les modèles)
    Route::view('/tableau-de-bord', 'dashboard')->name('dashboard');
    Route::view('/locations', 'locations.index')->name('locations.index');
    Route::view('/autres-revenus', 'revenus.index')->name('revenus.index');
    Route::view('/depenses', 'depenses.index')->name('depenses.index');
    Route::view('/besoins', 'besoins.index')->name('besoins.index');
    Route::view('/flotte', 'vehicules.index')->name('vehicules.index');
    Route::view('/employes', 'employes.index')->name('employes.index');
    Route::view('/paie', 'paie.index')->name('paie.index');
    Route::view('/rapports', 'rapports.index')->name('rapports.index');
    Route::view('/parametres', 'parametres.index')->name('parametres.index');
});

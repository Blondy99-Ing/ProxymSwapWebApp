<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapController;
use App\Http\Controllers\RavitaillementController;
use App\Http\Controllers\ControlBatterieController;



Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('home');
    })->name('home');


    // Routes AJAX (ou API internes)
    Route::get('/swaps', [\App\Http\Controllers\SwapController::class, 'index'])->name('swap.index');
    Route::get('/swap/agents/{stationId}', [\App\Http\Controllers\SwapController::class, 'getAgentsByStation']);
    Route::get('/swap/batteries/{stationId}', [\App\Http\Controllers\SwapController::class, 'getBatteriesByStation']);
    Route::get('/swap/chauffeur/{partialMac}', [\App\Http\Controllers\SwapController::class, 'getChauffeurByBattery']);

    // 🔸 Nouveau: prix dynamique backend
    Route::post('/swap/prix', [\App\Http\Controllers\SwapController::class, 'getSwapPrice']);

    // Enregistrement du swap (JSON)
    Route::post('/swap', [\App\Http\Controllers\SwapController::class, 'faireSwap']);


        
    // Vue principale (page front-end)
    Route::get('/swap/historique/employe/{employeeId}', [SwapController::class, 'historiqueEmployeView'])->name('swap.historique.employe.view');

    // API des données filtrées
    Route::get('/swap/historique/employe/data/{employeeId}', [SwapController::class, 'getHistoriqueEmployeData'])->name('swap.historique.employe.data');



    //ravitaillement
    Route::get('/ravitaillements', [\App\Http\Controllers\RavitaillementController::class, 'index'])->name('ravitaillement.index');
    
    //api ravitaillement
    Route::get('/ravitaillement/batteries', [RavitaillementController::class, 'getAllBatteries']);
    Route::get('/ravitaillement/batteries/{id}', [RavitaillementController::class, 'getBatteriesByAgence']);
    Route::post('/ravitaillement', [RavitaillementController::class, 'store']);




 



// Route principale qui charge la page HTML/Blade
Route::get('/batteries', [ControlBatterieController::class, 'index'])->name('batteries.control');

// ===========================================================
// API (Lecture des statuts)
// ===========================================================

// 1. OPTIMISATION : Route pour récupérer TOUS les statuts en une seule requête (Batch)
// C'est celle qui sera utilisée au chargement initial de la page par le JavaScript.
Route::get('/batteries/api/status/all', [ControlBatterieController::class, 'getAllBatteriesStatus']);

// 2. Route pour récupérer le statut d'une SEULE batterie (Utilisée après une commande pour rafraîchir la carte)
Route::get('/batteries/api/status/{macId}', [ControlBatterieController::class, 'getBatteryStatus']);

// ===========================================================
// API (Commande)
// ===========================================================

// Route pour envoyer les commandes ON/OFF (Charge ou Décharge)
// Le MAC ID et l'action sont passés dans le corps de la requête (POST)
Route::post('/batteries/api/command', [ControlBatterieController::class, 'sendBmsCommand']);


});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

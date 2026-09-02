<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Learner\LearnerDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site public (vitrine)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Redirection après connexion — selon le rôle
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'learner.dashboard');
})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Espace apprenant
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('mon-espace')->name('learner.')->group(function () {
    Route::get('/', [LearnerDashboardController::class, 'index'])->name('dashboard');
    // Phase 4 : catalogue, lecteur de formation, progression
    // Phase 5 : paiement
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    // Phase 3 : contenu du site, formations, marketplace, boutique, événements, communauté
    // Phase 5 : paiements à vérifier, commandes
    // Phase 6 : membres
});

/*
|--------------------------------------------------------------------------
| Compte (Breeze)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

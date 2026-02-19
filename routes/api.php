<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PsychologueController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\DisponibiliteController;

// Routes publiques
Route::post('/register', [AuthController::class, 'registerEtudiant']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Psychologues (accessible à tous)
    Route::get('/psychologues', [PsychologueController::class, 'index']);
    Route::get('/psychologues/disponibles', [PsychologueController::class, 'disponibles']);
    Route::get('/psychologues/urgence', [PsychologueController::class, 'urgence']);
    Route::get('/psychologues/{id}', [PsychologueController::class, 'show']);

    // Disponibilités (Psychologue uniquement)
    Route::middleware('check.role:psychologue')->group(function () {
        Route::get('/disponibilites/mes-disponibilites', [DisponibiliteController::class, 'mesDisponibilites']);
        Route::post('/disponibilites', [DisponibiliteController::class, 'store']);
        Route::put('/disponibilites/{id}/toggle', [DisponibiliteController::class, 'toggle']);
        Route::delete('/disponibilites/{id}', [DisponibiliteController::class, 'destroy']);
    });
    
    // Disponibilités (accessibles à tous)
    Route::get('/psychologues/{psychologue_id}/disponibilites', [DisponibiliteController::class, 'index']);
    Route::get('/psychologues/{psychologue_id}/creneaux/{date}', [DisponibiliteController::class, 'creneauxDisponibles']);

    // Consultations
    Route::post('/consultations', [ConsultationController::class, 'store']);
    Route::get('/consultations/historique/etudiant', [ConsultationController::class, 'historiqueEtudiant']);
    Route::get('/consultations/historique/psychologue', [ConsultationController::class, 'historiquePsychologue']);
    Route::get('/consultations/demandes-en-attente', [ConsultationController::class, 'demandesEnAttente']);
    Route::get('/consultations/acceptees', [ConsultationController::class, 'consultationsAcceptees']);
    
    Route::put('/consultations/{id}/accepter', [ConsultationController::class, 'accepter']);
    Route::put('/consultations/{id}/refuser', [ConsultationController::class, 'refuser']);
    Route::put('/consultations/{id}/demarrer', [ConsultationController::class, 'demarrer']);
    Route::put('/consultations/{id}/terminer', [ConsultationController::class, 'terminer']);

    // Admin uniquement
    Route::middleware('check.role:admin')->group(function () {
        Route::post('/psychologues', [PsychologueController::class, 'store']);
        Route::put('/psychologues/{id}/disponibilite', [PsychologueController::class, 'updateDisponibilite']);
    });
});

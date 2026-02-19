<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Psychologue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PsychologueController extends Controller
{
    // Liste des psychologues (pour étudiants - RDV planifiés)
    public function index(Request $request)
    {
        try {
            $psychologues = User::where('role', 'psychologue')
                ->where('statut', 'actif')
                ->with('psychologue')
                ->get()
                ->filter(function($user) {
                    return $user->psychologue !== null;
                })
                ->values();

            return response()->json([
                'success' => true,
                'psychologues' => $psychologues
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des psychologues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Psychologues disponibles (pour consultations directes)
    public function disponibles()
    {
        try {
            $psychologues = User::where('role', 'psychologue')
                ->where('statut', 'actif')
                ->whereHas('psychologue', function($query) {
                    $query->where('disponible', true);
                })
                ->with('psychologue')
                ->get();

            return response()->json([
                'success' => true,
                'psychologues' => $psychologues
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des psychologues disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Psychologues pour urgences
    public function urgence()
    {
        try {
            $psychologues = User::where('role', 'psychologue')
                ->where('statut', 'actif')
                ->whereHas('psychologue', function($query) {
                    $query->where('disponible', true)
                          ->where('urgence', true);
                })
                ->with('psychologue')
                ->get();

            return response()->json([
                'success' => true,
                'psychologues' => $psychologues
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des psychologues d\'urgence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Détail d'un psychologue
    public function show($id)
    {
        try {
            $psychologue = User::where('role', 'psychologue')
                ->with(['psychologue', 'psychologue.disponibilites'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'psychologue' => $psychologue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Psychologue non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // Créer un psychologue (Admin uniquement)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'numero_ordre' => 'nullable|string|max:100',
            'specialites' => 'nullable|array',
            'bio' => 'nullable|string',
            'annees_experience' => 'nullable|integer',
            'diplome' => 'nullable|string',
            'etablissement' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'psychologue',
                'statut' => 'actif',
                'telephone' => $request->telephone,
            ]);

            // Créer le profil psychologue
            $psychologue = Psychologue::create([
                'user_id' => $user->id,
                'numero_ordre' => $request->numero_ordre,
                'specialites' => $request->specialites,
                'bio' => $request->bio,
                'annees_experience' => $request->annees_experience ?? 0,
                'diplome' => $request->diplome,
                'etablissement' => $request->etablissement,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Psychologue créé avec succès',
                'psychologue' => $user->load('psychologue')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du psychologue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mettre à jour la disponibilité (Psychologue lui-même)
    public function updateDisponibilite(Request $request)
    {
        $user = $request->user();

        if (!$user->isPsychologue()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'disponible' => 'required|boolean',
            'urgence' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!$user->psychologue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil psychologue non trouvé'
                ], 404);
            }

            $user->psychologue->update([
                'disponible' => $request->disponible,
                'urgence' => $request->urgence ?? $user->psychologue->urgence,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Disponibilité mise à jour',
                'psychologue' => $user->fresh('psychologue')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

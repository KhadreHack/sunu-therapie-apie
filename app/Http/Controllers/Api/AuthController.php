<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Etudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Inscription Étudiant
    public function registerEtudiant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'numero_etudiant' => 'nullable|string|max:50',
            'universite' => 'nullable|string|max:255',
            'faculte' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Créer l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'etudiant',
            'statut' => 'actif', // Actif directement pour l'instant
            'telephone' => $request->telephone,
        ]);

        // Créer le profil étudiant
        $etudiant = Etudiant::create([
            'user_id' => $user->id,
            'numero_etudiant' => $request->numero_etudiant,
            'universite' => $request->universite,
            'faculte' => $request->faculte,
            'niveau' => $request->niveau,
        ]);

        // Générer le token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => $user->load('etudiant'),
            'token' => $token,
        ], 201);
    }

    // Connexion (Étudiant et Psychologue)
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        // Vérifier le statut
        if ($user->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte n\'est pas actif'
            ], 403);
        }

        // Charger les relations selon le rôle
        if ($user->role === 'psychologue') {
            $user->load('psychologue');
        } elseif ($user->role === 'etudiant') {
            $user->load('etudiant');
        }

        // Générer le token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Déconnexion
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    // Profil utilisateur connecté
    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'psychologue') {
            $user->load('psychologue');
        } elseif ($user->role === 'etudiant') {
            $user->load('etudiant');
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    // Mettre à jour le profil
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'telephone', 'photo']));

        // Mise à jour spécifique selon le rôle
        if ($user->role === 'etudiant' && $user->etudiant) {
            $user->etudiant->update($request->only([
                'numero_etudiant',
                'universite',
                'faculte',
                'niveau',
                'date_naissance',
                'genre',
                'ville'
            ]));
        }

        if ($user->role === 'psychologue' && $user->psychologue) {
            $user->psychologue->update($request->only([
                'bio',
                'specialites',
                'annees_experience',
                'diplome',
                'etablissement'
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour',
            'user' => $user->fresh()->load($user->role)
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConsultationController extends Controller
{
    // Créer une consultation
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'psychologue_id' => 'required|exists:users,id',
            'type' => 'required|in:directe,planifiee,urgence,anonyme',
            'mode' => 'required|in:video,audio,chat',
            'video_active' => 'boolean',
            'date_consultation' => 'required|date',
            'motif_consultation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::create([
                'etudiant_id' => $request->user()->id,
                'psychologue_id' => $request->psychologue_id,
                'type' => $request->type,
                'mode' => $request->mode,
                'video_active' => $request->video_active ?? true,
                'statut' => 'en_attente',
                'date_consultation' => $request->date_consultation,
                'motif_consultation' => $request->motif_consultation,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande de consultation envoyée',
                'consultation' => $consultation->load(['etudiant', 'psychologue'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Accepter une consultation (PSYCHOLOGUE)
    public function accepter(Request $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            // Vérifier que c'est bien le psychologue concerné
            if ($consultation->psychologue_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            // Générer un nom de canal Agora unique
            $channelName = 'sunu_' . $consultation->id . '_' . time() . '_' . Str::random(6);

            $consultation->update([
                'statut' => 'acceptee',
                'agora_channel_name' => $channelName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consultation acceptée',
                'consultation' => $consultation->load(['etudiant', 'psychologue']),
                'channel_name' => $channelName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'acceptation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Refuser une consultation (PSYCHOLOGUE)
    public function refuser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motif_refus' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultation::findOrFail($id);

            if ($consultation->psychologue_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            $consultation->update([
                'statut' => 'refusee',
                'motif_refus' => $request->motif_refus,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consultation refusée',
                'consultation' => $consultation->load(['etudiant', 'psychologue'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du refus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Démarrer une consultation
    public function demarrer(Request $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            // Vérifier que l'utilisateur est concerné (étudiant OU psychologue)
            $userId = $request->user()->id;
            if ($consultation->etudiant_id !== $userId && $consultation->psychologue_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            // Mettre à jour le statut
            if ($consultation->statut === 'acceptee') {
                $consultation->update([
                    'statut' => 'en_cours',
                    'date_debut' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Consultation démarrée',
                'consultation' => $consultation->load(['etudiant', 'psychologue'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du démarrage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Terminer une consultation
    public function terminer(Request $request, $id)
    {
        try {
            $consultation = Consultation::findOrFail($id);

            $userId = $request->user()->id;
            if ($consultation->etudiant_id !== $userId && $consultation->psychologue_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé'
                ], 403);
            }

            $dateFin = now();
            $dureeMinutes = null;

            if ($consultation->date_debut) {
                $dureeMinutes = $consultation->date_debut->diffInMinutes($dateFin);
            }

            $consultation->update([
                'statut' => 'terminee',
                'date_fin' => $dateFin,
                'duree_minutes' => $dureeMinutes,
            ]);

            // Incrémenter le compteur du psychologue
            $psychologue = $consultation->psychologue->psychologue;
            if ($psychologue) {
                $psychologue->increment('total_consultations');
            }

            return response()->json([
                'success' => true,
                'message' => 'Consultation terminée',
                'consultation' => $consultation->load(['etudiant', 'psychologue'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Historique étudiant
    public function historiqueEtudiant(Request $request)
    {
        try {
            $consultations = Consultation::where('etudiant_id', $request->user()->id)
                ->with('psychologue.psychologue')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'consultations' => $consultations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Historique psychologue
    public function historiquePsychologue(Request $request)
    {
        try {
            $consultations = Consultation::where('psychologue_id', $request->user()->id)
                ->with('etudiant.etudiant')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'consultations' => $consultations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Demandes en attente (PSYCHOLOGUE)
    public function demandesEnAttente(Request $request)
    {
        try {
            $demandes = Consultation::where('psychologue_id', $request->user()->id)
                ->where('statut', 'en_attente')
                ->with('etudiant.etudiant')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'demandes' => $demandes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Consultations acceptées (PSYCHOLOGUE)
    public function consultationsAcceptees(Request $request)
    {
        try {
            $consultations = Consultation::where('psychologue_id', $request->user()->id)
                ->where('statut', 'acceptee')
                ->with('etudiant.etudiant')
                ->orderBy('date_consultation', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'consultations' => $consultations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

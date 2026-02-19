<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disponibilite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DisponibiliteController extends Controller
{
    // Ajouter une disponibilité (Psychologue)
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isPsychologue()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'jour_semaine' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $disponibilite = Disponibilite::create([
            'psychologue_id' => $user->id,
            'jour_semaine' => $request->jour_semaine,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'actif' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disponibilité ajoutée',
            'disponibilite' => $disponibilite
        ], 201);
    }

    // Liste des disponibilités d'un psychologue
    public function index(Request $request, $psychologue_id)
    {
        $disponibilites = Disponibilite::where('psychologue_id', $psychologue_id)
            ->where('actif', true)
            ->orderByRaw("FIELD(jour_semaine, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche')")
            ->orderBy('heure_debut')
            ->get();

        return response()->json([
            'success' => true,
            'disponibilites' => $disponibilites
        ]);
    }

    // Mes disponibilités (Psychologue connecté)
    public function mesDisponibilites(Request $request)
    {
        $user = $request->user();

        if (!$user->isPsychologue()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $disponibilites = Disponibilite::where('psychologue_id', $user->id)
            ->orderByRaw("FIELD(jour_semaine, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche')")
            ->orderBy('heure_debut')
            ->get();

        return response()->json([
            'success' => true,
            'disponibilites' => $disponibilites
        ]);
    }

    // Supprimer une disponibilité
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isPsychologue()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $disponibilite = Disponibilite::findOrFail($id);

        if ($disponibilite->psychologue_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette disponibilité ne vous appartient pas'
            ], 403);
        }

        $disponibilite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilité supprimée'
        ]);
    }

    // Activer/Désactiver une disponibilité
    public function toggle(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isPsychologue()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $disponibilite = Disponibilite::findOrFail($id);

        if ($disponibilite->psychologue_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette disponibilité ne vous appartient pas'
            ], 403);
        }

        $disponibilite->update([
            'actif' => !$disponibilite->actif
        ]);

        return response()->json([
            'success' => true,
            'message' => $disponibilite->actif ? 'Disponibilité activée' : 'Disponibilité désactivée',
            'disponibilite' => $disponibilite
        ]);
    }

    // Créneaux disponibles pour un psychologue (pour les RDV)
    public function creneauxDisponibles(Request $request, $psychologue_id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $date = $request->date ? Carbon::parse($request->date) : Carbon::now();
        $jourSemaine = $this->getJourSemaineFrancais($date->dayOfWeek);

        // Récupérer les disponibilités du jour
        $disponibilites = Disponibilite::where('psychologue_id', $psychologue_id)
            ->where('jour_semaine', $jourSemaine)
            ->where('actif', true)
            ->get();

        $creneaux = [];

        foreach ($disponibilites as $dispo) {
            $debut = Carbon::parse($date->format('Y-m-d') . ' ' . $dispo->heure_debut);
            $fin = Carbon::parse($date->format('Y-m-d') . ' ' . $dispo->heure_fin);

            // Créneaux de 30 minutes
            while ($debut->lt($fin)) {
                $creneauFin = $debut->copy()->addMinutes(30);
                
                if ($creneauFin->lte($fin)) {
                    $creneaux[] = [
                        'debut' => $debut->format('H:i'),
                        'fin' => $creneauFin->format('H:i'),
                        'datetime' => $debut->format('Y-m-d H:i:s'),
                    ];
                }
                
                $debut->addMinutes(30);
            }
        }

        return response()->json([
            'success' => true,
            'date' => $date->format('Y-m-d'),
            'jour' => $jourSemaine,
            'creneaux' => $creneaux
        ]);
    }

    private function getJourSemaineFrancais($dayOfWeek)
    {
        $jours = [
            0 => 'dimanche',
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
        ];

        return $jours[$dayOfWeek];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PropertyPerson;
use App\Models\PropertyOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyRelationController extends Controller
{
    // ========== PERSONNES IMPLIQUÉES ==========
    
    /**
     * Récupérer toutes les personnes pour une propriété
     */
    public function getPersons(Request $request, $listingId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            $persons = PropertyPerson::where('user_id', $user->id)
                ->where('property_listing_id', $listingId)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'success' => true,
                'persons' => $persons
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des personnes'
            ], 500);
        }
    }

    /**
     * Ajouter une personne à une propriété
     */
    public function addPerson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_location' => 'required|string',
            'property_listing_id' => 'required|string',
            'contact_id' => 'required|string',
            'name' => 'required|string',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'implication' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            
            $person = PropertyPerson::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'property_listing_id' => $request->property_listing_id,
                    'contact_id' => $request->contact_id
                ],
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'implication' => $request->implication,
                ]
            );
            return response()->json([
                'success' => true,
                'message' => 'Personne ajoutée avec succès',
                'person' => $person
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la personne: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour l'implication d'une personne
     */
    public function updatePersonRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_location' => 'required|string',
            'implication' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            
            $person = PropertyPerson::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $person->implication = $request->implication;
            $person->save();
            return response()->json([
                'success' => true,
                'message' => 'Implication mise à jour avec succès',
                'person' => $person
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'implication: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une personne
     */
    public function removePerson(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            
            $person = PropertyPerson::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $person->delete();
            return response()->json([
                'success' => true,
                'message' => 'Personne supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la personne'
            ], 500);
        }
    }

    // ========== OPPORTUNITÉS LIÉES ==========

    /**
     * Récupérer toutes les opportunités pour une propriété
     */
    public function getOpportunities(Request $request, $listingId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            $opportunities = PropertyOpportunity::where('user_id', $user->id)
                ->where('property_listing_id', $listingId)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'success' => true,
                'opportunities' => $opportunities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des opportunités'
            ], 500);
        }
    }

    /**
     * Ajouter une opportunité à une propriété
     */
    public function addOpportunity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_location' => 'required|string',
            'property_listing_id' => 'required|string',
            'opportunity_id' => 'required|string',
            'name' => 'required|string',
            'pipeline_id' => 'nullable|string',
            'pipeline_stage_id' => 'nullable|string',
            'source' => 'nullable|string',
            'status' => 'nullable|string',
            'monetary_value' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            
            $opportunity = PropertyOpportunity::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'property_listing_id' => $request->property_listing_id,
                    'opportunity_id' => $request->opportunity_id
                ],
                [
                    'name' => $request->name,
                    'pipeline_id' => $request->pipeline_id,
                    'pipeline_stage_id' => $request->pipeline_stage_id,
                    'source' => $request->source,
                    'status' => $request->status,
                    'monetary_value' => $request->monetary_value,
                ]
            );
            return response()->json([
                'success' => true,
                'message' => 'Opportunité ajoutée avec succès',
                'opportunity' => $opportunity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'opportunité'
            ], 500);
        }
    }

    /**
     * Supprimer une opportunité
     */
    public function removeOpportunity(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non connecté'
                ], 400);
            }
            
            $opportunity = PropertyOpportunity::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            $opportunity->delete();
            return response()->json([
                'success' => true,
                'message' => 'Opportunité supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'opportunité: ' . $e->getMessage()
            ], 500);
        }
    }
}

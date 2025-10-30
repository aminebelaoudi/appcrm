<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GhlAuthController extends Controller
{
    // Redirige l'utilisateur vers la page d'auth GHL
    public function redirectToGhl(Request $request)
    {
        $clientId = env('GHL_CLIENT_ID');
        $redirectUri = env('GHL_REDIRECT_URI');
        $state = csrf_token();
        $scope = 'contacts.readonly opportunities.readonly'; // adapte selon besoin
        $authUrl = 'https://marketplace.gohighlevel.com/oauth/chooselocation';

        $url = $authUrl . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => $state,
        ]);
        return redirect($url);
    }

    // Callback GHL : échange le code contre un token et le stocke
    public function handleGhlCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        
        if (!$code) {
            return view('ghl.oauth-status', [
                'success' => false,
                'message' => 'Code d\'autorisation manquant',
                'error' => 'Le code d\'autorisation n\'a pas été fourni par GHL'
            ]);
        }

        $clientId = env('GHL_CLIENT_ID');
        $clientSecret = env('GHL_CLIENT_SECRET');
        $redirectUri = env('GHL_REDIRECT_URI');

        // Échange le code contre un access_token
        $response = Http::asForm()->post('https://services.leadconnectorhq.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        if (!$response->ok()) {
            Log::error('GHL OAuth error', ['response' => $response->body()]);
            return view('ghl.oauth-status', [
                'success' => false,
                'message' => 'Erreur lors de l\'authentification',
                'error' => 'Impossible de récupérer le token d\'accès. Status: ' . $response->status()
            ]);
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? null;
        
        // Pour une installation Company, utiliser userId comme identifiant
        $userId = $data['userId'] ?? null;
        $companyId = $data['companyId'] ?? null;
        $userType = $data['userType'] ?? 'User';

        if (!$accessToken || !$userId) {
            Log::error('GHL OAuth incomplete data - missing required fields');
            return view('ghl.oauth-status', [
                'success' => false,
                'message' => 'Données OAuth incomplètes',
                'error' => 'Les informations d\'authentification sont incomplètes'
            ]);
        }

        // Trouver ou créer l'utilisateur basé sur userId
        $user = \App\Models\User::where('id_location', $userId)->first();
        
        try {
            if (!$user) {
                // Créer un nouvel utilisateur pour ce userId
                $user = \App\Models\User::create([
                    'name' => 'GHL User ' . \Illuminate\Support\Str::random(8),
                    'id_location' => $userId,
                    'ghl_location_id' => $userId,
                    'ghl_access_token' => $accessToken,
                    'ghl_refresh_token' => $refreshToken,
                    'ghl_token_expires_at' => now()->addSeconds($expiresIn),
                ]);
                
                Log::info('New GHL user created successfully');
            } else {
                // Mettre à jour les tokens de l'utilisateur existant
                $user->ghl_access_token = $accessToken;
                $user->ghl_refresh_token = $refreshToken;
                $user->ghl_token_expires_at = now()->addSeconds($expiresIn);
                $user->ghl_location_id = $userId;
                $user->save();
                
                Log::info('GHL user tokens updated successfully');
            }

            // Connecter automatiquement l'utilisateur
            Auth::login($user);

            return view('ghl.oauth-status', [
                'success' => true,
                'message' => 'Connexion établie avec succès',
                'locationId' => $userId,
                'expiresAt' => now()->addSeconds($expiresIn)->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('GHL user creation/update error: ' . $e->getMessage());
            
            return view('ghl.oauth-status', [
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde des données',
                'error' => 'Une erreur technique est survenue. Veuillez réessayer.'
            ]);
        }
    }
}

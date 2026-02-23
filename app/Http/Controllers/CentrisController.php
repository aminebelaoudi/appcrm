<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PropertyPerson;
use App\Models\PropertyOpportunity;
use App\User;


class CentrisController extends Controller
{
    /**
     * Obtenir un token d'accès pour une location spécifique
     */
    private function getLocationToken($locationId)
    {
        $companyId = env('companyId');
        
        // Trouver l'utilisateur company pour récupérer son access_token
        $companyUser = User::where('id_location', $companyId)->first();
        
        if (!$companyUser || !$companyUser->ghl_access_token) {
            Log::error('Company user not found or missing access token', ['companyId' => $companyId]);
            return null;
        }
        
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Version' => '2021-07-28',
                    'Authorization' => 'Bearer ' . $companyUser->ghl_access_token
                ])
                ->post('https://services.leadconnectorhq.com/oauth/locationToken', [
                    'companyId' => $companyId,
                    'locationId' => $locationId
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Location token obtained successfully', [
                    'locationId' => $locationId,
                    'has_access_token' => !empty($data['access_token'])
                ]);
                
                return $data;
            } else {
                Log::error('Failed to get location token', [
                    'locationId' => $locationId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception getting location token', [
                'locationId' => $locationId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    public function showProperties()
    {
        // Permettre l'affichage dans un iframe depuis n'importe quel domaine
        header('X-Frame-Options: ALLOWALL');
        header('Content-Security-Policy: frame-ancestors *');

    // Vérifier si un locationId est fourni dans l'URL
    $locationId = request()->query('locationId');
    // Code de bureau (OfficeKey) reçu en paramètre de l'URL et à persister
    $cbParam = request()->query('cb');
        
        if ($locationId) {
            // Rechercher l'utilisateur avec cet id_location dans la BD
            $user = User::where('id_location', $locationId)->first();
            
            // Si l'utilisateur n'existe pas, essayer de créer automatiquement via l'API locationToken
            if (!$user) {
                Log::info('User not found for locationId, attempting to create', ['locationId' => $locationId]);
                
                $tokenData = $this->getLocationToken($locationId);
                
                if ($tokenData && isset($tokenData['access_token'])) {
                    // Créer le nouvel utilisateur avec les tokens de la location
                    try {
                        $user = User::create([
                            'name' => 'Location ' . $locationId,
                            'id_location' => $locationId,
                            'ghl_access_token' => $tokenData['access_token'],
                            'ghl_refresh_token' => $tokenData['refresh_token'] ?? null,
                            'ghl_token_expires_at' => isset($tokenData['expires_in']) 
                                ? now()->addSeconds($tokenData['expires_in']) 
                                : null,
                            // Stocker le Codebureau si fourni dans l'URL
                            'Codebureau' => $cbParam ?? null,
                        ]);
                        
                        Log::info('New location user created successfully', ['locationId' => $locationId]);
                    } catch (\Exception $e) {
                        Log::error('Failed to create location user', [
                            'locationId' => $locationId,
                            'error' => $e->getMessage()
                        ]);
                        return view('centris.no-access', [
                            'message' => "Erreur lors de la création de l'accès pour cette location."
                        ]);
                    }
                } else {
                    // Impossible d'obtenir le token pour cette location
                    return view('centris.no-access', [
                        'message' => "Vous n'avez pas accès à ces propriétés. Location introuvable."
                    ]);
                }
            }
            
            // Vérifier que l'utilisateur a les credentials GHL nécessaires
            if (!$user->ghl_access_token) {
                return view('centris.no-access', ['message' => "Configuration GHL manquante pour cet emplacement."]);
            }
            
            // Connecter automatiquement l'utilisateur basé sur le locationId
            Auth::login($user);
            Log::info('User auto-logged in', ['locationId' => $locationId, 'userId' => $user->id]);
        } else {
            // Si pas de locationId, ne rien afficher
            return view('centris.no-access', ['message' => "Aucun identifiant d'emplacement fourni."]);
        }
        // Utiliser le Codebureau de l'utilisateur (lié au locationId) ou celui fourni dans l'URL (cb)
        $agentKey = $cbParam ?: ($user->Codebureau ?? null);
        // Si un cb est fourni et diffère de la valeur stockée, la persister
        if (!empty($cbParam) && $user->Codebureau !== $cbParam) {
            try {
                $user->Codebureau = $cbParam;
                $user->save();
                Log::info('Codebureau updated from URL parameter', ['locationId' => $locationId, 'cb' => $cbParam]);
            } catch (\Exception $e) {
                Log::warning('Failed to persist Codebureau from URL parameter', ['error' => $e->getMessage()]);
            }
        }
        if (empty($agentKey)) {
            Log::warning('Codebureau manquant pour cet utilisateur/location', [
                'locationId' => $locationId,
                'userId' => $user->id ?? null
            ]);
            return view('centris.no-access', [
                'message' => "Codebureau non configuré pour cet emplacement."
            ]);
        }
        // Filtre courtier (MemberKey) optionnel depuis la requête
        $selectedMemberKey = request()->get('memberKey');
        $search = trim((string) request()->get('search', ''));

        $perPage = 12;
        $page = request()->get('page', 1);

        // Clé API Centris
        $apiKey = env('CENTRIS_API_KEY');

        // Récupérer et mettre en cache la liste des courtiers (Members) de ce Codebureau
        $brokers = Cache::remember("centris_members_{$agentKey}", 3600, function() use ($agentKey, $apiKey) {
            $membersUrl = "https://datadistributionqc.centris.ca/v1/odata/Member?\$filter=OfficeKey eq '$agentKey'";
            $membersResponse = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($membersUrl);
            $members = $membersResponse->json()['value'] ?? [];
            // Garder seulement les champs nécessaires
            return array_map(function($m) {
                return [
                    'MemberKey' => $m['MemberKey'] ?? null,
                    'MemberFullName' => $m['MemberFullName'] ?? 'Sans nom',
                ];
            }, $members);
        });

        // Cache des propriétés (30 min). Clé différente si un courtier est sélectionné
        $propertiesCacheKey = $selectedMemberKey
            ? "centris_properties_member_{$selectedMemberKey}"
            : "centris_properties_office_{$agentKey}";

        $allProperties = Cache::remember($propertiesCacheKey, 1800, function() use ($agentKey, $selectedMemberKey, $apiKey) {
            // Construire le filtre selon la sélection
            if (!empty($selectedMemberKey)) {
                $url = "https://datadistributionqc.centris.ca/v1/odata/Property?\$filter=ListAgentKey eq '$selectedMemberKey'&\$count=true";
            } else {
                $url = "https://datadistributionqc.centris.ca/v1/odata/Property?\$filter=ListOfficeKey eq '$agentKey'&\$count=true";
            }
            $response = Http::timeout(120)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($url);
            return $response->json()['value'] ?? [];
        });

        if ($search !== '') {
            $searchLower = function_exists('mb_strtolower') ? mb_strtolower($search) : strtolower($search);
            $allProperties = array_values(array_filter($allProperties, function($property) use ($searchLower) {
                $parts = [];

                if (!empty($property['StreetNumberStart'])) {
                    $streetNumber = $property['StreetNumberStart'];
                    if (!empty($property['StreetNumberEnd'])) {
                        $streetNumber .= ' ' . $property['StreetNumberEnd'];
                    }
                    $parts[] = $streetNumber;
                }
                if (!empty($property['StreetShortName'])) {
                    $parts[] = $property['StreetShortName'];
                }
                if (!empty($property['Township'])) {
                    $parts[] = $property['Township'];
                }
                if (!empty($property['PostalCode'])) {
                    $parts[] = $property['PostalCode'];
                }
                if (!empty($property['MlsNumber'])) {
                    $parts[] = $property['MlsNumber'];
                }
                if (!empty($property['ListingId'])) {
                    $parts[] = $property['ListingId'];
                }
                if (!empty($property['ListingKey'])) {
                    $parts[] = $property['ListingKey'];
                }

                $haystack = implode(' ', $parts);
                $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);

                return $haystack !== '' && strpos($haystack, $searchLower) !== false;
            }));
        }

        $totalCount = count($allProperties);
        $offset = ($page - 1) * $perPage;
        $properties = array_slice($allProperties, $offset, $perPage);

        $listingKeys = array_column($properties, 'ListingKey');
    // $apiKey déjà défini plus haut

        if (!empty($listingKeys)) {
            $chunks = array_chunk($listingKeys, 3);
            $allMedia = [];
            foreach ($chunks as $chunk) {
                $chunkCacheKey = 'centris_media_in3_' . md5(implode('_', $chunk));
                $chunkMedia = Cache::remember($chunkCacheKey, 600, function() use ($chunk, $apiKey) {
                    $keysString = "'" . implode("','", $chunk) . "'";
                    $mediaUrl = "https://datadistributionqc.centris.ca/v1/odata/Media?\$filter=ResourceRecordKey in ($keysString) and MediaCategory eq 'Photo'";
                    $mediaResponse = Http::timeout(30)->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept' => 'application/json',
                    ])->get($mediaUrl);
                    return $mediaResponse->json()['value'] ?? [];
                });
                $allMedia = array_merge($allMedia, $chunkMedia);
            }
            $mediaByProperty = [];
            foreach ($allMedia as $media) {
                $key = $media['ResourceRecordKey'];
                if (!isset($mediaByProperty[$key])) {
                    $mediaByProperty[$key] = [];
                }
                $mediaByProperty[$key][] = $media;
            }
            foreach ($mediaByProperty as &$mediaArray) {
                usort($mediaArray, function($a, $b) {
                    return ($a['Order'] ?? 999) <=> ($b['Order'] ?? 999);
                });
            }
            foreach ($properties as &$property) {
                $mediaList = $mediaByProperty[$property['ListingKey']] ?? [];
                $property['Media'] = !empty($mediaList) ? [array_shift($mediaList)] : [];
            }
        }

    // Utiliser l'utilisateur connecté
    $userId = $user ? $user->id : null;

        // Optimisation: charger tous les comptages en une seule requête
        if ($userId) {
            $listingIds = array_column($properties, 'ListingId');
            
            $personsCounts = PropertyPerson::where('user_id', $userId)
                ->whereIn('property_listing_id', $listingIds)
                ->select('property_listing_id', \DB::raw('count(*) as total'))
                ->groupBy('property_listing_id')
                ->pluck('total', 'property_listing_id')
                ->toArray();
            
            $opportunitiesCounts = PropertyOpportunity::where('user_id', $userId)
                ->whereIn('property_listing_id', $listingIds)
                ->select('property_listing_id', \DB::raw('count(*) as total'))
                ->groupBy('property_listing_id')
                ->pluck('total', 'property_listing_id')
                ->toArray();
            
            foreach ($properties as &$property) {
                $property['PersonsCount'] = $personsCounts[$property['ListingId']] ?? 0;
                $property['OpportunitiesCount'] = $opportunitiesCounts[$property['ListingId']] ?? 0;
            }
        } else {
            foreach ($properties as &$property) {
                $property['PersonsCount'] = 0;
                $property['OpportunitiesCount'] = 0;
            }
        }

        $totalPages = ceil($totalCount / $perPage);
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ];

    return view('centris.properties', compact('properties', 'pagination', 'locationId', 'brokers', 'selectedMemberKey', 'agentKey', 'search'));
    }

    public function showPropertyDetails($listingKey)
    {
        // Permettre l'affichage dans un iframe depuis n'importe quel domaine
        header('X-Frame-Options: ALLOWALL');
        header('Content-Security-Policy: frame-ancestors *');

        // Vérifier si un locationId est fourni dans l'URL
        $locationId = request()->query('locationId');
        
        if ($locationId) {
            // Rechercher l'utilisateur avec cet id_location dans la BD
            $user = User::where('id_location', $locationId)->first();
            
            // Si l'utilisateur n'existe pas, afficher un message d'accès refusé
            if (!$user) {
                return view('centris.no-access', ['message' => "Vous n'avez pas accès à cette propriété."]);
            }
            
            // Vérifier que l'utilisateur a les credentials GHL nécessaires
            if (!$user->ghl_access_token) {
                return view('centris.no-access', ['message' => "Configuration GHL manquante pour cet emplacement."]);
            }
            
            // Connecter automatiquement l'utilisateur basé sur le locationId
            Auth::login($user);
            Log::info('User auto-logged in for property details', ['locationId' => $locationId, 'userId' => $user->id]);
        } else {
            // Si pas de locationId, ne rien afficher
            return view('centris.no-access', ['message' => "Aucun identifiant d'emplacement fourni."]);
        }
        $apiKey = env('CENTRIS_API_KEY');

        // Récupérer les détails de la propriété
        $propertyUrl = "https://datadistributionqc.centris.ca/v1/odata/Property?\$filter=ListingKey eq '$listingKey'";
        $propertyResponse = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->get($propertyUrl);
        $property = $propertyResponse->json()['value'][0] ?? null;
        if (!$property) {
            abort(404, 'Propriété non trouvée');
        }

        // Récupérer les médias et garder seulement la première photo
        $mediaUrl = "https://datadistributionqc.centris.ca/v1/odata/Media?\$filter=ResourceRecordKey eq '$listingKey' and MediaCategory eq 'Photo'";
        $mediaResponse = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->get($mediaUrl);
        $allMedia = $mediaResponse->json()['value'] ?? [];
        if (!empty($allMedia)) {
            usort($allMedia, function($a, $b) {
                return ($a['Order'] ?? 999) <=> ($b['Order'] ?? 999);
            });
            $property['Media'] = [array_shift($allMedia)];
        } else {
            $property['Media'] = [];
        }

    // Utiliser l'utilisateur connecté
    $userId = $user ? $user->id : null;

        // Charger les personnes et opportunités depuis la base de données
        $persons = $userId ? PropertyPerson::where('user_id', $userId)
            ->where('property_listing_id', $property['ListingId'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($person) {
                return [
                    'id' => $person->id,
                    'contactId' => $person->contact_id,
                    'name' => $person->name,
                    'email' => $person->email,
                    'phone' => $person->phone,
                    'role' => $person->implication,
                ];
            })
            ->toArray() : [];

        $opportunities = $userId ? PropertyOpportunity::where('user_id', $userId)
            ->where('property_listing_id', $property['ListingId'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($opp) {
                return [
                    'id' => $opp->id,
                    'opportunityId' => $opp->opportunity_id,
                    'name' => $opp->name,
                    'pipelineId' => $opp->pipeline_id,
                    'pipelineStageId' => $opp->pipeline_stage_id,
                    'source' => $opp->source,
                    'status' => $opp->status,
                ];
            })
            ->toArray() : [];

        // Récupérer le nom du member (courtier/agent)
        $memberName = '';
        $apiKey = env('CENTRIS_API_KEY');
        
        // Vérifier les clés possibles pour trouver l'agent
        $agentKey = $property['ListAgentKey'] ?? null;
        
        if (!empty($agentKey)) {
            try {
                // Utiliser le même format que dans showProperties: filtre avec MemberKey
                $apiUrl = "https://datadistributionqc.centris.ca/v1/odata/Member?\$filter=MemberKey eq '{$agentKey}'";
                Log::info('Fetching member info from API', ['url' => $apiUrl, 'agentKey' => $agentKey]);
                
                $memberResponse = Http::timeout(10)->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ])->get($apiUrl);
                
                Log::info('Member API response status', ['status' => $memberResponse->status()]);
                
                if ($memberResponse->successful()) {
                    $responseData = $memberResponse->json();
                    Log::info('Member API response data', ['data' => $responseData]);
                    
                    // Récupérer le premier résultat du tableau 'value'
                    if (isset($responseData['value']) && is_array($responseData['value']) && count($responseData['value']) > 0) {
                        $memberName = $responseData['value'][0]['MemberFullName'] ?? '';
                        Log::info('Member name extracted', ['memberName' => $memberName]);
                    }
                }
            } catch (\Exception $e) {
                // Si l'API échoue, continuer sans afficher le nom
                Log::warning('Failed to fetch member info', ['agentKey' => $agentKey, 'error' => $e->getMessage()]);
            }
        } else {
            Log::warning('ListAgentKey is empty or not found in property', ['ListingId' => $property['ListingId'] ?? 'unknown']);
        }

        // Pour compatibilité avec la vue, garder aussi idLocation
        $idLocation = $user ? $user->id_location : null;
        $locationId = request()->query('locationId');

        return view('centris.property-details', compact('property', 'persons', 'opportunities', 'idLocation', 'locationId', 'memberName'));
    }

    public function getGHLContacts()
    {
        try {
            // Vérifier si un locationId est fourni dans l'URL
            $locationId = request()->query('locationId');
            $page = request()->query('page', 1);
            $pageSize = 100; // Charger 100 contacts par page
            
            if ($locationId) {
                // Rechercher l'utilisateur avec cet id_location dans la BD
                $user = User::where('id_location', $locationId)->first();
                
                // Si l'utilisateur n'existe pas
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Emplacement non trouvé'
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun identifiant d\'emplacement fourni'
                ], 400);
            }
            
            $ghlToken = $user->ghl_access_token;
            $ghlLocationId = $user->id_location;
            
            // Vérifier que les credentials existent
            if (!$ghlToken || !$ghlLocationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration GHL manquante pour cet utilisateur'
                ], 500);
            }

            // Clé cache pour les métadonnées (total count, total pages)
            $metaCacheKey = 'ghl_contacts_meta_' . $user->id;
            $pageCacheKey = 'ghl_contacts_page_' . $user->id . '_' . $page;
            
            // Vérifier si cette page est en cache
            $cachedPageData = \Cache::get($pageCacheKey);
            if ($cachedPageData) {
                Log::info('Returning cached contacts page', ['page' => $page, 'user_id' => $user->id]);
                return response()->json([
                    'success' => true,
                    'contacts' => $cachedPageData['contacts'],
                    'total' => $cachedPageData['total'],
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalPages' => $cachedPageData['totalPages'],
                    'cached' => true
                ]);
            }

            // === Récupérer les contacts avec pagination ===
            $contactsMap = [];
            $limit = $pageSize;
            $nextPageUrl = null;
            $hasMore = true;
            $currentPageNum = 1;
            $totalContactsCount = 0;

            // Parcourir les pages jusqu'à atteindre la page demandée
            while ($hasMore && $currentPageNum <= $page) {
                if ($nextPageUrl) {
                    $url = $nextPageUrl;
                } else {
                    $url = "https://services.leadconnectorhq.com/contacts/?locationId={$ghlLocationId}&limit={$limit}";
                }
                
                Log::info('Fetching contacts from GHL API', ['url' => $url, 'api_page' => $currentPageNum]);
                
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $ghlToken,
                    'Version' => '2021-07-28',
                    'Accept' => 'application/json',
                ])->get($url);
                
                if (!$response->successful()) {
                    Log::error('GHL Contacts API Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'url' => $url
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors du chargement des contacts'
                    ], 500);
                }
                
                $data = $response->json();
                $contactsInThisPage = count($data['contacts'] ?? []);
                Log::info('GHL Contacts API Response', ['contacts_in_page' => $contactsInThisPage, 'page' => $currentPageNum]);
                
                // Traiter les contacts seulement si on est à la bonne page
                if ($currentPageNum == $page && isset($data['contacts']) && is_array($data['contacts'])) {
                    foreach ($data['contacts'] as $contact) {
                        $contactId = $contact['id'] ?? null;
                        if ($contactId && !isset($contactsMap[$contactId])) {
                            $firstName = $contact['firstName'] ?? '';
                            $lastName = $contact['lastName'] ?? '';
                            $fullName = trim($firstName . ' ' . $lastName);
                            
                            $contactsMap[$contactId] = [
                                'id' => $contactId,
                                'firstName' => $firstName,
                                'lastName' => $lastName,
                                'name' => !empty($fullName) ? $fullName : 'Sans nom',
                                'email' => $contact['email'] ?? 'Non renseigné',
                                'phone' => $contact['phone'] ?? 'Non renseigné',
                                'companyName' => $contact['companyName'] ?? ''
                            ];
                        }
                    }
                }
                
                // Compter le total de contacts pour calculer le nombre de pages
                if ($currentPageNum == 1) {
                    $totalContactsCount = $contactsInThisPage;
                }
                
                if (isset($data['meta']['nextPageUrl']) && !empty($data['meta']['nextPageUrl'])) {
                    $nextPageUrl = $data['meta']['nextPageUrl'];
                    $hasMore = true;
                    $currentPageNum++;
                } else {
                    $hasMore = false;
                }
            }

            $contacts = array_values($contactsMap);
            $totalPages = ceil($totalContactsCount / $pageSize);
            
            // Mettre en cache cette page pour 30 minutes
            \Cache::put($pageCacheKey, [
                'contacts' => $contacts,
                'total' => $totalContactsCount,
                'totalPages' => $totalPages
            ], now()->addMinutes(30));
            
            Log::info('GHL Contacts page fetched successfully', ['page' => $page, 'total_contacts_in_page' => count($contacts), 'total_pages' => $totalPages]);
            
            return response()->json([
                'success' => true,
                'contacts' => $contacts,
                'total' => $totalContactsCount,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => $totalPages,
                'cached' => false
            ]);
        } catch (\Exception $e) {
            Log::error('GHL Contacts API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getGHLOpportunities()
    {
        try {
            // Vérifier si un locationId est fourni dans l'URL
            $locationId = request()->query('locationId');
            $page = request()->query('page', 1);
            $pageSize = 50;
            
            if ($locationId) {
                // Rechercher l'utilisateur avec cet id_location dans la BD
                $user = User::where('id_location', $locationId)->first();
                
                // Si l'utilisateur n'existe pas
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Emplacement non trouvé'
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun identifiant d\'emplacement fourni'
                ], 400);
            }
            
            $ghlToken = $user->ghl_access_token;
            $ghlLocationId = $user->id_location;
            
            // Vérifier que les credentials existent
            if (!$ghlToken || !$ghlLocationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration GHL manquante pour cet utilisateur'
                ], 500);
            }

            // Clé cache pour les opportunités
            $pageCacheKey = 'ghl_opportunities_page_' . $user->id . '_' . $page;
            
            // Vérifier si cette page est en cache
            $cachedPageData = \Cache::get($pageCacheKey);
            if ($cachedPageData) {
                Log::info('Returning cached opportunities page', ['page' => $page, 'user_id' => $user->id]);
                return response()->json([
                    'success' => true,
                    'opportunities' => $cachedPageData['opportunities'],
                    'total' => $cachedPageData['total'],
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalPages' => $cachedPageData['totalPages'],
                    'cached' => true
                ]);
            }

            // Récupérer les pipelines pour mapper les IDs aux noms
            $pipelinesMap = [];
            $stagesMap = [];
            
            $pipelinesResponse = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $ghlToken,
                'Version' => '2021-07-28',
                'Accept' => 'application/json',
            ])->get("https://services.leadconnectorhq.com/opportunities/pipelines?locationId={$ghlLocationId}");
            
            if ($pipelinesResponse->successful()) {
                $pipelinesData = $pipelinesResponse->json();
                if (isset($pipelinesData['pipelines']) && is_array($pipelinesData['pipelines'])) {
                    foreach ($pipelinesData['pipelines'] as $pipeline) {
                        $pipelineId = $pipeline['id'] ?? '';
                        $pipelineName = $pipeline['name'] ?? 'Pipeline sans nom';
                        $pipelinesMap[$pipelineId] = $pipelineName;
                        
                        if (isset($pipeline['stages']) && is_array($pipeline['stages'])) {
                            foreach ($pipeline['stages'] as $stage) {
                                $stageId = $stage['id'] ?? '';
                                $stageName = $stage['name'] ?? 'Stage sans nom';
                                $stagesMap[$stageId] = $stageName;
                            }
                        }
                    }
                }
            }

            // === Récupérer les opportunités avec pagination ===
            $opportunitiesList = [];
            $nextPageUrl = null;
            $hasMore = true;
            $currentPageNum = 1;
            $totalOpportunitiesCount = 0;

            // Parcourir les pages jusqu'à atteindre la page demandée
            while ($hasMore && $currentPageNum <= $page) {
                if ($nextPageUrl) {
                    $url = $nextPageUrl;
                } else {
                    $url = "https://services.leadconnectorhq.com/opportunities/search?locationId={$ghlLocationId}&limit={$pageSize}";
                }
                
                Log::info('Fetching opportunities from GHL API', ['url' => $url, 'api_page' => $currentPageNum]);
                
                $response = Http::timeout(60)->withHeaders([
                    'Authorization' => 'Bearer ' . $ghlToken,
                    'Version' => '2021-07-28',
                    'Accept' => 'application/json',
                ])->get($url);
                
                if (!$response->successful()) {
                    Log::error('GHL Opportunities API Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'url' => $url
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors du chargement des opportunités'
                    ], 500);
                }
                
                $data = $response->json();
                $oppsInThisPage = count($data['opportunities'] ?? []);
                Log::info('GHL Opportunities API Response', ['opps_in_page' => $oppsInThisPage, 'page' => $currentPageNum]);
                
                // Traiter les opportunités seulement si on est à la bonne page
                if ($currentPageNum == $page && isset($data['opportunities']) && is_array($data['opportunities'])) {
                    foreach ($data['opportunities'] as $opp) {
                        $pipelineId = $opp['pipelineId'] ?? '';
                        $pipelineStageId = $opp['pipelineStageId'] ?? '';
                        $pipelineName = $pipelinesMap[$pipelineId] ?? $pipelineId;
                        $stageName = $stagesMap[$pipelineStageId] ?? $pipelineStageId;
                        
                        $opportunitiesList[] = [
                            'id' => $opp['id'] ?? '',
                            'name' => $opp['name'] ?? 'Sans nom',
                            'monetaryValue' => $opp['monetaryValue'] ?? 0,
                            'pipelineId' => $pipelineName,
                            'pipelineStageId' => $stageName,
                            'status' => $opp['status'] ?? '',
                            'source' => $opp['source'] ?? '',
                            'contactId' => $opp['contactId'] ?? ''
                        ];
                    }
                }
                
                // Compter le total d'opportunités pour calculer le nombre de pages
                if ($currentPageNum == 1) {
                    $totalOpportunitiesCount = $oppsInThisPage;
                }
                
                if (isset($data['meta']['nextPageUrl']) && !empty($data['meta']['nextPageUrl'])) {
                    $nextPageUrl = $data['meta']['nextPageUrl'];
                    $hasMore = true;
                    $currentPageNum++;
                } else {
                    $hasMore = false;
                }
            }

            $totalPages = ceil($totalOpportunitiesCount / $pageSize);
            
            // Mettre en cache cette page pour 30 minutes
            \Cache::put($pageCacheKey, [
                'opportunities' => $opportunitiesList,
                'total' => $totalOpportunitiesCount,
                'totalPages' => $totalPages
            ], now()->addMinutes(30));
            
            Log::info('GHL Opportunities page fetched successfully', ['page' => $page, 'total_opps_in_page' => count($opportunitiesList), 'total_pages' => $totalPages]);
            
            return response()->json([
                'success' => true,
                'opportunities' => $opportunitiesList,
                'total' => $totalOpportunitiesCount,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => $totalPages,
                'cached' => false
            ]);
        } catch (\Exception $e) {
            Log::error('GHL Opportunities API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
}

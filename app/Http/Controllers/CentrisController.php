<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\PropertyPerson;
use App\Models\PropertyOpportunity;
use App\Models\CentrisSubmission;
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

            $centrisSubmissionsCounts = CentrisSubmission::where('user_id', $userId)
                ->where('id_location', $user->id_location)
                ->whereIn('mls', $listingIds)
                ->select('mls', \DB::raw('count(*) as total'))
                ->groupBy('mls')
                ->pluck('total', 'mls')
                ->toArray();
            
            foreach ($properties as &$property) {
                $property['PersonsCount'] = $personsCounts[$property['ListingId']] ?? 0;
                $property['OpportunitiesCount'] = $opportunitiesCounts[$property['ListingId']] ?? 0;
                $property['CentrisSubmissionsCount'] = $centrisSubmissionsCounts[$property['ListingId']] ?? 0;
            }
        } else {
            foreach ($properties as &$property) {
                $property['PersonsCount'] = 0;
                $property['OpportunitiesCount'] = 0;
                $property['CentrisSubmissionsCount'] = 0;
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

        $centrisSubmissions = $userId ? CentrisSubmission::where('user_id', $userId)
            ->where('id_location', $user->id_location)
            ->where('mls', $property['ListingId'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->id,
                    'externalContactId' => $submission->external_contact_id,
                    'firstName' => $submission->first_name,
                    'lastName' => $submission->last_name,
                    'fullName' => $submission->full_name,
                    'email' => $submission->email,
                    'phone' => $submission->phone,
                    'createdAt' => optional($submission->created_at)->format('d/m/Y H:i'),
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

        return view('centris.property-details', compact('property', 'persons', 'opportunities', 'centrisSubmissions', 'idLocation', 'locationId', 'memberName'));
    }

    public function getGHLContacts()
    {
        try {
            // Vérifier si un locationId est fourni dans l'URL
            $locationId = request()->query('locationId');
            $forceRefresh = request()->boolean('force_refresh', false);
            $page = request()->query('page', 1);
            $search = trim((string) request()->query('search', ''));
            $searchLower = function_exists('mb_strtolower') ? mb_strtolower($search) : strtolower($search);
            $hasSearch = $search !== '';
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

            // Clé cache pour cette page
            $pageCacheKey = 'ghl_contacts_page_v4_' . $user->id . '_' . $page . '_' . md5($searchLower);
            
            // Vérifier si cette page est en cache
            $cachedPageData = !$forceRefresh ? \Cache::get($pageCacheKey) : null;
            if ($cachedPageData) {
                Log::info('Returning cached contacts page', ['page' => $page, 'user_id' => $user->id]);
                return response()->json([
                    'success' => true,
                    'contacts' => $cachedPageData['contacts'],
                    'total' => $cachedPageData['total'],
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalPages' => $cachedPageData['totalPages'],
                    'hasNextPage' => $cachedPageData['hasNextPage'] ?? false,
                    'search' => $search,
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
            $knownTotalPages = null;
            $hasNextPage = false;

            // Parcourir les pages jusqu'à atteindre la page demandée
            while ($hasMore && $currentPageNum <= $page) {
                if ($nextPageUrl) {
                    $url = $nextPageUrl;
                } else {
                    $url = "https://services.leadconnectorhq.com/contacts/?locationId={$ghlLocationId}&limit={$limit}";
                    if ($hasSearch) {
                        $url .= '&query=' . urlencode($search);
                    }
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

                $apiTotal = $data['meta']['total'] ?? null;
                if (is_numeric($apiTotal)) {
                    $totalContactsCount = (int) $apiTotal;
                    $knownTotalPages = max(1, (int) ceil($totalContactsCount / $pageSize));
                }
                
                // Traiter les contacts
                if (isset($data['contacts']) && is_array($data['contacts'])) {
                    foreach ($data['contacts'] as $contact) {
                        $contactId = $contact['id'] ?? null;
                        if (!$contactId || isset($contactsMap[$contactId])) {
                            continue;
                        }

                        $firstName = $contact['firstName'] ?? '';
                        $lastName = $contact['lastName'] ?? '';
                        $fullName = trim($firstName . ' ' . $lastName);

                        $normalized = [
                            'id' => $contactId,
                            'firstName' => $firstName,
                            'lastName' => $lastName,
                            'name' => !empty($fullName) ? $fullName : 'Sans nom',
                            'email' => $contact['email'] ?? 'Non renseigné',
                            'phone' => $contact['phone'] ?? 'Non renseigné',
                            'companyName' => $contact['companyName'] ?? ''
                        ];

                        if ($currentPageNum == $page) {
                            $contactsMap[$contactId] = $normalized;
                        }
                    }
                }
                
                // Compter minimalement si le total n'est pas fourni
                if ($currentPageNum == 1 && $knownTotalPages === null) {
                    $totalContactsCount = $contactsInThisPage;
                }
                
                if (isset($data['meta']['nextPageUrl']) && !empty($data['meta']['nextPageUrl'])) {
                    $nextPageUrl = $data['meta']['nextPageUrl'];
                    $hasMore = true;
                    if ($currentPageNum == $page) {
                        $hasNextPage = true;
                    }
                    $currentPageNum++;
                } else {
                    $hasMore = false;
                    if ($currentPageNum == $page) {
                        $hasNextPage = false;
                    }
                }
            }

            $contacts = array_values($contactsMap);
            $totalPages = $knownTotalPages ?? ($hasNextPage ? max(1, $page + 1) : max(1, $page));
            
            // Mettre en cache cette page pour 60 minutes
            \Cache::put($pageCacheKey, [
                'contacts' => $contacts,
                'total' => $totalContactsCount,
                'totalPages' => $totalPages,
                'hasNextPage' => $hasNextPage
            ], now()->addMinutes(60));
            
            Log::info('GHL Contacts page fetched successfully', ['page' => $page, 'total_contacts_in_page' => count($contacts), 'total_pages' => $totalPages]);
            
            return response()->json([
                'success' => true,
                'contacts' => $contacts,
                'total' => $totalContactsCount,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => $totalPages,
                'hasNextPage' => $hasNextPage,
                'search' => $search,
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
            $forceRefresh = request()->boolean('force_refresh', false);
            $pageSize = 100;
            
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

            // Clé cache pour toutes les opportunités
            $cacheKey = 'ghl_opportunities_all_' . $user->id;
            
            // Vérifier si le cache est disponible
            $cachedPageData = !$forceRefresh ? \Cache::get($cacheKey) : null;
            if ($cachedPageData) {
                Log::info('Returning cached opportunities list', ['user_id' => $user->id]);
                return response()->json([
                    'success' => true,
                    'opportunities' => $cachedPageData['opportunities'],
                    'total' => $cachedPageData['total'],
                    'page' => 1,
                    'pageSize' => $pageSize,
                    'totalPages' => 1,
                    'cached' => true
                ]);
            }

            // Récupérer les pipelines pour mapper les IDs aux noms
            $pipelinesMap = [];
            $stagesMap = [];
            $pipelineIds = [];
            
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
                        if (!empty($pipelineId)) {
                            $pipelinesMap[$pipelineId] = $pipelineName;
                            $pipelineIds[] = $pipelineId;
                        }
                        
                        if (isset($pipeline['stages']) && is_array($pipeline['stages'])) {
                            foreach ($pipeline['stages'] as $stage) {
                                $stageId = $stage['id'] ?? '';
                                if (!empty($stageId)) {
                                    $stageName = $stage['name'] ?? 'Stage sans nom';
                                    $stagesMap[$stageId] = $stageName;
                                }
                            }
                        }
                    }
                }
            }

            // === Récupérer les opportunités de toutes les pipelines ===
            $opportunitiesMap = [];
            $pipelinesToFetch = !empty($pipelineIds) ? $pipelineIds : [null];

            foreach ($pipelinesToFetch as $pipelineIdToFetch) {
                $nextPageUrl = null;

                do {
                    if ($nextPageUrl) {
                        $url = $nextPageUrl;
                    } else {
                        $url = "https://services.leadconnectorhq.com/opportunities/search?location_id={$ghlLocationId}&limit={$pageSize}";
                        if (!empty($pipelineIdToFetch)) {
                            $url .= '&pipeline_id=' . urlencode($pipelineIdToFetch);
                        }
                    }

                    Log::info('Fetching opportunities from GHL API', [
                        'url' => $url,
                        'pipeline_id' => $pipelineIdToFetch
                    ]);

                    $response = Http::timeout(60)->withHeaders([
                        'Authorization' => 'Bearer ' . $ghlToken,
                        'Version' => '2021-07-28',
                        'Accept' => 'application/json',
                    ])->get($url);

                    if (!$response->successful()) {
                        $errorData = $response->json();
                        $upstreamMessage = $errorData['message'] ?? null;
                        Log::error('GHL Opportunities API Error', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                            'url' => $url,
                            'pipeline_id' => $pipelineIdToFetch
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => $upstreamMessage ?: 'Erreur lors du chargement des opportunités'
                        ], 500);
                    }

                    $data = $response->json();
                    $opportunities = $data['opportunities'] ?? [];

                    foreach ($opportunities as $opp) {
                        $opportunityId = $opp['id'] ?? '';
                        if (empty($opportunityId) || isset($opportunitiesMap[$opportunityId])) {
                            continue;
                        }

                        $pipelineId = $opp['pipelineId'] ?? '';
                        $pipelineStageId = $opp['pipelineStageId'] ?? '';

                        $opportunitiesMap[$opportunityId] = [
                            'id' => $opportunityId,
                            'name' => $opp['name'] ?? 'Sans nom',
                            'monetaryValue' => $opp['monetaryValue'] ?? 0,
                            'pipelineId' => $pipelinesMap[$pipelineId] ?? $pipelineId,
                            'pipelineStageId' => $stagesMap[$pipelineStageId] ?? $pipelineStageId,
                            'status' => $opp['status'] ?? '',
                            'source' => $opp['source'] ?? '',
                            'contactId' => $opp['contactId'] ?? ''
                        ];
                    }

                    $nextPageUrl = $data['meta']['nextPageUrl'] ?? null;
                } while (!empty($nextPageUrl));
            }

            $opportunitiesList = array_values($opportunitiesMap);
            $totalOpportunitiesCount = count($opportunitiesList);
            
            // Mettre en cache pour 30 minutes
            \Cache::put($cacheKey, [
                'opportunities' => $opportunitiesList,
                'total' => $totalOpportunitiesCount
            ], now()->addMinutes(60));
            
            Log::info('GHL Opportunities list fetched successfully', [
                'total_opps' => $totalOpportunitiesCount,
                'pipelines_count' => count($pipelinesToFetch)
            ]);
            
            return response()->json([
                'success' => true,
                'opportunities' => $opportunitiesList,
                'total' => $totalOpportunitiesCount,
                'page' => 1,
                'pageSize' => $pageSize,
                'totalPages' => 1,
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

    public function getGHLSocialAccounts($listingKey)
    {
        try {
            $locationId = request()->query('locationId');
            $user = $this->getUserForLocationRequest($locationId);

            if ($user instanceof \Illuminate\Http\JsonResponse) {
                return $user;
            }

            $accounts = $this->fetchFilteredGhlSocialAccounts($user);

            $property = $this->fetchCentrisPropertyByListingKey($listingKey);
            $summary = $property ? $this->buildSocialPostSummary($property) : '';
            $mediaCount = count($this->fetchCentrisPropertyPhotos($listingKey, 5));

            return response()->json([
                'success' => true,
                'accounts' => $accounts,
                'summary' => $summary,
                'mediaCount' => $mediaCount,
            ]);
        } catch (\Exception $e) {
            Log::error('GHL Social Accounts Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function createGHLSocialPost(Request $request, $listingKey)
    {
        $validator = Validator::make($request->all(), [
            'locationId' => 'required|string',
            'accountIds' => 'required|array|min:1',
            'accountIds.*' => 'required|string',
            'scheduleDate' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $this->getUserForLocationRequest($request->input('locationId'));

            if ($user instanceof \Illuminate\Http\JsonResponse) {
                return $user;
            }

            $accounts = $this->fetchFilteredGhlSocialAccounts($user);
            $selectableAccountIds = array_column(array_filter($accounts, function($account) {
                return $account['selectable'] ?? false;
            }), 'id');
            $invalidAccountIds = array_diff($request->input('accountIds'), $selectableAccountIds);

            if (!empty($invalidAccountIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un ou plusieurs comptes sociaux ne sont pas disponibles pour la planification.',
                ], 422);
            }

            $property = $this->fetchCentrisPropertyByListingKey($listingKey);
            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Propriété introuvable',
                ], 404);
            }

            $photos = $this->fetchCentrisPropertyPhotos($listingKey, 5);
            if (empty($photos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune image disponible pour cette inscription.',
                ], 422);
            }

            $ghlUserId = $this->fetchFirstActiveGhlUserId($user);
            if (!$ghlUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun utilisateur GHL valide trouvé pour cette location.',
                ], 422);
            }

            $scheduleDate = \Carbon\Carbon::parse($request->input('scheduleDate'), config('app.timezone'))
                ->utc()
                ->toIso8601String();

            $payload = [
                'accountIds' => array_values($request->input('accountIds')),
                'summary' => $this->buildSocialPostSummary($property),
                'media' => array_map(function($photo) {
                    return [
                        'url' => $photo['MediaURL'],
                        'type' => $this->guessMediaType($photo['MediaURL']),
                    ];
                }, $photos),
                'status' => 'scheduled',
                'scheduleDate' => $scheduleDate,
                'type' => 'post',
                'userId' => $ghlUserId,
            ];

            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $user->ghl_access_token,
                'Version' => 'v3',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("https://services.leadconnectorhq.com/social-media-posting/{$user->id_location}/posts", $payload);

            if (!$response->successful()) {
                $errorData = $response->json();
                $upstreamMessage = $errorData['message'] ?? null;
                Log::error('GHL Social Post API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'locationId' => $user->id_location,
                    'listingKey' => $listingKey,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $upstreamMessage ?: 'Erreur lors de la planification du post social',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post social planifié avec succès',
                'post' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error('GHL Social Post Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'listingKey' => $listingKey,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function fetchFilteredGhlSocialAccounts(User $user)
    {
        $accountsResponse = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $user->ghl_access_token,
            'Version' => 'v3',
            'Accept' => 'application/json',
        ])->get("https://services.leadconnectorhq.com/social-media-posting/{$user->id_location}/accounts");

        if (!$accountsResponse->successful()) {
            Log::error('GHL Social Accounts API Error', [
                'status' => $accountsResponse->status(),
                'body' => $accountsResponse->body(),
                'locationId' => $user->id_location,
            ]);

            throw new \RuntimeException('Erreur lors du chargement des comptes sociaux');
        }

        $responseData = $accountsResponse->json();
        $accounts = $this->normalizeGhlList($responseData, ['accounts', 'data', 'items', 'results', 'socialAccounts']);
        $filteredAccounts = array_values(array_filter(array_map(function($account) use ($user) {
            $platform = strtolower($account['platform'] ?? '');
            if (!in_array($platform, ['facebook', 'instagram'], true)) {
                Log::debug('GHL Social Account ignored: unsupported platform', [
                    'locationId' => $user->id_location,
                    'account_id' => $account['id'] ?? null,
                    'account_name' => $account['name'] ?? null,
                    'platform' => $account['platform'] ?? null,
                    'available_keys' => array_keys($account),
                ]);
                return null;
            }

            $selectable = !empty($account['id']);
            if (!$selectable) {
                Log::debug('GHL Social Account ignored: missing id', [
                    'locationId' => $user->id_location,
                    'account_name' => $account['name'] ?? null,
                    'platform' => $account['platform'] ?? null,
                    'available_keys' => array_keys($account),
                ]);
            }

            return [
                'id' => $account['id'] ?? '',
                'name' => $account['name'] ?? 'Compte social',
                'platform' => $platform,
                'avatar' => $account['avatar'] ?? null,
                'type' => $account['type'] ?? null,
                'active' => $account['active'] ?? true,
                'isExpired' => $account['isExpired'] ?? false,
                'deleted' => $account['deleted'] ?? false,
                'selectable' => $selectable,
            ];
        }, $accounts)));

        Log::info('GHL Social Accounts fetched', [
            'locationId' => $user->id_location,
            'response_status' => $accountsResponse->status(),
            'response_top_level_keys' => is_array($responseData) ? array_keys($responseData) : [],
            'raw_accounts_count' => count($accounts),
            'filtered_accounts_count' => count($filteredAccounts),
            'selectable_accounts_count' => count(array_filter($filteredAccounts, function($account) {
                return $account['selectable'] ?? false;
            })),
            'raw_platforms' => array_values(array_unique(array_filter(array_map(function($account) {
                return $account['platform'] ?? null;
            }, $accounts)))),
            'filtered_accounts' => array_map(function($account) {
                return [
                    'id' => $account['id'] ?? null,
                    'name' => $account['name'] ?? null,
                    'platform' => $account['platform'] ?? null,
                    'type' => $account['type'] ?? null,
                    'active' => $account['active'] ?? null,
                    'isExpired' => $account['isExpired'] ?? null,
                    'deleted' => $account['deleted'] ?? null,
                    'selectable' => $account['selectable'] ?? false,
                ];
            }, $filteredAccounts),
        ]);

        if (empty($filteredAccounts)) {
            Log::warning('GHL Social Accounts empty after filtering', [
                'locationId' => $user->id_location,
                'response_top_level_keys' => is_array($responseData) ? array_keys($responseData) : [],
                'raw_accounts_count' => count($accounts),
                'raw_accounts_preview' => array_slice($accounts, 0, 5),
            ]);
        }

        return $filteredAccounts;
    }

    private function getUserForLocationRequest($locationId)
    {
        if (empty($locationId)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun identifiant d\'emplacement fourni',
            ], 400);
        }

        $user = User::where('id_location', $locationId)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Emplacement non trouvé',
            ], 404);
        }

        if (!$user->ghl_access_token) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration GHL manquante pour cet utilisateur',
            ], 500);
        }

        return $user;
    }

    private function fetchCentrisPropertyByListingKey($listingKey)
    {
        $apiKey = env('CENTRIS_API_KEY');
        $escapedListingKey = $this->escapeODataString($listingKey);
        $propertyUrl = "https://datadistributionqc.centris.ca/v1/odata/Property?\$filter=ListingKey eq '$escapedListingKey'";

        $propertyResponse = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->get($propertyUrl);

        if (!$propertyResponse->successful()) {
            Log::warning('Failed to fetch Centris property for social post', [
                'listingKey' => $listingKey,
                'status' => $propertyResponse->status(),
                'body' => $propertyResponse->body(),
            ]);
            return null;
        }

        return $propertyResponse->json()['value'][0] ?? null;
    }

    private function fetchCentrisPropertyPhotos($listingKey, $limit = 5)
    {
        $apiKey = env('CENTRIS_API_KEY');
        $escapedListingKey = $this->escapeODataString($listingKey);
        $mediaUrl = "https://datadistributionqc.centris.ca/v1/odata/Media?\$filter=ResourceRecordKey eq '$escapedListingKey' and MediaCategory eq 'Photo'";

        $mediaResponse = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->get($mediaUrl);

        if (!$mediaResponse->successful()) {
            Log::warning('Failed to fetch Centris media for social post', [
                'listingKey' => $listingKey,
                'status' => $mediaResponse->status(),
                'body' => $mediaResponse->body(),
            ]);
            return [];
        }

        $media = array_filter($mediaResponse->json()['value'] ?? [], function($item) {
            return !empty($item['MediaURL']);
        });

        usort($media, function($a, $b) {
            return ($a['Order'] ?? 999) <=> ($b['Order'] ?? 999);
        });

        return array_slice(array_values($media), 0, $limit);
    }

    private function fetchFirstActiveGhlUserId(User $user)
    {
        $companyId = env('companyId');
        if (!$companyId) {
            Log::error('Missing companyId env for GHL users search');
            return null;
        }

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $user->ghl_access_token,
            'Version' => 'v3',
            'Accept' => 'application/json',
        ])->get('https://services.leadconnectorhq.com/users/search', [
            'companyId' => $companyId,
            'limit' => 2,
            'locationId' => $user->id_location,
        ]);

        if (!$response->successful()) {
            Log::error('GHL Users Search API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'locationId' => $user->id_location,
            ]);
            return null;
        }

        $users = $this->normalizeGhlList($response->json(), ['users', 'data']);

        foreach ($users as $ghlUser) {
            $isDeleted = (bool) ($ghlUser['deleted'] ?? false);
            $isActive = array_key_exists('active', $ghlUser) ? (bool) $ghlUser['active'] : true;
            $userId = $ghlUser['id'] ?? $ghlUser['_id'] ?? null;

            if ($userId && !$isDeleted && $isActive) {
                return $userId;
            }
        }

        return null;
    }

    private function buildSocialPostSummary(array $property)
    {
        $addressParts = [];
        if (!empty($property['StreetNumberStart'])) {
            $streetNumber = $property['StreetNumberStart'];
            if (!empty($property['StreetNumberEnd'])) {
                $streetNumber .= ' - ' . $property['StreetNumberEnd'];
            }
            $addressParts[] = $streetNumber;
        }
        foreach (['StreetShortName', 'Township', 'PostalCode'] as $field) {
            if (!empty($property[$field])) {
                $addressParts[] = $property[$field];
            }
        }

        $lines = ['Nouvelle inscription disponible.'];
        $address = implode(', ', $addressParts);
        if ($address !== '') {
            $lines[] = '';
            $lines[] = $address;
        }

        $type = $property['PropertySubType'] ?? $property['PropertyType'] ?? null;
        if (!empty($type)) {
            $lines[] = $type;
        }

        $price = $property['ListPrice'] ?? null;
        $isRent = false;
        if (empty($price) && !empty($property['RentPrice'])) {
            $price = $property['RentPrice'];
            $isRent = true;
        }
        if (!empty($price)) {
            $lines[] = 'Prix : ' . number_format((float) $price, 0, ',', ' ') . ' $' . ($isRent ? '/mois' : '');
        }

        $details = [];
        if (!empty($property['BedroomsTotal'])) {
            $details[] = 'Chambres : ' . $property['BedroomsTotal'];
        }
        $bathrooms = ($property['BathroomsFull'] ?? 0) + ($property['BathroomsPartial'] ?? 0);
        if ($bathrooms > 0) {
            $details[] = 'Salles de bain : ' . $bathrooms;
        }
        if (!empty($property['LivingArea'])) {
            $details[] = 'Superficie : ' . $property['LivingArea'];
        }
        if (!empty($details)) {
            $lines[] = '';
            $lines = array_merge($lines, $details);
        }

        if (!empty($property['ListingURL'])) {
            $lines[] = '';
            $lines[] = 'Découvrez cette propriété ici :';
            $lines[] = $property['ListingURL'];
        }

        return implode("\n", $lines);
    }

    private function guessMediaType($url)
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        if (substr($path, -4) === '.png') {
            return 'image/png';
        }
        if (substr($path, -5) === '.webp') {
            return 'image/webp';
        }
        return 'image/jpeg';
    }

    private function escapeODataString($value)
    {
        return str_replace("'", "''", $value);
    }

    private function normalizeGhlList($data, array $candidateKeys)
    {
        if (!is_array($data)) {
            return [];
        }

        foreach ($candidateKeys as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return array_values($data) === $data ? $data : [];
    }
}

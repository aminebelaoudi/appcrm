<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\PropertyPerson;
use App\Models\PropertyOpportunity;
use App\User;


class CentrisController extends Controller
{
    public function showProperties()
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
                return view('centris.no-access', ['message' => "Vous n'avez pas accès à ces propriétés."]);
            }
            
            // Vérifier que l'utilisateur a les credentials GHL nécessaires
            if (!$user->ghl_access_token) {
                return view('centris.no-access', ['message' => "Configuration GHL manquante pour cet emplacement."]);
            }
        } else {
            // Si pas de locationId, ne rien afficher
            return view('centris.no-access', ['message' => "Aucun identifiant d'emplacement fourni."]);
        }
        $agentKey = '76440';
        $perPage = 12;
        $page = request()->get('page', 1);

        // Cache plus long pour les propriétés (30 minutes au lieu de 10)
        $cacheKey = "centris_properties_{$agentKey}";
        $allProperties = Cache::remember($cacheKey, 1800, function() use ($agentKey) {
            $url = "https://datadistributionqc.centris.ca/v1/odata/Property?\$filter=ListAgentKey eq '$agentKey'&\$count=true";
            $apiKey = env('CENTRIS_API_KEY');
            $response = Http::timeout(120)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->get($url);
            return $response->json()['value'] ?? [];
        });

        $totalCount = count($allProperties);
        $offset = ($page - 1) * $perPage;
        $properties = array_slice($allProperties, $offset, $perPage);

        $listingKeys = array_column($properties, 'ListingKey');
        $apiKey = env('CENTRIS_API_KEY');

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

    // Utiliser uniquement id_location de l'utilisateur connecté
    $idLocation = $user ? $user->id_location : null;

        // Optimisation: charger tous les comptages en une seule requête
        if ($idLocation) {
            $listingIds = array_column($properties, 'ListingId');
            
            $personsCounts = PropertyPerson::where('id_location', $idLocation)
                ->whereIn('property_listing_id', $listingIds)
                ->select('property_listing_id', \DB::raw('count(*) as total'))
                ->groupBy('property_listing_id')
                ->pluck('total', 'property_listing_id')
                ->toArray();
            
            $opportunitiesCounts = PropertyOpportunity::where('id_location', $idLocation)
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

        return view('centris.properties', compact('properties', 'pagination', 'locationId'));
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

    // Utiliser uniquement id_location de l'utilisateur connecté
    $idLocation = $user ? $user->id_location : null;

        // Charger les personnes et opportunités depuis la base de données
        $persons = $idLocation ? PropertyPerson::where('id_location', $idLocation)
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

        $opportunities = $idLocation ? PropertyOpportunity::where('id_location', $idLocation)
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

        return view('centris.property-details', compact('property', 'persons', 'opportunities', 'idLocation', 'locationId'));
    }

    public function getGHLContacts()
    {
        try {
            // Vérifier si un locationId est fourni dans l'URL
            $locationId = request()->query('locationId');
            
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
            
            // Vérifier si les données sont en cache (5 minutes)
            $cacheKey = 'ghl_contacts_list_' . $user->id;
            $cachedContacts = \Cache::get($cacheKey);
            if ($cachedContacts) {
                return response()->json([
                    'success' => true,
                    'contacts' => $cachedContacts['contacts'] ?? $cachedContacts,
                    'opportunities' => $cachedContacts['opportunities'] ?? [],
                    'total' => count($cachedContacts['contacts'] ?? $cachedContacts),
                    'cached' => true
                ]);
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
                        
                        // Mapper les stages de ce pipeline
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

            $contactsMap = [];
            $opportunitiesList = [];
            $limit = 100;
            $nextPageUrl = null;
            $hasMore = true;
            $pageCount = 0;

            while ($hasMore) {
                if ($nextPageUrl) {
                    $url = $nextPageUrl;
                } else {
                    $url = "https://services.leadconnectorhq.com/opportunities/search?location_id={$ghlLocationId}&limit={$limit}";
                }
                $response = Http::timeout(30)->withHeaders([
                    'Authorization' => 'Bearer ' . $ghlToken,
                    'Version' => '2021-07-28',
                    'Accept' => 'application/json',
                ])->get($url);
                if (!$response->successful()) {
                    \Log::error('GHL API Error', [
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
                if (isset($data['opportunities']) && is_array($data['opportunities'])) {
                    foreach ($data['opportunities'] as $opp) {
                        $pipelineId = $opp['pipelineId'] ?? '';
                        $pipelineStageId = $opp['pipelineStageId'] ?? '';
                        
                        // Utiliser les noms au lieu des IDs
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
                        if (isset($opp['contact']) && isset($opp['contact']['id'])) {
                            $contact = $opp['contact'];
                            $contactId = $contact['id'];
                            if (!isset($contactsMap[$contactId])) {
                                $contactsMap[$contactId] = [
                                    'id' => $contactId,
                                    'name' => $contact['name'] ?? 'Sans nom',
                                    'email' => $contact['email'] ?? 'Non renseigné',
                                    'phone' => $contact['phone'] ?? 'Non renseigné',
                                    'companyName' => $contact['companyName'] ?? ''
                                ];
                            }
                        }
                    }
                }
                if (isset($data['meta']['nextPageUrl']) && !empty($data['meta']['nextPageUrl'])) {
                    $nextPageUrl = $data['meta']['nextPageUrl'];
                    $hasMore = true;
                } else {
                    $hasMore = false;
                }
                $pageCount++;
                if ($pageCount >= 100) {
                    \Log::warning('GHL Pagination: Limite de sécurité atteinte (100 pages)');
                    break;
                }
            }
            $contacts = array_values($contactsMap);
            \Cache::put($cacheKey, [
                'contacts' => $contacts,
                'opportunities' => $opportunitiesList
            ], now()->addMinutes(5));
            return response()->json([
                'success' => true,
                'contacts' => $contacts,
                'opportunities' => $opportunitiesList,
                'total' => count($contacts),
                'cached' => false
            ]);
        } catch (\Exception $e) {
            \Log::error('GHL API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }
}

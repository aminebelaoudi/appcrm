<?php

namespace App\Http\Controllers;

use App\Models\OpenHouse;
use App\Models\OpenHouseRegistration;
use App\User;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class OpenHouseController extends Controller
{
    public function defaults(Request $request, $listingKey)
    {
        $user = $this->userForSignedRequest($request);
        $property = $this->propertyForUser($listingKey, $user);
        $member = $this->member($property['ListAgentKey'] ?? null);
        $location = $this->location($user);

        return response()->json([
            'brokerName' => $member['MemberFullName'] ?? '',
            'brokerEmail' => $member['MemberEmail'] ?? $member['MemberEmailAddress'] ?? '',
            'brokerPhone' => $member['MemberPreferredPhone'] ?? $member['MemberDirectPhone'] ?? $member['MemberMobilePhone'] ?? '',
            'brokerPhotoUrl' => $member['MemberPhotoURL'] ?? $member['MemberPhotoUrl'] ?? '',
            'timezone' => $location['timezone'] ?? 'America/Toronto',
            'photoCount' => count($this->photos($listingKey)),
        ]);
    }

    public function store(Request $request, $listingKey)
    {
        $user = $this->userForSignedRequest($request);
        $data = $this->validate($request, [
            'startsAt' => 'required|date_format:Y-m-d\TH:i',
            'endsAt' => 'required|date_format:Y-m-d\TH:i',
            'timezone' => 'required|timezone',
            'brokerName' => 'required|string|max:255',
            'brokerEmail' => 'nullable|email|max:255',
            'brokerPhone' => 'nullable|string|max:255',
            'brokerPhotoUrl' => 'nullable|url|max:2048',
            'brokerPhoto' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $startsAt = Carbon::createFromFormat('!Y-m-d\TH:i', $data['startsAt'], $data['timezone']);
            $endsAt = Carbon::createFromFormat('!Y-m-d\TH:i', $data['endsAt'], $data['timezone']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Date ou fuseau horaire invalide.'], 422);
        }
        if (!$startsAt || !$endsAt || $startsAt->format('Y-m-d\TH:i') !== $data['startsAt']
            || $endsAt->format('Y-m-d\TH:i') !== $data['endsAt']
            || $startsAt->isPast() || $endsAt->lte($startsAt)) {
            return response()->json(['message' => 'Vérifiez les heures de début et de fin.'], 422);
        }

        $property = $this->propertyForUser($listingKey, $user);
        $photos = $this->photos($listingKey);
        if (!$photos) {
            return response()->json(['message' => 'Aucune photo Centris disponible pour cette propriété.'], 422);
        }

        $photoUrl = $data['brokerPhotoUrl'] ?? null;
        if ($request->hasFile('brokerPhoto')) {
            $filename = Str::random(32).'.'.$request->file('brokerPhoto')->extension();
            $directory = public_path('uploads/open-houses');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $request->file('brokerPhoto')->move($directory, $filename);
            $photoUrl = asset('uploads/open-houses/'.$filename);
        }

        $price = $property['ListPrice'] ?? 0;
        $priceLabel = $price > 0 ? number_format($price, 0, ',', ' ').' $' : null;
        if (!$priceLabel && !empty($property['RentPrice'])) {
            $priceLabel = number_format($property['RentPrice'], 0, ',', ' ').' $/mois';
        }

        $location = $this->location($user);
        $openHouse = OpenHouse::create([
            'user_id' => $user->id,
            'id_location' => $user->id_location,
            'listing_key' => $listingKey,
            'listing_id' => (string) ($property['ListingId'] ?? $listingKey),
            'public_token' => Str::random(40),
            'starts_at' => $startsAt->copy()->utc(),
            'ends_at' => $endsAt->copy()->utc(),
            'timezone' => $data['timezone'],
            'address' => $this->address($property),
            'property_type' => $property['PropertySubType'] ?? $property['PropertyType'] ?? null,
            'price_label' => $priceLabel,
            'bedrooms' => $property['BedroomsTotal'] ?? null,
            'bathrooms' => ($property['BathroomsFull'] ?? 0) + ($property['BathroomsPartial'] ?? 0),
            'living_area' => isset($property['LivingArea']) ? (string) $property['LivingArea'] : null,
            'photos' => $photos,
            'listing_url' => $property['ListingURL'] ?? null,
            'broker_name' => $data['brokerName'],
            'broker_email' => $data['brokerEmail'] ?? null,
            'broker_phone' => $data['brokerPhone'] ?? null,
            'broker_photo_url' => $photoUrl,
            'logo_url' => $location['logoUrl'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json($this->publicDetails($openHouse), 201);
    }

    public function registrations(Request $request, $openHouseId)
    {
        $user = $this->userForSignedRequest($request);
        $openHouse = OpenHouse::where('user_id', $user->id)->findOrFail($openHouseId);
        $visitors = $openHouse->registrations()->latest()->get()->map(function ($visitor) use ($openHouse) {
            return [
                'name' => trim($visitor->first_name.' '.$visitor->last_name),
                'email' => $visitor->email,
                'phone' => $visitor->phone,
                'registeredAt' => $visitor->created_at->timezone($openHouse->timezone)->format('d/m/Y H:i'),
                'contactUrl' => 'https://go.optimocrm.com/v2/location/'.rawurlencode($openHouse->id_location).'/contacts/detail/'.rawurlencode($visitor->ghl_contact_id),
            ];
        });

        return response()->json(['visitors' => $visitors]);
    }

    public function show($token)
    {
        $openHouse = OpenHouse::where('public_token', $token)->firstOrFail();
        return view('open-houses.show', compact('openHouse'));
    }

    public function qr($token)
    {
        $openHouse = OpenHouse::where('public_token', $token)->firstOrFail();
        $qrCode = new QrCode(route('open-houses.public', $openHouse->public_token));
        $qrCode->setWriterByName('svg');
        $qrCode->setSize(640);
        $qrCode->setMargin(24);

        return response($qrCode->writeString(), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="visite-libre.svg"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function register(Request $request, $token)
    {
        $openHouse = OpenHouse::where('public_token', $token)->firstOrFail();
        if ($openHouse->ends_at->isPast()) {
            return response()->json(['message' => 'Les inscriptions pour cette visite sont terminées.'], 410);
        }
        $data = $this->validate($request, [
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:40',
            'consent' => 'accepted',
            'website' => 'nullable|string|max:255',
        ]);
        if (!empty($data['website'])) {
            return response()->json(['success' => true]);
        }

        $user = User::findOrFail($openHouse->user_id);
        if (!$this->readyToken($user)) {
            return response()->json(['message' => 'Inscription temporairement indisponible. Veuillez réessayer.'], 503);
        }

        try {
            $headers = [
                'Authorization' => 'Bearer '.$user->ghl_access_token,
                'Version' => 'v3',
                'Accept' => 'application/json',
            ];
            $contactResponse = Http::timeout(15)->withHeaders($headers)
                ->post('https://services.leadconnectorhq.com/contacts/upsert', [
                    'locationId' => $openHouse->id_location,
                    'firstName' => $data['firstName'],
                    'lastName' => $data['lastName'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'source' => 'Visite libre',
                ]);
            $contactId = data_get($contactResponse->json(), 'contact.id');
            if (!$contactResponse->successful() || !$contactId) {
                Log::warning('Open house contact upsert failed', ['open_house_id' => $openHouse->id, 'status' => $contactResponse->status()]);
                return response()->json(['message' => 'Le CRM est temporairement indisponible. Veuillez réessayer.'], 503);
            }

            $tag = config('open_houses.contact_tag');
            $tagResponse = Http::timeout(15)->withHeaders($headers)
                ->post('https://services.leadconnectorhq.com/contacts/'.rawurlencode($contactId).'/tags', ['tags' => [$tag]]);
            if (!$tagResponse->successful()) {
                Log::warning('Open house contact tag failed', ['open_house_id' => $openHouse->id, 'status' => $tagResponse->status()]);
                return response()->json(['message' => 'Le CRM est temporairement indisponible. Veuillez réessayer.'], 503);
            }

            OpenHouseRegistration::updateOrCreate(
                ['open_house_id' => $openHouse->id, 'ghl_contact_id' => $contactId],
                ['first_name' => $data['firstName'], 'last_name' => $data['lastName'], 'email' => $data['email'], 'phone' => $data['phone']]
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Open house registration failed', ['open_house_id' => $openHouse->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Inscription temporairement indisponible. Veuillez réessayer.'], 503);
        }
    }

    private function publicDetails(OpenHouse $openHouse)
    {
        return [
            'id' => $openHouse->id,
            'url' => route('open-houses.public', $openHouse->public_token),
            'qrUrl' => route('open-houses.qr', $openHouse->public_token),
            'registrationsUrl' => URL::temporarySignedRoute('open-houses.registrations', now()->addHours(2), [
                'openHouseId' => $openHouse->id,
                'locationId' => $openHouse->id_location,
            ], false),
            'startsAt' => $openHouse->starts_at->timezone($openHouse->timezone)->format('d/m/Y H:i'),
            'endsAt' => $openHouse->ends_at->timezone($openHouse->timezone)->format('H:i'),
            'visitorCount' => 0,
        ];
    }

    private function propertyForUser($listingKey, User $user)
    {
        $escaped = str_replace("'", "''", $listingKey);
        try {
            $response = Http::timeout(30)->withHeaders($this->centrisHeaders())
                ->get("https://datadistributionqc.centris.ca/v1/odata/Property?\$filter=ListingKey eq '$escaped'");
        } catch (\Throwable $e) {
            Log::warning('Open house property lookup failed', ['listingKey' => $listingKey, 'error' => $e->getMessage()]);
            abort(503, 'La propriété Centris est temporairement indisponible.');
        }
        if (!$response->successful()) {
            Log::warning('Open house property lookup failed', ['listingKey' => $listingKey, 'status' => $response->status()]);
            abort(503, 'La propriété Centris est temporairement indisponible.');
        }
        $property = data_get($response->json(), 'value.0');
        if (!$property) {
            abort(404, 'Propriété introuvable');
        }
        if (empty($user->Codebureau) || ($property['ListOfficeKey'] ?? null) !== $user->Codebureau) {
            abort(403, 'Cette propriété ne fait pas partie de votre bureau.');
        }
        return $property;
    }

    private function photos($listingKey)
    {
        $escaped = str_replace("'", "''", $listingKey);
        try {
            $response = Http::timeout(30)->withHeaders($this->centrisHeaders())
                ->get("https://datadistributionqc.centris.ca/v1/odata/Media?\$filter=ResourceRecordKey eq '$escaped' and MediaCategory eq 'Photo'");
        } catch (\Throwable $e) {
            Log::warning('Open house photo lookup failed', ['listingKey' => $listingKey, 'error' => $e->getMessage()]);
            abort(503, 'Les photos Centris sont temporairement indisponibles.');
        }
        if (!$response->successful()) {
            Log::warning('Open house photo lookup failed', ['listingKey' => $listingKey, 'status' => $response->status()]);
            abort(503, 'Les photos Centris sont temporairement indisponibles.');
        }
        $media = array_values(array_filter(data_get($response->json(), 'value', []), function ($item) {
            return is_array($item) && $this->validUrl($item['MediaURL'] ?? null);
        }));
        usort($media, function ($a, $b) {
            return ($a['Order'] ?? 999) <=> ($b['Order'] ?? 999);
        });
        return array_slice(array_column($media, 'MediaURL'), 0, 12);
    }

    private function member($memberKey)
    {
        if (!$memberKey) {
            return [];
        }
        $escaped = str_replace("'", "''", $memberKey);
        try {
            $response = Http::timeout(15)->withHeaders($this->centrisHeaders())
                ->get("https://datadistributionqc.centris.ca/v1/odata/Member?\$filter=MemberKey eq '$escaped'");
            return $response->successful() ? data_get($response->json(), 'value.0', []) : [];
        } catch (\Throwable $e) {
            Log::warning('Open house member lookup failed', ['memberKey' => $memberKey, 'error' => $e->getMessage()]);
            return [];
        }
    }

    private function location(User $user)
    {
        if (!$this->readyToken($user)) {
            return [];
        }
        try {
            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer '.$user->ghl_access_token,
                'Version' => 'v3',
                'Accept' => 'application/json',
            ])->get('https://services.leadconnectorhq.com/locations/'.rawurlencode($user->id_location));
        } catch (\Throwable $e) {
            Log::warning('Open house location lookup failed', ['locationId' => $user->id_location, 'error' => $e->getMessage()]);
            return [];
        }
        if (!$response->successful()) {
            Log::warning('Open house location lookup failed', ['locationId' => $user->id_location, 'status' => $response->status()]);
            return [];
        }
        return data_get($response->json(), 'location', []);
    }

    private function readyToken(User $user)
    {
        if ($user->ghl_token_expires_at && $user->ghl_token_expires_at->lte(now()->addMinutes(2))) {
            try {
                $refreshed = GhlAuthController::refreshUserToken($user);
            } catch (\Throwable $e) {
                Log::warning('Open house token refresh failed', ['userId' => $user->id, 'error' => $e->getMessage()]);
                return false;
            }
            if (!$refreshed) {
                return false;
            }
        }
        return !empty($user->ghl_access_token);
    }

    private function centrisHeaders()
    {
        return ['Authorization' => 'Bearer '.env('CENTRIS_API_KEY'), 'Accept' => 'application/json'];
    }

    private function address(array $property)
    {
        $street = trim(($property['StreetNumberStart'] ?? '').' '.($property['StreetShortName'] ?? ''));
        return implode(', ', array_filter([$street, $property['Township'] ?? null, $property['PostalCode'] ?? null]));
    }

    private function validUrl($url)
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function userForSignedRequest(Request $request)
    {
        abort_unless($request->hasValidSignature(false), 403, 'Lien expiré ou invalide. Rechargez la fiche.');
        $locationId = $request->query('locationId');
        abort_unless(is_string($locationId) && in_array($locationId, config('open_houses.allowed_location_ids'), true), 403);
        return User::where('id_location', $locationId)->firstOrFail();
    }
}

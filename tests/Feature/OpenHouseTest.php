<?php

namespace Tests\Feature;

use App\Models\OpenHouse;
use App\Models\OpenHouseRegistration;
use App\User;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpenHouseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('id_location');
            $table->text('ghl_access_token')->nullable();
            $table->timestamp('ghl_token_expires_at')->nullable();
            $table->string('Codebureau')->nullable();
            $table->timestamps();
        });
        require_once database_path('migrations/2026_10_28_000000_create_open_houses_tables.php');
        (new \CreateOpenHousesTables)->up();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    private function user($locationId = 'FqqkdWQ0F0QPYOpLYXnz')
    {
        return User::create([
            'name' => 'Courtier',
            'id_location' => $locationId,
            'Codebureau' => 'office-1',
            'ghl_access_token' => 'test-token',
        ]);
    }

    private function openHouse(User $user)
    {
        return OpenHouse::create([
            'user_id' => $user->id,
            'id_location' => $user->id_location,
            'listing_key' => 'key-1',
            'listing_id' => '12345678',
            'public_token' => 'public-token',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'timezone' => 'America/Toronto',
            'address' => '123 Rue Test, Montréal',
            'photos' => ['https://example.com/house.jpg'],
            'broker_name' => 'Courtier Test',
        ]);
    }

    private function httpResponse(array $data, $status = 200)
    {
        return new FulfilledPromise(new Psr7Response($status, ['Content-Type' => 'application/json'], json_encode($data)));
    }

    public function test_creates_a_public_open_house_from_centris_data()
    {
        $user = $this->user();
        Http::fake([
            'datadistributionqc.centris.ca/*Property*' => $this->httpResponse(['value' => [[
                'ListingKey' => 'key-1', 'ListingId' => '12345678', 'ListOfficeKey' => 'office-1',
                'StreetNumberStart' => '123', 'StreetShortName' => 'Rue Test', 'Township' => 'Montréal',
                'ListPrice' => 450000, 'ListAgentKey' => 'agent-1',
            ]]], 200),
            'datadistributionqc.centris.ca/*Media*' => $this->httpResponse(['value' => [
                ['MediaURL' => 'https://example.com/second.jpg', 'Order' => 2],
                ['MediaURL' => 'https://example.com/first.jpg', 'Order' => 1],
            ]], 200),
            'services.leadconnectorhq.com/locations/*' => $this->httpResponse(['location' => ['timezone' => 'America/Toronto']]),
        ]);

        $start = now('America/Toronto')->addDays(2)->setTime(11, 0);
        $response = $this->actingAs($user)->postJson('/properties/key-1/open-houses', [
            'startsAt' => $start->format('Y-m-d\TH:i'),
            'endsAt' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'timezone' => 'America/Toronto',
            'brokerName' => 'Courtier Test',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['id', 'url', 'qrUrl']);
        $visit = OpenHouse::firstOrFail();
        $this->assertSame('123 Rue Test, Montréal', $visit->address);
        $this->assertSame(['https://example.com/first.jpg', 'https://example.com/second.jpg'], $visit->photos);
        $this->assertSame($start->copy()->utc()->format('Y-m-d H:i'), $visit->starts_at->format('Y-m-d H:i'));
        $this->get('/visites-libres/'.$visit->public_token)->assertOk()->assertSee('123 Rue Test');
        $this->get('/visites-libres/'.$visit->public_token.'/qr.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_registration_adds_a_tag_without_replacing_existing_tags()
    {
        $visit = $this->openHouse($this->user());
        Http::fake([
            'services.leadconnectorhq.com/contacts/upsert' => $this->httpResponse(['contact' => ['id' => 'contact-1']]),
            'services.leadconnectorhq.com/contacts/contact-1/tags' => $this->httpResponse([]),
        ]);

        $payload = [
            'firstName' => 'Alex', 'lastName' => 'Martin', 'email' => 'alex@example.com',
            'phone' => '5145550101', 'consent' => '1',
        ];
        $this->postJson('/visites-libres/'.$visit->public_token.'/inscriptions', $payload)->assertOk()->assertJson(['success' => true]);
        $this->postJson('/visites-libres/'.$visit->public_token.'/inscriptions', $payload)->assertOk();

        $this->assertSame(1, OpenHouseRegistration::count());
        Http::assertSent(function ($request) {
            return Str::endsWith($request->url(), '/contacts/upsert')
                && $request['locationId'] === 'FqqkdWQ0F0QPYOpLYXnz'
                && !isset($request['tags']);
        });
        Http::assertSent(function ($request) {
            return Str::endsWith($request->url(), '/contacts/contact-1/tags')
                && $request['tags'] === [config('open_houses.contact_tag')];
        });
    }

    public function test_failed_crm_tag_does_not_record_a_visitor()
    {
        $visit = $this->openHouse($this->user());
        Http::fake([
            'services.leadconnectorhq.com/contacts/upsert' => $this->httpResponse(['contact' => ['id' => 'contact-1']]),
            'services.leadconnectorhq.com/contacts/contact-1/tags' => $this->httpResponse([], 500),
        ]);

        $this->postJson('/visites-libres/'.$visit->public_token.'/inscriptions', [
            'firstName' => 'Alex', 'lastName' => 'Martin', 'email' => 'alex@example.com',
            'phone' => '5145550101', 'consent' => '1',
        ])->assertStatus(503);
        $this->assertSame(0, OpenHouseRegistration::count());
    }

    public function test_cannot_create_for_another_office()
    {
        $user = $this->user();
        Http::fake([
            'datadistributionqc.centris.ca/*Property*' => $this->httpResponse(['value' => [[
                'ListingKey' => 'key-1', 'ListOfficeKey' => 'other-office',
            ]]]),
        ]);

        $start = now('America/Toronto')->addDays(2)->setTime(11, 0);
        $this->actingAs($user)->postJson('/properties/key-1/open-houses', [
            'startsAt' => $start->format('Y-m-d\TH:i'),
            'endsAt' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'timezone' => 'America/Toronto',
            'brokerName' => 'Courtier Test',
        ])->assertStatus(403);
        $this->assertSame(0, OpenHouse::count());
    }

    public function test_requires_a_centris_photo()
    {
        $user = $this->user();
        Http::fake([
            'datadistributionqc.centris.ca/*Property*' => $this->httpResponse(['value' => [[
                'ListingKey' => 'key-1', 'ListOfficeKey' => 'office-1',
            ]]]),
            'datadistributionqc.centris.ca/*Media*' => $this->httpResponse(['value' => []]),
        ]);

        $start = now('America/Toronto')->addDays(2)->setTime(11, 0);
        $this->actingAs($user)->postJson('/properties/key-1/open-houses', [
            'startsAt' => $start->format('Y-m-d\TH:i'),
            'endsAt' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'timezone' => 'America/Toronto',
            'brokerName' => 'Courtier Test',
        ])->assertStatus(422);
        $this->assertSame(0, OpenHouse::count());
    }

    public function test_finished_visit_rejects_new_registrations()
    {
        $visit = $this->openHouse($this->user());
        $visit->starts_at = now()->subDays(2);
        $visit->ends_at = now()->subDay();
        $visit->save();
        Http::fake();

        $this->get('/visites-libres/'.$visit->public_token)->assertOk()->assertSee('Visite terminée');
        $this->postJson('/visites-libres/'.$visit->public_token.'/inscriptions', [
            'firstName' => 'Alex', 'lastName' => 'Martin', 'email' => 'alex@example.com',
            'phone' => '5145550101', 'consent' => '1',
        ])->assertStatus(410);
        Http::assertNothingSent();
    }

    public function test_other_locations_cannot_manage_open_houses()
    {
        $user = $this->user('another-location');
        $visit = $this->openHouse($user);
        Http::fake();

        $this->actingAs($user)->getJson('/properties/key-1/open-house/defaults')->assertStatus(403);
        $this->actingAs($user)->postJson('/properties/key-1/open-houses', [])->assertStatus(403);
        $this->actingAs($user)->getJson('/open-houses/'.$visit->id.'/registrations')->assertStatus(403);
        Http::assertNothingSent();
    }

    public function test_detail_page_only_shows_open_house_controls_for_allowed_location()
    {
        $data = [
            'property' => ['ListingKey' => 'key-1', 'ListingId' => '12345678'],
            'persons' => [], 'opportunities' => [], 'centrisSubmissions' => [],
            'openHouses' => collect(), 'idLocation' => 'another-location',
            'locationId' => 'another-location', 'memberName' => '',
        ];

        $hidden = view('centris.property-details', $data + ['openHouseEnabled' => false])->render();
        $visible = view('centris.property-details', $data + ['openHouseEnabled' => true])->render();

        $this->assertStringNotContainsString('<span>Créer une visite libre</span>', $hidden);
        $this->assertStringContainsString('<span>Créer une visite libre</span>', $visible);
    }
}

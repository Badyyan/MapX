<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
            'mapx.integrations.mock' => false,
        ]);

        $this->admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );
    }

    public function test_redirect_sends_user_to_google_consent(): void
    {
        $response = $this->actingAs($this->admin)->get('/integrations/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $response->headers->get('Location'));
        $this->assertStringContainsString('business.manage', urldecode($response->headers->get('Location')));
    }

    public function test_callback_stores_company_connection(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.test',
                'refresh_token' => '1//refresh',
                'expires_in' => 3599,
            ]),
            'openidconnect.googleapis.com/*' => Http::response(['email' => 'owner@business.sa']),
        ]);

        $this->withSession(['google_oauth_state' => 'state-123'])
            ->actingAs($this->admin)
            ->get('/integrations/google/callback?code=auth-code&state=state-123')
            ->assertRedirect(route('integrations.google.import'));

        $connection = PlatformConnection::withoutGlobalScope('company')
            ->where('company_id', $this->admin->company_id)
            ->where('platform', 'google')
            ->whereNull('branch_id')
            ->first();

        $this->assertNotNull($connection);
        $this->assertSame('connected', $connection->status);
        $this->assertSame('ya29.test', $connection->access_token);
        $this->assertSame('owner@business.sa', $connection->meta['google_email']);
    }

    public function test_callback_rejects_bad_state(): void
    {
        $this->withSession(['google_oauth_state' => 'expected'])
            ->actingAs($this->admin)
            ->get('/integrations/google/callback?code=x&state=tampered')
            ->assertForbidden();
    }

    public function test_import_creates_branches_from_gbp_locations(): void
    {
        PlatformConnection::withoutGlobalScope('company')->create([
            'company_id' => $this->admin->company_id,
            'platform' => 'google',
            'branch_id' => null,
            'status' => 'connected',
            'access_token' => 'ya29.test',
            'token_expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'mybusinessaccountmanagement.googleapis.com/*' => Http::response([
                'accounts' => [['name' => 'accounts/123', 'accountName' => 'Aroma Group', 'type' => 'LOCATION_GROUP']],
            ]),
            'mybusinessbusinessinformation.googleapis.com/*' => Http::response([
                'locations' => [[
                    'name' => 'locations/555',
                    'title' => 'Aroma — Olaya',
                    'storefrontAddress' => ['addressLines' => ['Olaya St'], 'locality' => 'Riyadh', 'regionCode' => 'SA'],
                    'latlng' => ['latitude' => 24.69, 'longitude' => 46.68],
                    'phoneNumbers' => ['primaryPhone' => '+966112345678'],
                    'categories' => ['primaryCategory' => ['displayName' => 'Coffee shop']],
                    'metadata' => ['placeId' => 'ChIJtest'],
                ]],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->post('/integrations/google/import', ['locations' => ['locations/555']])
            ->assertRedirect(route('branches.index'));

        $branch = Branch::withoutGlobalScope('company')->where('name', 'Aroma — Olaya')->first();

        $this->assertNotNull($branch);
        $this->assertSame('Riyadh', $branch->city);
        $this->assertSame('ChIJtest', $branch->google_place_id);
        $this->assertSame('verified', $branch->verification_status);

        $this->assertDatabaseHas('platform_connections', [
            'branch_id' => $branch->id,
            'platform' => 'google',
            'external_id' => 'locations/555',
            'status' => 'connected',
        ]);
    }

    public function test_generic_connect_is_blocked_for_oauth_platforms_in_real_mode(): void
    {
        Branch::create(['company_id' => $this->admin->company_id, 'name' => 'Main']);

        $this->actingAs($this->admin)
            ->post('/integrations/connect', ['platform' => 'google'])
            ->assertRedirect(route('integrations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('platform_connections', ['platform' => 'google']);
    }
}

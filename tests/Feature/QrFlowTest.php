<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\QrCampaign;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private QrCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );

        $branch = Branch::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Main',
            'lat' => 24.7,
            'lng' => 46.6,
        ]);

        $this->campaign = QrCampaign::create([
            'company_id' => $this->admin->company_id,
            'branch_id' => $branch->id,
            'name' => 'Table QR',
            'slug' => 'testslug123',
            'rating_scale' => 10,
            'threshold' => 8,
        ]);
    }

    public function test_scan_increments_counter(): void
    {
        $this->get('/f/testslug123')->assertOk();

        $this->assertSame(1, $this->campaign->fresh()->scans_count);
    }

    public function test_positive_rating_redirects_to_public_review_page(): void
    {
        // FR-19: rating >= threshold goes to the public review page.
        $response = $this->post('/f/testslug123/rate', ['rating' => 9]);

        $response->assertRedirect();
        $this->assertStringContainsString('google.com/maps', $response->headers->get('Location'));
        $this->assertSame(1, $this->campaign->fresh()->positive_count);
    }

    public function test_negative_rating_shows_internal_form_and_stores_feedback(): void
    {
        // FR-19/FR-20: rating < threshold stays internal.
        $this->post('/f/testslug123/rate', ['rating' => 4])
            ->assertOk()
            ->assertSee('name="customer_name"', false);

        $this->post('/f/testslug123/feedback', [
            'rating' => 4,
            'comment' => 'Order was late',
            'customer_name' => 'Ahmed',
            'customer_phone' => '+966500000000',
        ])->assertRedirect(route('feedback.thanks', 'testslug123'));

        $this->assertDatabaseHas('qr_feedback', [
            'qr_campaign_id' => $this->campaign->id,
            'rating' => 4,
            'comment' => 'Order was late',
        ]);

        // Internal feedback never creates a public review (FR-20).
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_inactive_campaign_is_not_reachable(): void
    {
        $this->campaign->update(['is_active' => false]);

        $this->get('/f/testslug123')->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_provisions_company_roles_and_trial(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Test Cafe',
            'industry' => 'Restaurants & Cafes',
            'name' => 'Owner',
            'email' => 'owner@test.sa',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'locale' => 'en',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'owner@test.sa')->firstOrFail();

        $this->assertNotNull($user->company_id);
        $this->assertTrue($user->isCompanyAdmin());
        $this->assertCount(4, $user->company->roles); // admin, manager, agent, viewer

        $subscription = $user->company->subscription;
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame(3, $subscription->branch_limit); // FR-30
        $this->assertTrue($subscription->trial_ends_at->isFuture()); // FR-29
    }
}

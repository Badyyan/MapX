<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );
    }

    public function test_admin_can_create_branch(): void
    {
        $response = $this->actingAs($this->admin)->post('/branches', [
            'name' => 'Main Branch',
            'city' => 'Riyadh',
            'lat' => 24.7136,
            'lng' => 46.6753,
            'categories' => 'Cafe, Bakery',
        ]);

        $branch = Branch::withoutGlobalScope('company')->where('name', 'Main Branch')->firstOrFail();

        $response->assertRedirect(route('branches.show', $branch));
        $this->assertSame($this->admin->company_id, $branch->company_id);
        $this->assertSame(['Cafe', 'Bakery'], $branch->categories);
    }

    public function test_trial_branch_limit_is_enforced(): void
    {
        // FR-30: trial allows max 3 branches.
        foreach (range(1, 3) as $i) {
            $this->actingAs($this->admin)->post('/branches', ['name' => "Branch $i"]);
        }

        $this->actingAs($this->admin)
            ->post('/branches', ['name' => 'Branch 4'])
            ->assertSessionHas('error');

        $this->assertSame(3, Branch::withoutGlobalScope('company')
            ->where('company_id', $this->admin->company_id)->count());
    }

    public function test_branches_are_tenant_isolated(): void
    {
        $other = app(CompanyProvisioner::class)->provision(
            ['name' => 'Other Co'],
            ['name' => 'Other', 'email' => 'other@other.sa', 'password' => 'password123'],
        );

        $this->actingAs($other)->post('/branches', ['name' => 'Foreign Branch']);
        $foreign = Branch::withoutGlobalScope('company')->where('name', 'Foreign Branch')->first();

        // The first tenant can neither see nor open the other tenant's branch.
        $this->actingAs($this->admin)->get('/branches')->assertDontSee('Foreign Branch');
        $this->actingAs($this->admin)->get("/branches/{$foreign->id}")->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_manage_branches_but_can_view(): void
    {
        $admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );

        $viewerRole = $admin->company->roles()->where('slug', 'viewer')->first();
        $viewer = User::create([
            'company_id' => $admin->company_id,
            'role_id' => $viewerRole->id,
            'name' => 'Viewer',
            'email' => 'viewer@acme.sa',
            'password' => 'password123',
        ]);

        $this->actingAs($viewer)->get('/branches')->assertOk();
        $this->actingAs($viewer)->post('/branches', ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($viewer)->get('/team')->assertForbidden();
        $this->actingAs($viewer)->get('/billing')->assertForbidden();
    }

    public function test_admin_can_create_custom_role_with_predefined_permissions(): void
    {
        $admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );

        $this->actingAs($admin)->post('/roles', [
            'name' => 'Marketing',
            'permissions' => ['posts.view', 'posts.manage', 'analytics.view'],
        ])->assertRedirect();

        $role = $admin->company->roles()->where('name', 'Marketing')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermission('posts.manage'));
        $this->assertFalse($role->hasPermission('billing.manage'));
    }

    public function test_invalid_permission_is_rejected(): void
    {
        $admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );

        $this->actingAs($admin)->post('/roles', [
            'name' => 'Hacker',
            'permissions' => ['not.a.permission'],
        ])->assertSessionHasErrors('permissions.0');
    }
}

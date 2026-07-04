<?php

namespace Tests\Feature;

use App\Jobs\SyncPlatformConnection;
use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IntegrationSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(CompanyProvisioner::class)->provision(
            ['name' => 'Acme'],
            ['name' => 'Admin', 'email' => 'admin@acme.sa', 'password' => 'password123'],
        );

        $this->branch = Branch::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Main',
            'lat' => 24.7,
            'lng' => 46.6,
            'phone' => '+966111111111',
        ]);
    }

    public function test_connect_without_branch_targets_all_branches(): void
    {
        Queue::fake();

        // Regression: connecting platform-wide (no branch_id in the request)
        // must not crash on the absent key.
        $this->actingAs($this->admin)
            ->post('/integrations/connect', ['platform' => 'google'])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_connections', [
            'company_id' => $this->admin->company_id,
            'branch_id' => $this->branch->id,
            'platform' => 'google',
            'status' => 'connected',
        ]);

        Queue::assertPushed(SyncPlatformConnection::class);
    }

    public function test_sync_job_runs_and_marks_connection_synced(): void
    {
        // Regression: the job's model property must not collide with the
        // Queueable trait's $connection (queue name) property.
        $connection = PlatformConnection::create([
            'company_id' => $this->admin->company_id,
            'branch_id' => $this->branch->id,
            'platform' => 'google',
            'status' => 'connected',
        ]);

        (new SyncPlatformConnection($connection))->handle(app(\App\Services\PlatformManager::class));

        $connection->refresh();
        $this->assertSame('synced', $connection->sync_status);
        $this->assertGreaterThan(0, $connection->data_completeness);
        $this->assertNotNull($connection->last_synced_at);
    }

    public function test_unknown_platform_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/integrations/connect', ['platform' => 'myspace'])
            ->assertNotFound();
    }
}

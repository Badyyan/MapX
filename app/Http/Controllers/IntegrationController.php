<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPlatformConnection;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PlatformConnection;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index()
    {
        $connections = PlatformConnection::with('branch')->get()->groupBy('platform');

        return view('integrations.index', [
            'platforms' => config('mapx.platforms'),
            'connections' => $connections,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    /**
     * Connect a platform for one branch or all branches. In real mode this
     * is where the OAuth redirect starts; in mock mode the connection is
     * established immediately so the full flow can be exercised.
     */
    public function connect(Request $request)
    {
        $data = $request->validate([
            'platform' => ['required', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        abort_unless(array_key_exists($data['platform'], config('mapx.platforms')), 404);

        $branchIds = ($data['branch_id'] ?? null)
            ? [$data['branch_id']]
            : Branch::pluck('id')->all();

        if (empty($branchIds)) {
            return back()->with('error', __('Add a branch before connecting platforms.'));
        }

        foreach ($branchIds as $branchId) {
            $connection = PlatformConnection::firstOrCreate(
                [
                    'company_id' => $request->user()->company_id,
                    'branch_id' => $branchId,
                    'platform' => $data['platform'],
                ],
                ['status' => 'connected', 'sync_status' => 'pending'],
            );

            $connection->update(['status' => 'connected']);
            SyncPlatformConnection::dispatch($connection);
        }

        AuditLog::record('integration.connected', null, ['platform' => $data['platform']]);

        return back()->with('success', __(':platform connected. Synchronization started.', [
            'platform' => config("mapx.platforms.{$data['platform']}.name"),
        ]));
    }

    public function sync(PlatformConnection $connection)
    {
        SyncPlatformConnection::dispatch($connection);

        return back()->with('success', __('Synchronization queued.'));
    }

    public function syncAll()
    {
        PlatformConnection::where('status', 'connected')
            ->each(fn ($connection) => SyncPlatformConnection::dispatch($connection));

        return back()->with('success', __('Synchronization queued for all connections.'));
    }

    public function disconnect(PlatformConnection $connection)
    {
        $platform = $connection->platformName();
        $connection->delete();
        AuditLog::record('integration.disconnected', null, ['platform' => $platform]);

        return back()->with('success', __(':platform disconnected.', ['platform' => $platform]));
    }
}

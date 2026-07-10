<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPlatformConnection;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Services\Google\GoogleOAuthService;
use App\Services\Meta\MetaOAuthService;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    /** Platforms whose real connection happens through OAuth, not the generic connect. */
    private const OAUTH_PLATFORMS = ['google', 'facebook', 'instagram'];

    /** Platforms that need no connection at all — deep links are generated from branch data. */
    private const AUTOMATIC_PLATFORMS = ['waze', 'uber', 'careem', 'bolt', 'snap'];

    public function index(GoogleOAuthService $google, MetaOAuthService $meta)
    {
        $connections = PlatformConnection::with('branch')->whereNotNull('branch_id')->get()->groupBy('platform');

        return view('integrations.index', [
            'platforms' => config('mapx.platforms'),
            'connections' => $connections,
            'branches' => Branch::orderBy('name')->get(),
            'mockMode' => (bool) config('mapx.integrations.mock'),
            'oauthPlatforms' => self::OAUTH_PLATFORMS,
            'automaticPlatforms' => self::AUTOMATIC_PLATFORMS,
            'googleConfigured' => $google->isConfigured(),
            'googleConnection' => $google->companyConnection(auth()->user()->company_id),
            'metaConfigured' => $meta->isConfigured(),
            'metaConnection' => $meta->companyConnection(auth()->user()->company_id),
        ]);
    }

    /**
     * Generic connect — used for deep-link platforms always, and for OAuth
     * platforms only in mock/demo mode. Real Google/Meta connections go
     * through their OAuth controllers.
     */
    public function connect(Request $request)
    {
        $data = $request->validate([
            'platform' => ['required', 'string'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        abort_unless(array_key_exists($data['platform'], config('mapx.platforms')), 404);

        if (! config('mapx.integrations.mock') && in_array($data['platform'], self::OAUTH_PLATFORMS, true)) {
            return redirect()->route('integrations.index')
                ->with('error', __('This platform connects through its official sign-in flow. Use the Connect button on its card.'));
        }

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
            ->whereNotNull('branch_id')
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

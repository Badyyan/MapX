<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Services\Meta\MetaOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MetaIntegrationController extends Controller
{
    public function __construct(private MetaOAuthService $oauth)
    {
    }

    public function redirect(Request $request)
    {
        if (! $this->oauth->isConfigured()) {
            return redirect()->route('integrations.index')
                ->with('error', __('Meta integration is not configured yet. Add META_APP_ID and META_APP_SECRET first.'));
        }

        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);

        return redirect()->away($this->oauth->redirectUrl($state));
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('integrations.index')->with('error', __('Facebook connection was cancelled.'));
        }

        if ($request->query('state') !== $request->session()->pull('meta_oauth_state')) {
            abort(403, 'Invalid OAuth state.');
        }

        $tokens = $this->oauth->exchangeCode($request->query('code'));

        PlatformConnection::withoutGlobalScope('company')->updateOrCreate(
            [
                'company_id' => $request->user()->company_id,
                'platform' => 'facebook',
                'branch_id' => null,
            ],
            [
                'status' => 'connected',
                'sync_status' => 'synced',
                'access_token' => $tokens['access_token'],
                'token_expires_at' => isset($tokens['expires_in']) ? now()->addSeconds($tokens['expires_in']) : now()->addDays(60),
                'last_synced_at' => now(),
            ],
        );

        AuditLog::record('integration.meta.connected');

        return redirect()->route('integrations.meta.pages')
            ->with('success', __('Facebook connected. Map your pages to branches.'));
    }

    /** Map Facebook pages (and linked Instagram accounts) to branches. */
    public function showPages(Request $request)
    {
        $connection = $this->oauth->companyConnection($request->user()->company_id);

        if (! $connection) {
            return redirect()->route('integrations.index')->with('error', __('Connect Facebook first.'));
        }

        try {
            $pages = $this->oauth->pages($connection->access_token);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('integrations.index')
                ->with('error', __('Could not load your Facebook pages: :reason', ['reason' => Str::limit($e->getMessage(), 160)]));
        }

        return view('integrations.meta-pages', [
            'pages' => $pages,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    /** Link one page to one branch (creates FB + IG connections). */
    public function connectPage(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'page_id' => ['required', 'string'],
        ]);

        $connection = $this->oauth->companyConnection($request->user()->company_id);
        abort_unless($connection, 403);

        $page = collect($this->oauth->pages($connection->access_token))
            ->firstWhere('id', $data['page_id']);

        abort_unless($page, 404, 'Page not found on this Facebook account.');

        PlatformConnection::updateOrCreate(
            [
                'company_id' => $request->user()->company_id,
                'branch_id' => $data['branch_id'],
                'platform' => 'facebook',
            ],
            [
                'status' => 'connected',
                'sync_status' => 'synced',
                'external_id' => $page['id'],
                'access_token' => $page['access_token'],   // page tokens don't expire while the user token is valid
                'last_synced_at' => now(),
                'meta' => ['page_name' => $page['name']],
            ],
        );

        if (! empty($page['instagram_business_account']['id'])) {
            PlatformConnection::updateOrCreate(
                [
                    'company_id' => $request->user()->company_id,
                    'branch_id' => $data['branch_id'],
                    'platform' => 'instagram',
                ],
                [
                    'status' => 'connected',
                    'sync_status' => 'synced',
                    'external_id' => $page['instagram_business_account']['id'],
                    'access_token' => $page['access_token'],
                    'last_synced_at' => now(),
                    'meta' => ['username' => $page['instagram_business_account']['username'] ?? null],
                ],
            );
        }

        AuditLog::record('integration.meta.page_connected', null, ['page' => $page['name']]);

        return back()->with('success', __(':page linked.', ['page' => $page['name']]));
    }
}

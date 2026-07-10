<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PlatformConnection;
use App\Services\Google\GbpDirectory;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Real Google Business Profile integration (approach B):
 * OAuth connect -> list every location the business already has on Google
 * -> import selected locations as branches, linked for ongoing sync.
 */
class GoogleIntegrationController extends Controller
{
    public function __construct(
        private GoogleOAuthService $oauth,
        private GbpDirectory $directory,
    ) {
    }

    /** Kick off the OAuth consent flow. */
    public function redirect(Request $request)
    {
        if (! $this->oauth->isConfigured()) {
            return redirect()->route('integrations.index')
                ->with('error', __('Google integration is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET first.'));
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->oauth->redirectUrl($state));
    }

    /** OAuth callback: store tokens on a company-level connection. */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('integrations.index')
                ->with('error', __('Google connection was cancelled.'));
        }

        if ($request->query('state') !== $request->session()->pull('google_oauth_state')) {
            abort(403, 'Invalid OAuth state.');
        }

        $tokens = $this->oauth->exchangeCode($request->query('code'));
        $email = $this->oauth->userEmail($tokens['access_token']);

        PlatformConnection::withoutGlobalScope('company')->updateOrCreate(
            [
                'company_id' => $request->user()->company_id,
                'platform' => 'google',
                'branch_id' => null,
            ],
            [
                'status' => 'connected',
                'sync_status' => 'synced',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                'last_synced_at' => now(),
                'meta' => ['google_email' => $email],
            ],
        );

        AuditLog::record('integration.google.connected', null, ['email' => $email]);

        return redirect()->route('integrations.google.import')
            ->with('success', __('Google connected as :email. Choose the locations to import.', ['email' => $email]));
    }

    /** List the company's GBP locations for import. */
    public function showImport(Request $request)
    {
        $connection = $this->oauth->companyConnection($request->user()->company_id);

        if (! $connection) {
            return redirect()->route('integrations.index')->with('error', __('Connect Google first.'));
        }

        try {
            $token = $this->oauth->tokenFor($connection);

            $locations = collect($this->directory->accounts($token))
                ->flatMap(fn ($account) => $this->directory->locations($token, $account['name']));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('integrations.index')
                ->with('error', __('Could not load your Google locations: :reason', ['reason' => Str::limit($e->getMessage(), 160)]));
        }

        $importedIds = PlatformConnection::where('platform', 'google')
            ->whereNotNull('branch_id')
            ->pluck('external_id')
            ->all();

        return view('integrations.google-import', [
            'locations' => $locations,
            'importedIds' => $importedIds,
            'googleEmail' => $connection->meta['google_email'] ?? null,
        ]);
    }

    /** Import the selected locations as branches. */
    public function import(Request $request)
    {
        $data = $request->validate([
            'locations' => ['required', 'array', 'min:1'],
            'locations.*' => ['string'],
        ]);

        $connection = $this->oauth->companyConnection($request->user()->company_id);
        abort_unless($connection, 403);

        $token = $this->oauth->tokenFor($connection);

        $available = collect($this->directory->accounts($token))
            ->flatMap(fn ($account) => $this->directory->locations($token, $account['name']))
            ->keyBy('gbp_name');

        $imported = 0;

        foreach ($data['locations'] as $gbpName) {
            $location = $available->get($gbpName);

            if (! $location || PlatformConnection::where('platform', 'google')->where('external_id', $gbpName)->exists()) {
                continue;
            }

            $branch = Branch::create([
                'company_id' => $request->user()->company_id,
                'name' => $location['title'] ?: __('Unnamed location'),
                'address' => $location['address'] ?: null,
                'city' => $location['city'],
                'region' => $location['region'],
                'country' => $location['country'],
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'phone' => $location['phone'],
                'website' => $location['website'],
                'categories' => $location['categories'] ?: null,
                'hours' => $location['hours'],
                'google_place_id' => $location['place_id'],
                'verification_status' => 'verified', // it already exists on Google
            ]);

            PlatformConnection::create([
                'company_id' => $request->user()->company_id,
                'branch_id' => $branch->id,
                'platform' => 'google',
                'status' => 'connected',
                'sync_status' => 'synced',
                'external_id' => $gbpName,
                'data_completeness' => $branch->completenessScore(),
                'last_synced_at' => now(),
                'meta' => ['account' => $location['gbp_account'], 'maps_uri' => $location['maps_uri']],
            ]);

            $imported++;
        }

        AuditLog::record('integration.google.imported', null, ['count' => $imported]);

        return redirect()->route('branches.index')
            ->with('success', __(':count locations imported from Google Business Profile.', ['count' => $imported]));
    }

    /** Disconnect Google entirely (company token + branch links). */
    public function disconnect(Request $request)
    {
        PlatformConnection::where('platform', 'google')->delete();

        AuditLog::record('integration.google.disconnected');

        return redirect()->route('integrations.index')->with('success', __('Google disconnected.'));
    }
}

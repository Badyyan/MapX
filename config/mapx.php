<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform catalog
    |--------------------------------------------------------------------------
    | Platforms supported in the MVP per the BRD. "capabilities" drive what
    | the UI offers per platform; "driver" maps to a PlatformAdapter class.
    */
    'platforms' => [
        'google' => [
            'name' => 'Google Business Profile',
            'capabilities' => ['sync', 'posts', 'reviews', 'insights', 'duplicates', 'verification'],
            'driver' => \App\Services\Platforms\GoogleBusinessProfileAdapter::class,
            'color' => '#4285F4',
        ],
        'facebook' => [
            'name' => 'Facebook Pages',
            'capabilities' => ['sync', 'posts', 'reviews', 'insights'],
            'driver' => \App\Services\Platforms\FacebookAdapter::class,
            'color' => '#1877F2',
        ],
        'instagram' => [
            'name' => 'Instagram Business',
            'capabilities' => ['posts', 'insights'],
            'driver' => \App\Services\Platforms\InstagramAdapter::class,
            'color' => '#E4405F',
        ],
        'waze' => [
            'name' => 'Waze',
            'capabilities' => ['deeplink', 'visibility'],
            'driver' => \App\Services\Platforms\WazeAdapter::class,
            'color' => '#33CCFF',
        ],
        'snap' => [
            'name' => 'Snap Map',
            'capabilities' => ['visibility'],
            'driver' => \App\Services\Platforms\SnapMapAdapter::class,
            'color' => '#FFFC00',
        ],
        'uber' => [
            'name' => 'Uber',
            'capabilities' => ['deeplink'],
            'driver' => \App\Services\Platforms\RideDeepLinkAdapter::class,
            'color' => '#000000',
        ],
        'careem' => [
            'name' => 'Careem',
            'capabilities' => ['deeplink'],
            'driver' => \App\Services\Platforms\RideDeepLinkAdapter::class,
            'color' => '#00B140',
        ],
        'bolt' => [
            'name' => 'Bolt',
            'capabilities' => ['deeplink'],
            'driver' => \App\Services\Platforms\RideDeepLinkAdapter::class,
            'color' => '#34D186',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission catalog (predefined list per BRD 5.7)
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'branches.view', 'branches.manage',
        'integrations.view', 'integrations.manage',
        'reviews.view', 'reviews.reply',
        'auto_rules.manage',
        'qr.view', 'qr.manage',
        'posts.view', 'posts.manage',
        'analytics.view',
        'rank.view', 'rank.manage',
        'users.manage',
        'roles.manage',
        'billing.manage',
        'verification.manage',
        'audit.view',
        'ads.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default role templates (cloned into each new company)
    |--------------------------------------------------------------------------
    */
    'role_templates' => [
        'admin' => ['name' => 'Company Admin', 'permissions' => '*'],
        'manager' => [
            'name' => 'Branch Manager',
            'permissions' => [
                'branches.view', 'branches.manage', 'integrations.view',
                'reviews.view', 'reviews.reply', 'qr.view', 'qr.manage',
                'posts.view', 'posts.manage', 'analytics.view', 'rank.view',
                'verification.manage',
            ],
        ],
        'agent' => [
            'name' => 'Review Agent',
            'permissions' => ['reviews.view', 'reviews.reply', 'qr.view', 'analytics.view'],
        ],
        'viewer' => [
            'name' => 'Viewer',
            'permissions' => ['branches.view', 'reviews.view', 'posts.view', 'analytics.view', 'rank.view', 'qr.view', 'integrations.view'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing (BRD 5.8)
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'price_per_branch' => 99.00,      // SAR / branch / month
        'yearly_discount' => 0.20,        // 20 %
        'currency' => 'SAR',
        'trial_days' => 7,
        'trial_branch_limit' => 3,

        // auto  = use the first driver below that has credentials, else manual
        // manual = sandbox billing, nothing is charged
        // stripe|moyasar = force that driver (falls back to manual if unconfigured)
        'gateway' => env('MAPX_BILLING_GATEWAY', 'auto'),

        'drivers' => [
            'manual' => \App\Services\Billing\ManualGateway::class,
            'stripe' => \App\Services\Billing\StripeGateway::class,
            'moyasar' => \App\Services\Billing\MoyasarGateway::class,
        ],

        // Dunning for gateways MapX renews itself (Moyasar). 3 attempts at
        // 24 h fits inside the 3-day grace window that the billing-lifecycle
        // schedule uses before it locks an account.
        'renewal' => [
            'max_attempts' => (int) env('MAPX_RENEWAL_MAX_ATTEMPTS', 3),
            'retry_hours' => (int) env('MAPX_RENEWAL_RETRY_HOURS', 24),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI engine
    |--------------------------------------------------------------------------
    | When no API key is configured the AiService falls back to a deterministic
    | offline generator so every feature stays usable in dev/demo mode.
    */
    'ai' => [
        'provider' => env('MAPX_AI_PROVIDER', 'offline'), // openai|anthropic|offline
        'api_key' => env('MAPX_AI_API_KEY'),
        'model' => env('MAPX_AI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('MAPX_AI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration credentials (real API mode)
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'mock' => env('MAPX_INTEGRATIONS_MOCK', true),
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        ],
        'meta' => [
            'app_id' => env('META_APP_ID'),
            'app_secret' => env('META_APP_SECRET'),
        ],
        'google_ads' => [
            'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        ],
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
     | Moyasar — the Saudi gateway (mada, Apple Pay, Visa/Mastercard, STC Pay).
     | Stripe does not onboard KSA merchant entities, so this is the driver
     | that actually takes money locally. Keys are pk_test_/sk_test_ in test
     | mode and pk_live_/sk_live_ in production.
     */
    'moyasar' => [
        'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
        'secret_key' => env('MOYASAR_SECRET_KEY'),
        'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET'),
        'base_url' => env('MOYASAR_BASE_URL', 'https://api.moyasar.com/v1'),
        // Payment methods offered on the embedded form, in display order.
        'methods' => env('MOYASAR_METHODS', 'creditcard,applepay,stcpay'),
        // Apple Pay merchant label shown in the sheet; domain must be verified.
        'apple_pay_label' => env('MOYASAR_APPLE_PAY_LABEL', env('APP_NAME', 'MapX')),
        'apple_pay_country' => env('MOYASAR_APPLE_PAY_COUNTRY', 'SA'),
    ],

];

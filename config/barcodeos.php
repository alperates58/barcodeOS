<?php

return [
    'plans' => [
        'default_free_slug' => env('BARCODEOS_DEFAULT_FREE_PLAN', 'free'),
    ],

    'admin' => [
        'roles' => [
            'super-admin',
            'admin',
        ],
    ],

    'coolify' => [
        'enabled' => (bool) env('COOLIFY_ENABLED', false),
        'webhook_url' => env('COOLIFY_WEBHOOK_URL'),
        'webhook_secret' => env('COOLIFY_WEBHOOK_SECRET'),
        'api_token' => env('COOLIFY_API_TOKEN'),
        'deploy_branch' => env('COOLIFY_DEPLOY_BRANCH', 'main'),
        'deploy_method' => env('COOLIFY_DEPLOY_METHOD', 'POST'),
        'timeout' => (int) env('COOLIFY_REQUEST_TIMEOUT', 15),
    ],

    'payment_providers' => [
        'stripe' => [
            'enabled' => (bool) env('STRIPE_ENABLED', false),
            'credentials' => [
                'key' => env('STRIPE_KEY'),
                'secret' => env('STRIPE_SECRET'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            ],
        ],
        'paddle' => [
            'enabled' => (bool) env('PADDLE_ENABLED', false),
            'credentials' => [
                'vendor_id' => env('PADDLE_VENDOR_ID'),
                'api_key' => env('PADDLE_API_KEY'),
            ],
        ],
        'paypal' => [
            'enabled' => (bool) env('PAYPAL_ENABLED', false),
            'credentials' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            ],
        ],
        'iyzico' => [
            'enabled' => (bool) env('IYZICO_ENABLED', false),
            'credentials' => [
                'api_key' => env('IYZICO_API_KEY'),
                'secret_key' => env('IYZICO_SECRET_KEY'),
            ],
        ],
        'manual-bank-transfer' => [
            'enabled' => (bool) env('MANUAL_BANK_TRANSFER_ENABLED', false),
            'credentials' => [
                'instructions' => env('MANUAL_BANK_TRANSFER_INSTRUCTIONS'),
            ],
        ],
    ],
];

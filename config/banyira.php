<?php

// Operational defaults. Financing rules (fees, ceilings, risk-band limits) are
// NOT here: they live in the versioned rule_sets tables and change only
// through the approval workflow.

return [

    'currency' => 'MWK',

    'marketplace' => [
        'min_offer' => env('BANYIRA_MIN_OFFER', '50000.00'),
        'offer_expiry_days' => (int) env('BANYIRA_OFFER_EXPIRY_DAYS', 14),
        'listing_days' => (int) env('BANYIRA_LISTING_DAYS', 30),
    ],

    'security' => [
        'customer_max_attempts' => 5,
        'staff_max_attempts' => 5,
        'lockout_minutes' => 15,
        'staff_idle_minutes' => 15,
    ],

    // A hosted demonstration: fictional data only. Passwords come from the
    // host's settings, never from the repository.
    'demo' => [
        'public' => (bool) env('PUBLIC_DEMO', false),
        'customer_password' => env('DEMO_CUSTOMER_PASSWORD'),
        'staff_password' => env('DEMO_STAFF_PASSWORD'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'sender_id' => env('SMS_SENDER_ID', 'BANYIRA'),
        'africastalking' => [
            'username' => env('AFRICASTALKING_USERNAME'),
            'api_key' => env('AFRICASTALKING_API_KEY'),
        ],
    ],

    'payments' => [
        'driver' => env('PAYMENTS_DRIVER', 'demo'),
        'webhook_secret' => env('PAYMENTS_WEBHOOK_SECRET'),
        'airtel' => [
            'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
            'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
        ],
        'tnm' => [
            'api_key' => env('TNM_MPAMBA_API_KEY'),
        ],
    ],

    'identity' => [
        'driver' => env('IDENTITY_DRIVER', 'manual'),
    ],

    'documents_disk' => env('DOCUMENTS_DISK', 'documents'),

    // Pictures uploaded for the public website.
    'media_disk' => env('MEDIA_DISK', 'media'),
    'media_max_kb' => 4096,

    // Database backups. A backup holds everything in the database, so the disk
    // must be private and, in production, outside the web server.
    'backups' => [
        'disk' => env('BACKUP_DISK', 'backups'),
        'timeout_seconds' => (int) env('BACKUP_TIMEOUT', 600),
        'mysqldump' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
        'pg_dump' => env('BACKUP_PG_DUMP_PATH', 'pg_dump'),
    ],

];

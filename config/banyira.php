<?php

return [
    /*
    | Branding is configurable: set BANYIRA_BRAND in .env (the working name is temporary).
    */
    'brand' => env('BANYIRA_BRAND', 'Banyira MicroCredit'),
    'tagline' => env('BANYIRA_TAGLINE', 'Lend to Malawian businesses. Grow with them.'),
    'support_email' => env('BANYIRA_SUPPORT_EMAIL', 'support@example.test'),
    'support_phone' => env('BANYIRA_SUPPORT_PHONE'),   // shown on the public site; null shows a visible placeholder
    'support_hours' => env('BANYIRA_SUPPORT_HOURS'),

    /*
    | This build can only run in SIMULATION mode. Setting operating_mode=live is refused unless every compliance
    | check is approved, and even then there is no live payment provider implemented (see docs/compliance.md).
    */
    'live_provider_implemented' => false,

    // Shared secret for the SMS gateway's signed inbound callbacks (derived from APP_KEY when empty).
    'sms_webhook_secret' => env('SMS_WEBHOOK_SECRET'),

    // Shared secret for the sandbox provider's signed webhooks. Derived from APP_KEY unless set explicitly.
    'sandbox_webhook_secret' => env('SANDBOX_WEBHOOK_SECRET'),

    'documents' => [
        'required' => ['business_registration', 'owner_id', 'bank_statements'],
        'optional' => ['financial_statements', 'tax_clearance', 'collateral', 'other'],
        'max_kilobytes' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    'sectors' => ['Agriculture', 'Retail & trade', 'Manufacturing', 'Transport & logistics', 'Services', 'Construction', 'Food & hospitality', 'Technology', 'Education', 'Health', 'Mining', 'Tourism'],
    'districts' => ['Blantyre', 'Lilongwe', 'Mzuzu', 'Zomba', 'Mangochi', 'Salima', 'Kasungu', 'Mchinji', 'Dedza', 'Ntcheu', 'Machinga', 'Balaka', 'Thyolo', 'Mulanje', 'Chikwawa', 'Karonga', 'Rumphi', 'Nkhata Bay', 'Nkhotakota', 'Dowa'],
];

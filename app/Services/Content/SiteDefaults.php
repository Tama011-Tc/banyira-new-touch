<?php

namespace App\Services\Content;

/** The website wording used until an editor changes it. */
class SiteDefaults
{
    public static function settings(): array
    {
        return [
            'announcement' => ['enabled' => false, 'text' => '', 'link' => ''],
            'footer_about' => 'A peer-to-peer marketplace connecting Malawian businesses that need financing with people who want to invest in them.',
            'footer_location' => 'Lilongwe, Malawi',
            'footer_notice' => 'Applying for financing does not guarantee approval or funding.',
            'risk_statement' => 'Investors may experience delayed repayments or lose some or all of their invested capital. Banyira\'s credit assessments and risk classifications do not guarantee repayment or returns.',
        ];
    }
}

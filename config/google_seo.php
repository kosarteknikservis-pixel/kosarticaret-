<?php

/**
 * Google Search Console + GA4 API (aylik B1 otomasyonu).
 * JSON key repo disinda tutulur; .env ile yol verilir.
 */
return [
    'credentials_path' => env('GOOGLE_SEO_CREDENTIALS'),

    'gsc_site_url' => env('GSC_SITE_URL', 'https://kosarticaret.com/'),

    'ga4_property_id' => env('GA4_PROPERTY_ID'),

    /*
    | Aylik B1 varsayilan donem (gun). Manuel GSC exportu "Son 3 ay" ile uyumlu.
    */
    'default_period_days' => (int) env('SEO_MONTHLY_PERIOD_DAYS', 90),

    'monthly_output_root' => storage_path('seo-reports/monthly'),
];

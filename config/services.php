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

    'telegram' => [
        'enabled' => env('TELEGRAM_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'dataforseo' => [
        'login' => env('DATAFORSEO_USERNAME'),
        'password' => env('DATAFORSEO_PASSWORD'),
        'base_url' => env('DATAFORSEO_BASE_URL', 'https://api.dataforseo.com'),
        'location_code' => (int) env('DATAFORSEO_LOCATION_CODE', 2792),
        'language_code' => env('DATAFORSEO_LANGUAGE_CODE', 'tr'),
        'shopping_depth' => (int) env('DATAFORSEO_SHOPPING_DEPTH', 40),
        'poll_timeout' => (int) env('DATAFORSEO_POLL_TIMEOUT', 75),
        'poll_interval' => (int) env('DATAFORSEO_POLL_INTERVAL', 3),
        'min_match_score' => (float) env('DATAFORSEO_MIN_MATCH_SCORE', 0.34),
        'min_match_score_relaxed' => (float) env('DATAFORSEO_MIN_MATCH_SCORE_RELAXED', 0.28),
        'price_band_min' => (float) env('DATAFORSEO_PRICE_BAND_MIN', 0.55),
        'price_band_min_relaxed' => (float) env('DATAFORSEO_PRICE_BAND_MIN_RELAXED', 0.48),
        'price_band_max' => (float) env('DATAFORSEO_PRICE_BAND_MAX', 2.25),
        'outlier_floor' => (float) env('DATAFORSEO_OUTLIER_FLOOR', 0.75),
    ],

];

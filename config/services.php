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

    // Self-call via Nginx catch-all — kept as HTTP for legacy SampleSiteController cached fetches
    // Both keys read the same MENUDIRECT_INTAKE_TOKEN env: services.portal.menudirect_intake_token
    // matches what MenuDirectController (ported from sos-tech) reads; services.menudirect.intake_token
    // matches what MenudirectLeadController API endpoint reads. Same secret, two config paths.
    'portal' => [
        'url' => env('PORTAL_API_URL', 'http://127.0.0.1'),
        'menudirect_intake_token' => env('MENUDIRECT_INTAKE_TOKEN'),
    ],

    // Bearer token shared with sos-tech.ca for inbound lead intake on /api/menudirect/leads
    'menudirect' => [
        'intake_token' => env('MENUDIRECT_INTAKE_TOKEN'),
    ],

    // Cloudflare Turnstile — defenses for public-facing forms
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    // IndexNow — ping Bing/Yandex when URLs change
    'indexnow' => [
        'key' => env('INDEXNOW_KEY'),
    ],

];

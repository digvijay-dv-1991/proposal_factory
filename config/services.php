<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        // Client's authorized model is gpt-5.6-terra (must support the
        // Responses API's hosted web_search tool for the Competitive
        // Analysis / Contracting Officer research calls) — the fallback
        // here matches that so a missing OPENAI_MODEL env var on a server
        // can never silently substitute an unauthorized model.
        'model' => env('OPENAI_MODEL', 'gpt-5.6-terra'),
    ],

    'sam_gov' => [
        'key' => env('SAM_GOV_API_KEY'),
    ],

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

];

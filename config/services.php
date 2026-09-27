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

    /*
    |----------------------------------------------------------------------
    | Groq AI assistant (SRS 1.6 Customer feature 7 — optional AI chatbot)
    | Free API key: https://console.groq.com/keys
    | Leave GROQ_API_KEY empty to fall back to the built-in rule-based bot.
    |----------------------------------------------------------------------
    */
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'qwen/qwen3.8-27b'),
        'enabled' => env('GROQ_ENABLED', true) === 'true' || env('GROQ_ENABLED', true) === true,
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    ],

];

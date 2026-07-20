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

    // BytePlus Ark — provider terjemahan AI (model seed-translation, Responses API).
    // Hanya BOOTSTRAP default; sumber kebenaran = tabel ai_configs (Pengaturan → Konfigurasi AI).
    'ark' => [
        'key' => env('ARK_API_KEY'),
        'base_url' => env('ARK_BASE_URL', 'https://ark.ap-southeast.bytepluses.com/api/v3'),
        'translation_model' => env('ARK_TRANSLATION_MODEL', 'seed-translation-250915'),
    ],

    // MegaNova — provider chat OpenAI-compatible untuk Koreksi Konten (CONTENT_REFINEMENT).
    // Bootstrap default; admin dapat override via Pengaturan → Konfigurasi AI.
    'meganova' => [
        'key' => env('MEGANOVA_API_KEY'),
        'base_url' => env('MEGANOVA_BASE_URL', 'https://api.meganova.ai/v1'),
        'chat_model' => env('MEGANOVA_CHAT_MODEL', 'meganova-ai/manta-flash-1.0'),
    ],

];

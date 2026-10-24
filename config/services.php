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

    // Dashboard task (trang /tasks). Key chỉ đặt trong .env, không commit.
    'redmine' => [
        'url' => env('REDMINE_URL'),
        'key' => env('REDMINE_API_KEY'),
        'project' => env('REDMINE_PROJECT'),
        // Múi giờ để tính "hôm nay" và giờ hiển thị của dashboard (app.timezone vẫn là UTC).
        'timezone' => env('REDMINE_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

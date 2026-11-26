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

    /*
    | InterServer (my.interserver.net), whose VPS, web hosting, domains and
    | Rapid Deploy dedicated servers clients order under Cloud. The API key
    | comes from Account Security on my.interserver.net; orders are paid
    | from the account's prepay balance.
    */
    'interserver' => [
        'key' => env('INTERSERVER_API_KEY'),
        'url' => env('INTERSERVER_API_URL', 'https://my.interserver.net/apiv2'),
        'timeout' => (int) env('INTERSERVER_TIMEOUT', 30),
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

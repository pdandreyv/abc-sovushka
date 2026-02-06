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

    'vkontakte' => [
        'client_id' => env('VKONTAKTE_CLIENT_ID'),
        'client_secret' => env('VKONTAKTE_CLIENT_SECRET'),
        'redirect' => env('VKONTAKTE_REDIRECT_URI'),
    ],

    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'redirect' => env('YANDEX_REDIRECT_URI'),
    ],

    'odnoklassniki' => [
        'client_id' => env('ODNOKLASSNIKI_CLIENT_ID'),
        'client_secret' => env('ODNOKLASSNIKI_CLIENT_SECRET'),
        'redirect' => env('ODNOKLASSNIKI_REDIRECT_URI'),
        'public_key' => env('ODNOKLASSNIKI_PUBLIC_KEY'),
    ],

    'telegram' => [
        'bot_name' => env('TELEGRAM_BOT_NAME'),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    /*
    | ЮKassa (YooKassa) — приём платежей и автоплатежи
    | Документация: https://yookassa.ru/developers/using-api/interaction-format
    | Тестовый режим: создать демо-магазин в ЛК, взять shop_id и secret_key из раздела Интеграция → Ключи API
    */
    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
        'api_url' => 'https://api.yookassa.ru/v3',
    ],

];

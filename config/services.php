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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'yandex' => [
        'service_account_api' => env('YANDEX_SERVICE_ACCOUNT_API'),
        'folder_id' => env('YANDEX_FOLDER_ID'),

        'auto_translate' => env('YANDEX_AUTO_TRANSLATE', true),
        'target_languages' => explode(',', env('YANDEX_TARGET_LANGUAGES', 'ru,en,de,fr,es,zh_CN,pt_PT')),
        'update_delay' => env('YANDEX_TRANSLATE_UPDATE_DELAY', 300),
    ],
];

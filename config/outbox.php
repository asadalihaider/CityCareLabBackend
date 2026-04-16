<?php

/*
|--------------------------------------------------------------------------
| Outbox Service - Channel Configuration
|--------------------------------------------------------------------------
|
| This file configures available outbound channels.
| Message title/body are provided by callers (API or in-app), not templates.
| Channel toggles are driven by .env so they can be flipped without deploy.
|
*/

return [

    'channels' => [
        'expo' => [
            'enabled' => env('EXPO_NOTIFICATIONS_ENABLED', false),
        ],
        'whatsapp' => [
            'enabled' => (bool) env('WHATSAPP_NOTIFICATIONS_ENABLED', false),
        ],
        'sms' => [
            'enabled' => (bool) env('SMS_NOTIFICATIONS_ENABLED', false),
        ],
    ],

];

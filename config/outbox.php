<?php

/*
|--------------------------------------------------------------------------
| Outbox Service – Channel & Template Configuration
|--------------------------------------------------------------------------
|
| This file configures the centralized outbound messaging service.
| Templates use the #{variable} syntax for runtime interpolation.
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

    'templates' => [

        'BOOKING_CONFIRMED' => [
            'title' => 'Booking Confirmed ✅',
            'body' => 'Your booking ##{booking_id} has been confirmed for #{booking_date}. We look forward to seeing you!',
        ],

        'BOOKING_CANCELLED' => [
            'title' => 'Booking Cancelled',
            'body' => 'Your booking ##{booking_id} has been cancelled. For queries, please contact us.',
        ],

        'BOOKING_REMINDER' => [
            'title' => 'Upcoming Appointment Reminder 🔔',
            'body' => 'Reminder: Your lab appointment ##{booking_id} is scheduled for #{booking_date}. Please arrive 10 minutes early.',
        ],

        'REPORT_READY' => [
            'title' => 'Your Lab Report is Ready 📋',
            'body' => 'Your lab report for booking ##{booking_id} is now available. Open the City Care app to view it.',
        ],

        'BOOKING_CREATED' => [
            'title' => 'Booking Received 🎉',
            'body' => 'We received your booking ##{booking_id} for #{booking_date}. You will be notified once it is confirmed.',
        ],

        'OTP' => [
            'title' => 'Your OTP Code',
            'body' => 'Your verification code is #{otp}. It is valid for 10 minutes. Do not share it with anyone.',
        ],

        'GENERAL' => [
            'title' => '#{title}',
            'body' => '#{body}',
        ],

    ],

];

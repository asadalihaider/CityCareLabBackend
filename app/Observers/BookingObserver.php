<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\OutboxLog;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        $adminMobile = config('app.admin_mobile');

        if ($adminMobile) {
            $bookingDate = $booking->booking_date?->format('M j, Y');

            OutboxLog::create([
                'mobile' => $adminMobile,
                'event' => 'SYSTEM',
                'title' => 'Booking Received',
                'body' => 'New booking request'.($bookingDate ? ' for '.$bookingDate : '').' has been received.',
                'payload' => [
                    'booking_id' => $booking->id,
                    'patient_name' => $booking->patient_name,
                    'booking_date' => $bookingDate,
                ],
            ]);
        }
    }

    public function updated(Booking $booking): void
    {
        //
    }

    public function deleted(Booking $booking): void
    {
        //
    }

    public function restored(Booking $booking): void
    {
        //
    }

    public function forceDeleted(Booking $booking): void
    {
        //
    }
}

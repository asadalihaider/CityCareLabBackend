<?php

namespace App\Observers;

use App\Jobs\ProcessOutboxJob;
use App\Models\Booking;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        $adminMobile = config('app.admin_mobile');

        if ($adminMobile) {
            ProcessOutboxJob::dispatch(
                mobile: $adminMobile,
                event: 'BOOKING_CREATED',
                data: [
                    'booking_id' => $booking->id,
                    'patient_name' => $booking->patient_name,
                    'booking_date' => $booking->booking_date?->format('M j, Y'),
                ],
            );
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

<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Customer;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        $customer = Customer::where('mobile_number', env('ADMIN_MOBILE_NUMBER'))->first();

        if (!$customer || !$customer->expoTokens()->exists()) {
            return;
        }

        $title = 'Booking Created!';
        $body = $this->getNotificationMessage($booking);
        $additionalData = $this->getNotificationData($booking);

        $pushNotification = new \App\Notifications\PushNotification(
            $title,
            $body,
            $additionalData,
            false
        );

        $customer->notify($pushNotification);
    }

    private function getNotificationMessage(Booking $booking): string
    {
        $bookingType = $booking->booking_type->label();
        $patientName = $booking->patient_name;
        $bookingDate = $booking->booking_date->format('M j, Y \a\t g:i A');

        return "New {$bookingType} booking for {$patientName} has been created for {$bookingDate}.";
    }

    private function getNotificationData(Booking $booking): array
    {
        return [
            'booking_id' => $booking->id,
        ];
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}

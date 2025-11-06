<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Card\ActivateCardRequest;
use App\Http\Requests\Card\DeactivateCardRequest;
use App\Models\Customer;
use App\Models\CustomerCard;
use App\Models\DiscountCard;
use App\Models\Enum\DiscountCardStatus;
use Illuminate\Support\Facades\Storage;

class CardController extends BaseApiController
{
    private function getCustomerCards(Customer $customer)
    {
        $customer->load(['customerCards.discountCard.offerCard']);

        return $customer->customerCards->map(function ($customerCard) {
            $card = $customerCard->discountCard;

            return [
                'id' => $card->id,
                'serial_number' => $card->serial_number,
                'expiry_date' => $card->expiry_date->format('Y-m-d'),
                'status' => $card->status->value,
                'is_active' => $card->is_active,
                'is_expired' => $card->isExpired(),
                'offer_card' => [
                    'id' => $card->offerCard->id,
                    'title' => $card->offerCard->title,
                    'description' => $card->offerCard->description,
                    'features' => $card->offerCard->features,
                    'price' => $card->offerCard->price,
                    'image' => $card->offerCard->image ? Storage::disk('s3')->temporaryUrl($card->offerCard->image, now()->addDays(1)) : null,
                ],
                'attached_at' => $customerCard->created_at->toISOString(),
            ];
        });
    }

    public function activateCard(ActivateCardRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();

            if ($customer->activeCard) {
                return $this->errorResponse(
                    'You already have an active discount card. Please deactivate it before activating a new one.',
                    400
                );
            }

            $discountCard = DiscountCard::where('serial_number', $request->identifier)
                ->where('expiry_date', $request->expiry_date)
                ->first();

            if (! $discountCard) {
                return $this->errorResponse(
                    'Invalid card credentials. Please check the serial number and expiry date.',
                    404
                );
            }

            if ($discountCard->isExpired()) {
                return $this->errorResponse(
                    'This card has expired and cannot be activated.',
                    400
                );
            }

            if (! $discountCard->is_active) {
                return $this->errorResponse(
                    'This card has been deactivated by the admin and cannot be used.',
                    400
                );
            }

            if (! $discountCard->offerCard->is_active) {
                return $this->errorResponse(
                    'This card type is currently not available for activation.',
                    400
                );
            }

            if ($discountCard->status === DiscountCardStatus::ATTACHED) {
                return $this->errorResponse(
                    'This card is already attached to another customer.',
                    400
                );
            }

            $message = 'Discount card activated successfully';

            CustomerCard::attachCard($customer->id, $discountCard->id);

            $customer->refresh();

            return $this->successResponse(['cards' => $this->getCustomerCards($customer)], $message);

        }, 'Failed to activate card. Please try again.');
    }

    public function deactivateCard(DeactivateCardRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();
            $cardId = $request->card_id;

            $customerCard = CustomerCard::whereHas('discountCard', function ($query) use ($cardId) {
                $query->where('id', $cardId);
            })->where('customer_id', $customer->id)->first();

            if (! $customerCard) {
                return $this->errorResponse(
                    'Card not found or does not belong to you.',
                    404
                );
            }

            $card = $customerCard->discountCard;
            $card->update(['is_active' => false]);
            $customer->refresh();

            return $this->successResponse(['cards' => $this->getCustomerCards($customer)], 'Discount card deactivated successfully');
        }, 'Failed to deactivate card. Please try again.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Card\ActivateCardRequest;
use App\Http\Requests\Card\UpdateCardRequest;
use App\Models\Customer;
use App\Models\CustomerCard;
use App\Models\Enum\PhysicalCardStatus;
use App\Models\PhysicalCard;

class CardController extends BaseApiController
{
    public function activateCard(ActivateCardRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();

            if ($customer->activeCard) {
                return $this->errorResponse(
                    'You already have an active health card. Please deactivate it before activating a new one.',
                    400
                );
            }

            $physicalCard = PhysicalCard::where('serial_number', $request->identifier)
                ->where('expiry_date', $request->expiry_date)
                ->first();

            if (! $physicalCard) {
                return $this->errorResponse(
                    'Invalid card credentials. Please check the serial number and expiry date.',
                    404
                );
            }

            if ($physicalCard->isExpired()) {
                return $this->errorResponse(
                    'This card has expired and cannot be activated.',
                    400
                );
            }

            if (! $physicalCard->is_active) {
                return $this->errorResponse(
                    'This card has been deactivated by the admin and cannot be used.',
                    400
                );
            }

            if (! $physicalCard->healthCard->is_active) {
                return $this->errorResponse(
                    'This card type is currently not available for activation.',
                    400
                );
            }

            if ($physicalCard->status === PhysicalCardStatus::ACTIVATED) {
                return $this->errorResponse(
                    'This card is already activated by another customer.',
                    400
                );
            }

            $message = 'Health card activated successfully';

            $customerCard = CustomerCard::activateCard($customer->id, $physicalCard->id);

            return $this->successResponse(['card' => $customerCard->card_details], $message);

        }, 'Failed to activate card. Please try again.');
    }

    public function updateCard(UpdateCardRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();
            $cardId = $request->card_id;
            $isActive = $request->is_active;

            // Find the customer's card
            $customerCard = CustomerCard::whereHas('physicalCard', function ($query) use ($cardId) {
                $query->where('id', $cardId);
            })->where('customer_id', $customer->id)->first();

            if (! $customerCard) {
                return $this->errorResponse(
                    'Card not found or does not belong to you.',
                    404
                );
            }

            $physicalCard = $customerCard->physicalCard;

            if ($isActive) {
                // Check if customer already has an active card (excluding current one)
                $existingActiveCard = $customer->customerCards()
                    ->whereHas('physicalCard', function ($query) use ($cardId) {
                        $query->where('id', '!=', $cardId)
                            ->where('status', PhysicalCardStatus::ACTIVATED)
                            ->where('is_active', true)
                            ->where('expiry_date', '>', now());
                    })
                    ->first();

                if ($existingActiveCard) {
                    return $this->errorResponse(
                        'You already have another active health card. Please deactivate it before activating this one.',
                        400
                    );
                }

                // Check if the offer card is still active
                if (! $physicalCard->healthCard->is_active) {
                    return $this->errorResponse(
                        'This card type is currently not available for activation.',
                        400
                    );
                }
            }

            $physicalCard->update(['is_active' => $isActive]);

            if ($isActive) {
                $physicalCard->update(['status' => PhysicalCardStatus::ACTIVATED]);
            } else {
                $physicalCard->update(['status' => PhysicalCardStatus::AVAILABLE]);
            }

            $message = $isActive ? 'Health card activated successfully' : 'Health card deactivated successfully';

            return $this->successResponse(['card' => $customerCard->card_details], $message);
        }, 'Failed to update card status. Please try again.');
    }
}

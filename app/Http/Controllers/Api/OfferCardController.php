<?php

namespace App\Http\Controllers\Api;

use App\Models\OfferCard;
use Illuminate\Http\JsonResponse;

class OfferCardController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () {
            $offerCards = OfferCard::active()
                ->select(['id', 'title', 'description', 'link', 'image', 'price'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $offerCards->getCollection()->transform(function ($card) {
                return [
                    'id' => $card->id,
                    'title' => $card->title,
                    'description' => $card->description,
                    'link' => $card->link,
                    'image' => $card->image_url,
                    'price' => $card->price,
                ];
            });

            return $this->paginatedResponse($offerCards, 'Discount cards retrieved successfully');
        }, 'Failed to retrieve discount cards');
    }
}

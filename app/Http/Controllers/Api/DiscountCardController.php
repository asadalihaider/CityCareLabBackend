<?php

namespace App\Http\Controllers\Api;

use App\Models\DiscountCard;
use Illuminate\Http\JsonResponse;

class DiscountCardController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () {
            $discountCards = DiscountCard::active()
                ->select(['id', 'title', 'description', 'link', 'image'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $discountCards->getCollection()->transform(function ($card) {
                return [
                    'id' => $card->id,
                    'title' => $card->title,
                    'description' => $card->description,
                    'link' => $card->link,
                    'image' => $card->image_url,
                ];
            });

            return $this->paginatedResponse($discountCards, 'Discount cards retrieved successfully');
        }, 'Failed to retrieve discount cards');
    }
}

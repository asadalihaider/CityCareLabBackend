<?php

namespace App\Http\Controllers\Api;

use App\Models\HealthCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class HealthCardController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () {
            $healthCards = HealthCard::active()
                ->select(['id', 'title', 'description', 'link', 'image', 'price'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $healthCards->getCollection()->transform(function ($card) {
                return [
                    'id' => $card->id,
                    'title' => $card->title,
                    'description' => $card->description,
                    'link' => $card->link,
                    'image' => $card->image ? Storage::disk('s3')->temporaryUrl($card->image, now()->addDays(1)) : null,
                    'price' => $card->price,
                    'max_members' => $card->max_members,
                ];
            });

            return $this->paginatedResponse($healthCards, 'Health cards retrieved successfully');
        }, 'Failed to retrieve health cards');
    }
}

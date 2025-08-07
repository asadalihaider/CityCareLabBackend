<?php

namespace App\Http\Controllers\Api;

use App\Models\LabOffer;
use Illuminate\Http\JsonResponse;

class LabOfferController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () {
            $labOffers = LabOffer::active()
                ->select(['id', 'link', 'image'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $labOffers->getCollection()->transform(function ($offer) {
                return [
                    'id' => $offer->id,
                    'link' => $offer->link,
                    'image' => $offer->image_url,
                ];
            });

            return $this->paginatedResponse($labOffers, 'Lab offers retrieved successfully');
        }, 'Failed to retrieve lab offers');
    }
}

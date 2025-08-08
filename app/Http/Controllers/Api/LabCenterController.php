<?php

namespace App\Http\Controllers\Api;

use App\Models\LabCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabCenterController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $query = LabCenter::active()
                ->with('operatingCity:id,name')
                ->select(['id', 'address', 'phone', 'secondary_phone', 'rating', 'operating_city_id']);

            // Filter by city if provided
            if ($request->has('city_id') && $request->city_id) {
                $query->byCity($request->city_id);
            }

            $labCenters = $query->orderBy('rating', 'desc')->paginate(50);

            $labCenters->getCollection()->transform(function ($center) {
                return [
                    'id' => $center->id,
                    'address' => $center->address,
                    'phone' => $center->phone,
                    'secondaryPhone' => $center->secondary_phone,
                    'rating' => (float) $center->rating,
                    'city' => $center->operatingCity->name ?? null,
                ];
            });

            return $this->paginatedResponse($labCenters, 'Lab centers retrieved successfully');
        }, 'Failed to retrieve lab centers');
    }
}

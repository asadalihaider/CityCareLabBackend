<?php

namespace App\Http\Controllers\Api;

use App\Models\OperatingCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatingCityController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $query = OperatingCity::active()->select(['id', 'name', 'province']);

            // Filter by province if provided
            if ($request->has('province') && $request->province) {
                $query->byProvince($request->province);
            }

            $operatingCities = $query->orderBy('name', 'asc')->paginate(50);

            $operatingCities->getCollection()->transform(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'province' => $city->province,
                ];
            });

            return $this->paginatedResponse($operatingCities, 'Operating cities retrieved successfully');
        }, 'Failed to retrieve operating cities');
    }
}

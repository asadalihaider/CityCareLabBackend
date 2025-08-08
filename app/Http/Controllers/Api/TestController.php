<?php

namespace App\Http\Controllers\Api;

use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $query = Test::active()
                ->select(['id', 'name', 'abbreviation', 'duration', 'type', 'categories', 'price', 'sale_price', 'includes']);

            // Add filtering by type if needed
            if ($request->has('type') && $request->type) {
                $query->byType($request->type);
            }

            $tests = $query->orderBy('name', 'asc')->paginate(50);

            $tests->getCollection()->transform(function ($test) {
                $categories = $test->getTestCategories()->pluck('category')->toArray();

                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'abbreviation' => $test->abbreviation,
                    'duration' => $test->duration,
                    'type' => $test->type->value,
                    'categories' => $categories,
                    'price' => $test->price,
                    'salePrice' => $test->sale_price,
                    'includes' => $test->includes ?? [],
                ];
            });

            return $this->paginatedResponse($tests, 'Tests retrieved successfully');
        }, 'Failed to retrieve tests');
    }
}

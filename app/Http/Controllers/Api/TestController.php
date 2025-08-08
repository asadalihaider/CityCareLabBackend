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
                ->with(['categories:id,category'])
                ->select(['id', 'title', 'short_title', 'duration', 'type', 'price', 'sale_price', 'includes']);

            // Add filtering by type if needed
            if ($request->has('type') && $request->type) {
                $query->byType($request->type);
            }

            $tests = $query->orderBy('title', 'asc')->paginate(50);

            $tests->getCollection()->transform(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'shortTitle' => $test->short_title,
                    'duration' => $test->duration,
                    'type' => $test->type->value,
                    'categories' => $test->categories->pluck('category')->toArray(),
                    'price' => $test->price,
                    'salePrice' => $test->sale_price,
                    'includes' => $test->includes ?? [],
                ];
            });

            return $this->paginatedResponse($tests, 'Tests retrieved successfully');
        }, 'Failed to retrieve tests');
    }
}

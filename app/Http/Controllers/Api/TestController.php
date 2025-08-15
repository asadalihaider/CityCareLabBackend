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
                ->with(['categories:id,slug'])
                ->select(['id', 'title', 'short_title', 'duration', 'type', 'price', 'discount', 'includes', 'prerequisites', 'relevant_diseases', 'relevant_symptoms', 'is_featured', 'image']);

            // Add filtering by type if needed
            if ($request->has('type') && $request->type) {
                $query->byType($request->type);
            }

            // Add filtering by featured status
            if ($request->has('featured') && $request->boolean('featured')) {
                $query->featured();
            }

            $tests = $query->orderBy('title', 'asc')->paginate(50);

            $tests->getCollection()->transform(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'shortTitle' => $test->short_title,
                    'duration' => $test->duration,
                    'type' => $test->type->value,
                    'categories' => $test->categories->pluck('slug')->toArray(),
                    'price' => $test->price,
                    'discount' => $test->discount,
                    'includes' => $test->includes ?? [],
                    'prerequisites' => $test->prerequisites ?? [],
                    'relevantSymptoms' => $test->relevant_symptoms ?? [],
                    'relevantDiseases' => $test->relevant_diseases ?? [],
                    'isFeatured' => $test->is_featured,
                    'image' => $test->image ? asset('storage/'.$test->image) : null,
                ];
            });

            return $this->paginatedResponse($tests, 'Tests retrieved successfully');
        }, 'Failed to retrieve tests');
    }
}

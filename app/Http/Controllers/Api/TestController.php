<?php

namespace App\Http\Controllers\Api;

use App\Models\Test;
use App\Services\PathCareSoftApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $query = Test::active()
                ->with(['categories:id,slug'])
                ->select(['id', 'title', 'short_title', 'duration', 'type', 'price', 'discount', 'includes', 'prerequisites', 'relevant_diseases', 'relevant_symptoms', 'is_featured', 'image']);

            // Add search functionality
            if ($request->has('category') && $request->category) {
                $categorySlug = $request->category;
                $query->whereHas('categories', function ($categoryQuery) use ($categorySlug) {
                    $categoryQuery->where('slug', $categorySlug);
                });
            }

            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('short_title', 'LIKE', "%{$searchTerm}%")
                        ->orWhereJsonContains('relevant_symptoms', $searchTerm)
                        ->orWhereJsonContains('relevant_diseases', $searchTerm)
                        ->orWhereHas('categories', function ($categoryQuery) use ($searchTerm) {
                            $categoryQuery->where('title', 'LIKE', "%{$searchTerm}%");
                        });
                });
            }

            if ($request->has('type') && $request->type) {
                $query->byType($request->type);
            }

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
                    'image' => $test->image ? Storage::disk('s3')->temporaryUrl($test->image, now()->addDays(3)) : null,
                ];
            });

            return $this->paginatedResponse($tests, 'Tests retrieved successfully');
        }, 'Failed to retrieve tests');
    }

    public function history(PathCareSoftApiService $pathCareSoftService): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($pathCareSoftService) {
            /** @var \App\Models\Customer $customer */
            $customer = auth('sanctum')->user();

            $patientData = $pathCareSoftService->getPatientData($customer->mobile_number);

            return $this->collectionResponse($patientData, 'Test history retrieved successfully');

        }, 'Failed to retrieve test history');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\TestCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestCategoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $query = TestCategory::active()
                ->select(['id', 'title', 'icon', 'category', 'is_active']);

            // Add filtering by category if needed
            if ($request->has('category') && $request->category) {
                $query->where('category', $request->category);
            }

            $testCategories = $query->paginate(10);

            $testCategories->getCollection()->transform(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'icon' => $category->icon,
                    'category' => $category->category,
                    'isActive' => $category->is_active,
                ];
            });

            return $this->paginatedResponse($testCategories, 'Test categories retrieved successfully');
        }, 'Failed to retrieve test categories');
    }
}

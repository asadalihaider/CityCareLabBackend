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
                ->select(['id', 'title', 'slug', 'icon', 'is_active']);

            // Add filtering by slug if needed
            if ($request->has('slug') && $request->slug) {
                $query->where('slug', $request->slug);
            }

            $testCategories = $query->paginate(10);

            $testCategories->getCollection()->transform(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'isActive' => $category->is_active,
                ];
            });

            return $this->paginatedResponse($testCategories, 'Test categories retrieved successfully');
        }, 'Failed to retrieve test categories');
    }
}

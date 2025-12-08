<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppInfoController extends BaseApiController
{
    public function checkVersion(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $currentVersion = $request->input('currentVersion');
            $latestVersion = env('APP_LATEST_VERSION', '1.0.0');
            $minVersion = env('APP_MIN_VERSION', '1.0.0');

            $needsForceUpdate = false;
            if ($currentVersion) {
                $needsForceUpdate = version_compare($currentVersion, $minVersion, '<');
            }

            $data = [
                'latest_version' => $latestVersion,
                'min_version' => $minVersion,
                'current_version' => $currentVersion,
                'needs_force_update' => $needsForceUpdate,
                'needs_update' => $currentVersion ? version_compare($currentVersion, $latestVersion, '<') : false,
            ];

            return $this->successResponse($data, 'App version information retrieved successfully');
        }, 'Failed to retrieve app version information');
    }
}

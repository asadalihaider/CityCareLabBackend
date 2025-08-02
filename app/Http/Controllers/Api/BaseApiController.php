<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;

abstract class BaseApiController extends Controller
{
    use ApiResponseTrait;

    protected function handleException(\Exception $e, string $defaultMessage = 'Operation failed', int $defaultStatusCode = 500): \Illuminate\Http\JsonResponse
    {
        Log::error('API Exception: '.$e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Resource not found');
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->unauthorizedResponse('Authentication required');
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->forbiddenResponse('Access denied');
        }

        $message = app()->environment(['local', 'testing']) ? $e->getMessage() : $defaultMessage;

        return $this->errorResponse($message, $defaultStatusCode);
    }

    protected function executeWithExceptionHandling(callable $callback, string $defaultErrorMessage = 'Operation failed')
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return $this->handleException($e, $defaultErrorMessage);
        }
    }
}

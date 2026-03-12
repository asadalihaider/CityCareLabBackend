<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');

        if (! $key) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required. Provide it via the X-API-KEY header.',
                'timestamp' => now()->toISOString(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $client = ApiClient::findByKey($key);

        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key.',
                'timestamp' => now()->toISOString(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}

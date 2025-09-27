<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PathCareSoftApiService
{
    protected string $baseUrl;

    protected string $userId;

    protected string $password;

    protected int $timeout;

    public function __construct()
    {
        $config = config('services.pathcaresoft');
        $this->baseUrl = $config['base_url'];
        $this->userId = $config['user_id'];
        $this->password = $config['password'];
        $this->timeout = $config['timeout'];
    }

    /**
     * Get patient data from PathCareSoft API
     *
     * @throws \Exception
     */
    public function getPatientTestHistory(string $phoneNumber): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'api-version' => '2.0',
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v2/publicapi/patient-data", [
                    'userid' => $this->userId,
                    'password' => $this->password,
                    'ph' => $phoneNumber,
                ]);

            // Check if the request was successful
            if (! $response->successful()) {
                Log::error('PathCareSoft API: Request failed', [
                    'phone_number' => $phoneNumber,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                if ($response->status() === 404) {
                    throw new \Exception('No patient data found for the provided phone number.');
                }

                if ($response->status() === 401) {
                    throw new \Exception('API authentication failed. Please contact support.');
                }

                throw new \Exception('Failed to retrieve patient data. Please try again.');
            }

            return $response->json()['data'];

        } catch (\Exception $e) {
            throw $e;
        }
    }
}

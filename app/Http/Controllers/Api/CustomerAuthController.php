<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CustomerLoginRequest;
use App\Http\Requests\CustomerRegistrationRequest;
use App\Models\Customer;
use App\Models\Enum\CustomerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends BaseApiController
{
    public function register(CustomerRegistrationRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = Customer::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'password' => Hash::make($request->password),
                'location' => $request->location,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'status' => CustomerStatus::ACTIVE,
            ]);

            $token = $customer->createToken('mobile-app')->plainTextToken;

            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'location' => $customer->location,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
                'token' => $token,
                'token_type' => 'Bearer',
            ];

            return $this->createdResponse($data, 'Customer registered successfully');
        }, 'Registration failed');
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = Customer::where('mobile_number', $request->mobile_number)->first();

            if (! $customer || ! Hash::check($request->password, $customer->password)) {
                throw ValidationException::withMessages([
                    'mobile_number' => ['The provided credentials are incorrect.'],
                ]);
            }

            if ($customer->status !== CustomerStatus::ACTIVE) {
                return $this->forbiddenResponse('Your account is currently inactive. Please contact support.');
            }

            $customer->tokens()->delete();

            $token = $customer->createToken('mobile-app')->plainTextToken;

            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'location' => $customer->location,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
                'token' => $token,
                'token_type' => 'Bearer',
            ];

            return $this->successResponse($data, 'Login successful');
        }, 'Login failed');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();

            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'location' => $customer->location,
                'date_of_birth' => $customer->date_of_birth,
                'gender' => $customer->gender,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
            ];

            return $this->successResponse($data, 'Profile retrieved successfully');
        }, 'Failed to fetch profile');
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logout successful');
        }, 'Logout failed');
    }

    public function refresh(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();

            $request->user()->currentAccessToken()->delete();

            $token = $customer->createToken('mobile-app')->plainTextToken;

            $data = [
                'token' => $token,
                'token_type' => 'Bearer',
            ];

            return $this->successResponse($data, 'Token refreshed successfully');
        }, 'Token refresh failed');
    }
}

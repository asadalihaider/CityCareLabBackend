<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Customer\LoginRequest;
use App\Http\Requests\Customer\RegistrationRequest;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Models\Customer;
use App\Models\Enum\CustomerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends BaseApiController
{
    public function register(RegistrationRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = Customer::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'email' => $request->email,
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
                'email' => $customer->email,
                'location' => $customer->location,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
                'email_verified' => $customer->isEmailVerified(),
                'token' => $token,
                'token_type' => 'Bearer',
            ];

            return $this->createdResponse($data, 'Customer registered successfully');
        }, 'Registration failed');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $login = $request->login;
            $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_number';

            $customer = Customer::where($loginField, $login)->first();

            if (! $customer || ! Hash::check($request->password, $customer->password)) {
                throw ValidationException::withMessages([
                    'login' => ['The provided credentials are incorrect.'],
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
                'email' => $customer->email,
                'location' => $customer->location,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
                'email_verified' => $customer->isEmailVerified(),
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
                'email' => $customer->email,
                'location' => $customer->location,
                'date_of_birth' => $customer->date_of_birth,
                'gender' => $customer->gender,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
                'email_verified' => $customer->isEmailVerified(),
            ];

            return $this->successResponse($data, 'Profile retrieved successfully');
        }, 'Failed to fetch profile');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();

            // If email is being updated and is different from current, reset email verification
            $updateData = $request->validated();
            if (isset($updateData['email']) && $updateData['email'] !== $customer->email) {
                $updateData['email_verified_at'] = null;
            }

            $customer->update($updateData);

            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'email' => $customer->email,
                'location' => $customer->location,
                'date_of_birth' => $customer->date_of_birth,
                'gender' => $customer->gender,
                'status' => $customer->status,
                'mobile_verified' => $customer->isMobileVerified(),
                'email_verified' => $customer->isEmailVerified(),
            ];

            return $this->updatedResponse($data, 'Profile updated successfully');
        }, 'Failed to update profile');
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

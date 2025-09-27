<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Customer\ForgotPasswordRequest;
use App\Http\Requests\Customer\LoginRequest;
use App\Http\Requests\Customer\RegistrationRequest;
use App\Http\Requests\Customer\ResetPasswordRequest;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Http\Requests\Customer\VerifyOtpRequest;
use App\Models\Customer;
use App\Models\Enum\CustomerStatus;
use App\Models\Enum\OtpType;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerAuthController extends BaseApiController
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function register(RegistrationRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = Customer::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'password' => Hash::make($request->password),
            ]);

            $otp = $this->otpService->createAndSendOtp(
                $customer->mobile_number,
                OtpType::MOBILE_VERIFICATION
            );

            $data = ['otp_sent' => $otp !== null];

            return $this->createdResponse($data, 'Customer registered successfully. OTP sent for verification.');
        }, 'Registration failed');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $login = $request->login;
            $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_number';

            $customer = Customer::with('city')->where($loginField, $login)->first();

            if (! $customer || ! Hash::check($request->password, $customer->password)) {
                return $this->errorResponse('The provided credentials are incorrect.', 422);
            }

            if ($customer->status !== CustomerStatus::ACTIVE) {
                return $this->forbiddenResponse('Your account is currently inactive. Please contact support.');
            }

            // Check if mobile number is verified for login via mobile number
            if ($loginField === 'mobile_number' && ! $customer->isMobileVerified()) {
                return $this->errorResponse('Your phone number is not verified. Please verify your phone number first.', 403);
            }

            $token = $customer->createToken('mobile-app')->plainTextToken;

            $data = ['token' => $token];

            return $this->successResponse($data, 'Login successful');
        }, 'Login failed');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = $request->user();
            $customer->load('city');

            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'email' => $customer->email,
                'city' => $customer->city ? [
                    'id' => $customer->city->id,
                    'name' => $customer->city->name,
                    'province' => $customer->city->province,
                ] : null,
                'dob' => $customer->dob,
                'image' => $customer->image ? Storage::disk('s3')->temporaryUrl($customer->image, now()->addDays(1)) : null,
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
            $updateData = $request->validated();

            if ($request->hasFile('image')) {
                if ($customer->image) {
                    Storage::disk('s3')->delete($customer->image);
                }

                $imagePath = $request->file('image')->store('customers', 's3');
                $updateData['image'] = $imagePath;
            }

            if (isset($updateData['email']) && $updateData['email'] !== $customer->email) {
                $updateData['email_verified_at'] = null;
            }

            $customer->update($updateData);
            $customer->load('city');

            $data = [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'email' => $customer->email,
                'city' => $customer->city ? [
                    'id' => $customer->city->id,
                    'name' => $customer->city->name,
                    'province' => $customer->city->province,
                ] : null,
                'dob' => $customer->dob,
                'image' => $customer->image ? Storage::disk('s3')->temporaryUrl($customer->image, now()->addDays(1)) : null,
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

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $otpType = OtpType::from($request->type);

            $result = $this->otpService->verifyOtp(
                $request->mobile_number,
                $request->otp,
                $otpType
            );

            if (! $result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            $customer = Customer::where('mobile_number', $request->mobile_number)->first();

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            // Handle different verification types
            switch ($otpType) {
                case OtpType::MOBILE_VERIFICATION:
                    $customer->markMobileAsVerified();

                    return $this->successResponse(null, 'Phone number verified successfully. You can now login.');

                case OtpType::EMAIL_VERIFICATION:
                    $customer->markEmailAsVerified();

                    return $this->successResponse(null, 'Email verified successfully.');

                case OtpType::FORGOT_PASSWORD:
                    return $this->successResponse([
                        'mobile_number' => $customer->mobile_number,
                        'otp_verified' => true,
                        'message' => 'OTP verified. You can now reset your password.',
                    ], 'OTP verified successfully');

                default:
                    return $this->errorResponse('Invalid OTP type', 400);
            }
        }, 'OTP verification failed');
    }

    public function resendOtp(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $request->validate([
                'mobile_number' => [
                    'required',
                    'string',
                    'regex:/^(?:\+92|0)3[0-9]{9}$/',
                ],
                'type' => ['required', Rule::enum(OtpType::class)],
            ]);

            $otpType = OtpType::from($request->type);

            // Check if customer exists (phone number must be in database)
            $customer = Customer::where('mobile_number', $request->mobile_number)->first();
            if (! $customer) {
                return $this->notFoundResponse('Customer not found. Please register first.');
            }

            $otp = $this->otpService->createAndSendOtp($request->mobile_number, $otpType);

            if (! $otp) {
                return $this->errorResponse('Failed to send OTP. Please try again.');
            }

            return $this->successResponse(null, 'OTP sent successfully');
        }, 'Failed to resend OTP');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $customer = Customer::where('mobile_number', $request->mobile_number)->first();

            if (! $customer) {
                return $this->notFoundResponse('No account found with this mobile number.');
            }

            $otp = $this->otpService->createAndSendOtp(
                $request->mobile_number,
                OtpType::FORGOT_PASSWORD
            );

            if (! $otp) {
                return $this->errorResponse('Failed to send reset OTP. Please try again.');
            }

            return $this->successResponse(null, 'Password reset OTP sent to your mobile number.');
        }, 'Failed to initiate password reset');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $result = $this->otpService->hasVerifiedOtp(
                $request->mobile_number,
                $request->otp,
                OtpType::FORGOT_PASSWORD
            );

            if (! $result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            $customer = Customer::where('mobile_number', $request->mobile_number)->first();

            if (! $customer) {
                return $this->notFoundResponse('Customer not found');
            }

            $customer->update([
                'password' => Hash::make($request->password),
            ]);

            $customer->tokens()->delete();

            return $this->successResponse(null, 'Password reset successfully. Please login with your new password.');
        }, 'Password reset failed');
    }
}

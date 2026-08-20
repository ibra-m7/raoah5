<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\SaveLocationRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Services\Addresses\AddressService;
use App\Services\Auth\OtpAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpAuthController extends Controller
{
    public function requestOtp(RequestOtpRequest $request, OtpAuthService $otp): JsonResponse
    {
        try {
            $data = $otp->request(
                $request->validated('phone'),
                $request->ip(),
            );
        } catch (OtpException $e) {
            return ApiResponse::error($e->getMessage(), $e->status);
        }

        if (! empty($data['token']) && isset($data['user'])) {
            $data['otp_required'] = false;
            $data['user'] = app(AddressService::class)->userPayload($data['user']);

            return ApiResponse::success('تم تسجيل الدخول بنجاح.', $data);
        }

        return ApiResponse::success('تم إرسال رمز التحقق عبر واتساب.', $data);
    }

    public function verifyOtp(VerifyOtpRequest $request, OtpAuthService $otp): JsonResponse
    {
        try {
            $result = $otp->verify(
                $request->validated('phone'),
                $request->validated('code'),
            );
        } catch (OtpException $e) {
            return ApiResponse::error($e->getMessage(), $e->status);
        }

        return ApiResponse::success('تم تسجيل الدخول بنجاح.', [
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'user' => app(AddressService::class)->userPayload($result['user']),
        ]);
    }

    public function me(Request $request, AddressService $addresses): JsonResponse
    {
        return ApiResponse::success('حسابك', [
            'user' => $addresses->userPayload($request->user()),
        ]);
    }

    public function updateMe(UpdateProfileRequest $request, AddressService $addresses): JsonResponse
    {
        $request->user()->update([
            'name' => $request->validated('name'),
        ]);

        return ApiResponse::success('تم حفظ اسمك.', [
            'user' => $addresses->userPayload($request->user()->fresh()),
        ]);
    }

    public function saveLocation(SaveLocationRequest $request, AddressService $addresses): JsonResponse
    {
        $addresses->create($request->user(), $request->validated());

        return ApiResponse::success('تم حفظ موقعك.', [
            'user' => $addresses->userPayload($request->user()->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success('تم تسجيل الخروج.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\ClientProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Client;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * خطوة ١: بعت كود التفعيل. لازم الرقم يكون متسجل مسبقاً كمهندس أو
     * كعميل (المهندس هو اللي بيضيف العميل من الأساس).
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->value();

        if (! $this->accountExists($phone)) {
            throw ValidationException::withMessages([
                'phone' => 'هذا الرقم غير مسجل في النظام، تواصل مع المهندس المسؤول.',
            ]);
        }

        $this->otp->issue($phone);

        return response()->json([
            'message' => 'تم إرسال كود التفعيل.',
        ]);
    }

    /**
     * خطوة ٢: تأكيد الكود، وإرجاع Bearer token + نوع الحساب.
     * `fcm_token` اختياري - لو الموبايل بعته هنا بنسجّله على طول (أسهل من
     * عمل ريكوست تاني فورًا بعد الدخول)، وبرضو ممكن يتحدّث لاحقًا عن طريق
     * `POST /auth/fcm-token` (مهم لأن توكن الـ FCM بيتجدد لوحده أحيانًا من
     * غير ما المستخدم يعمل تسجيل دخول جديد).
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->value();
        $code = $request->string('code')->value();

        if (! $this->otp->verify($phone, $code)) {
            throw ValidationException::withMessages([
                'code' => 'كود التفعيل غير صحيح أو منتهي الصلاحية.',
            ]);
        }

        $fcmToken = $request->filled('fcm_token') ? $request->string('fcm_token')->value() : null;

        $engineer = User::where('phone', $phone)->first();

        if ($engineer) {
            $engineer->forceFill([
                'phone_verified_at' => $engineer->phone_verified_at ?? now(),
                'fcm_token' => $fcmToken ?? $engineer->fcm_token,
            ])->save();

            return response()->json([
                'token' => $engineer->createToken('mobile')->plainTextToken,
                'role' => 'engineer',
                'profile' => new UserResource($engineer),
            ]);
        }

        $client = Client::where('phone', $phone)->first();

        if ($client) {
            $client->forceFill([
                'phone_verified_at' => $client->phone_verified_at ?? now(),
                'fcm_token' => $fcmToken ?? $client->fcm_token,
            ])->save();

            return response()->json([
                'token' => $client->createToken('mobile')->plainTextToken,
                'role' => 'farmer',
                'profile' => new ClientProfileResource($client),
            ]);
        }

        // مستحيل نوصل هنا عملياً لأن requestOtp بيرفض الأرقام غير المسجلة،
        // بس بنتحوط لو الحساب اتمسح بين الخطوتين.
        throw ValidationException::withMessages([
            'phone' => 'هذا الرقم غير مسجل في النظام.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            return response()->json(['role' => 'engineer', 'profile' => new UserResource($user)]);
        }

        return response()->json(['role' => 'farmer', 'profile' => new ClientProfileResource($user)]);
    }

    /**
     * تحديث توكن إشعارات الموبايل (FCM) بعد الدخول - الموبايل بيبعته هنا
     * كل ما يتغيّر (حتى من غير تسجيل دخول جديد).
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->forceFill([
            'fcm_token' => $request->string('fcm_token')->value(),
        ])->save();

        return response()->json(['message' => 'تم تحديث توكن الإشعارات.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }

    private function accountExists(string $phone): bool
    {
        return User::where('phone', $phone)->exists() || Client::where('phone', $phone)->exists();
    }
}

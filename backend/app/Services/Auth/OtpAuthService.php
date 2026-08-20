<?php

namespace App\Services\Auth;

use App\Contracts\WhatsAppSender;
use App\Enums\UserRole;
use App\Exceptions\OtpException;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpAuthService
{
    public function __construct(private readonly WhatsAppSender $whatsApp) {}

    /**
     * @return array<string, mixed>
     */
    public function request(string $rawPhone, ?string $ip): array
    {
        $phone = Phone::normalize($rawPhone);
        if ($phone === null) {
            throw new OtpException('رقم الجوال غير صالح. أدخل رقماً سعودياً مثل 5xxxxxxxx.');
        }

        $existing = User::query()
            ->where('phone', $phone)
            ->where('role', UserRole::Customer)
            ->first();

        if ($existing !== null) {
            return $this->issueSession($existing) + [
                'otp_required' => false,
                'phone' => Phone::national($phone),
            ];
        }

        $ttl = (int) config('whatsapp.otp_ttl', 300);
        $resendAfter = (int) config('whatsapp.resend_seconds', 60);

        $latest = PhoneOtp::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($latest && $latest->created_at->gt(now()->subSeconds($resendAfter))) {
            $wait = max(1, $resendAfter - (int) $latest->created_at->diffInSeconds(now()));
            throw new OtpException(
                'يرجى الانتظار '.$wait.' ثانية قبل إعادة إرسال الرمز.',
                429,
            );
        }

        PhoneOtp::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $otp = PhoneOtp::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds($ttl),
            'ip_address' => $ip,
        ]);

        try {
            $this->whatsApp->sendOtp($phone, $code);
        } catch (OtpException $e) {
            $otp->delete();
            throw $e;
        }

        $payload = [
            'otp_required' => true,
            'phone' => Phone::national($phone),
            'from_phone' => Phone::companyNational(),
            'expires_in' => $ttl,
            'resend_in' => $resendAfter,
        ];

        if ($this->shouldExposeDebugCode()) {
            $payload['debug_code'] = $code;
        }

        return $payload;
    }

    /**
     * @return array{token: string, token_type: string, user: User}
     */
    public function verify(string $rawPhone, string $code): array
    {
        $phone = Phone::normalize($rawPhone);
        if ($phone === null) {
            throw new OtpException('رقم الهاتف غير صالح.');
        }

        $otp = PhoneOtp::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($otp === null) {
            throw new OtpException('لا يوجد رمز تحقق نشط. اطلب رمزاً جديداً.');
        }

        if ($otp->isExpired()) {
            $otp->delete();
            throw new OtpException('انتهت صلاحية رمز التحقق. اطلب رمزاً جديداً.');
        }

        $maxAttempts = (int) config('whatsapp.max_attempts', 5);
        if ($otp->attempts >= $maxAttempts) {
            $otp->delete();
            throw new OtpException('تجاوزت عدد المحاولات. اطلب رمزاً جديداً.', 429);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw new OtpException('رمز التحقق غير صحيح.');
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        $user = User::query()->where('phone', $phone)->first();

        if ($user === null) {
            $user = new User;
            $user->forceFill([
                'phone' => $phone,
                'name' => 'عميل',
                'email' => null,
                'password' => Str::password(32),
                'role' => UserRole::Customer,
                'locale' => 'ar',
                'phone_verified_at' => now(),
            ])->save();
        }

        return $this->issueSession($user);
    }

    /**
     * @return array{token: string, token_type: string, user: User}
     */
    private function issueSession(User $user): array
    {
        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(),
        ];
    }

    private function shouldExposeDebugCode(): bool
    {
        return (bool) config('app.debug')
            && config('whatsapp.driver') === 'log';
    }
}

<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;

/**
 * منطق كود التفعيل (OTP) في مكان واحد، عشان ربط مزود SMS حقيقي بعدين
 * يبقى تغيير في مكان واحد فقط (send()) من غير أي تأثير على الـ API.
 *
 * دلوقتي: كود ثابت "1111" لكل الأرقام (زي الديزاين)، ومكتوب في الـ log
 * بدل ما يتبعت SMS فعلي.
 */
class OtpService
{
    private const FIXED_CODE = '1111';

    private const EXPIRES_AFTER_MINUTES = 10;

    public function issue(string $phone): OtpCode
    {
        // نلغي أي كود سابق لسه صالح لنفس الرقم.
        OtpCode::where('phone', $phone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $otp = OtpCode::create([
            'phone' => $phone,
            'code' => self::FIXED_CODE,
            'expires_at' => now()->addMinutes(self::EXPIRES_AFTER_MINUTES),
            'attempts' => 0,
        ]);

        $this->send($phone, $otp->code);

        return $otp;
    }

    /**
     * TODO: استبدال ده بمزود SMS حقيقي (Twilio / بوابة محلية) لما يتحدد.
     */
    private function send(string $phone, string $code): void
    {
        Log::info("[OTP] كود التفعيل لرقم {$phone}: {$code}");
    }

    /**
     * @return bool true لو الكود صحيح وتم استهلاكه.
     */
    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->expires_at->isPast()) {
            return false;
        }

        if ($otp->attempts >= 5) {
            return false;
        }

        if (! hash_equals($otp->code, $code)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}

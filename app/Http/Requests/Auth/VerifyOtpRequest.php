<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'code' => ['required', 'string'],

            // توكن إشعارات الموبايل (FCM) - اختياري، لو الموبايل عنده واحد
            // وقت الدخول نسجّله على طول.
            'fcm_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}

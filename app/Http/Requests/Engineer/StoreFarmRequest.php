<?php

namespace App\Http\Requests\Engineer;

use App\Enums\SubscriptionCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'palm_count' => ['required', 'integer', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],

            // "الصنف" - نص مباشر (مجدول/صعيدي/أو أي نص من خانة "غيره")، مش id.
            'variety_type' => ['nullable', 'string', 'max:255'],
            'palm_age_years' => ['nullable', 'integer', 'min:0'],
            'planted_at' => ['nullable', 'date'],
            'soil_type' => ['nullable', 'string', 'max:255'],
            'water_source' => ['nullable', 'string', 'max:255'],
            'water_analysis' => ['nullable', 'string'],
            'general_notes' => ['nullable', 'string'],

            'subscription_cycle' => ['nullable', Rule::in(array_column(SubscriptionCycle::cases(), 'value'))],
            'subscription_next_payment_date' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests\Engineer;

use App\Enums\SubscriptionCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'palm_count' => ['sometimes', 'required', 'integer', 'min:0'],
            'area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],

            'variety_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'palm_age_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'planted_at' => ['sometimes', 'nullable', 'date'],
            'soil_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'water_source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'water_analysis' => ['sometimes', 'nullable', 'string'],
            'general_notes' => ['sometimes', 'nullable', 'string'],

            'subscription_cycle' => ['sometimes', 'nullable', Rule::in(array_column(SubscriptionCycle::cases(), 'value'))],
            'subscription_next_payment_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

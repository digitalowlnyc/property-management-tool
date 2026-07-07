<?php

namespace App\Http\Requests;

use App\Enums\Frequency;
use App\Enums\IncomeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Must reference a property owned by the current user.
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('user_id', $this->user()->id),
            ],
            'type' => ['required', Rule::enum(IncomeType::class)],
            'label' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', Rule::enum(Frequency::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}

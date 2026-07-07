<?php

namespace App\Http\Requests;

use App\Enums\AmountType;
use App\Enums\Frequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
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
        // Dollars when fixed, percent when percentage, and a count of months
        // (1–12) when months-of-rent.
        $amountRules = ['required', 'numeric'];
        if ($this->input('amount_type') === AmountType::MonthsOfRent->value) {
            $amountRules[] = 'min:1';
            $amountRules[] = 'max:12';
        } else {
            $amountRules[] = 'min:0';
        }

        return [
            // Must reference a property owned by the current user.
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('user_id', $this->user()->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'frequency' => ['required', Rule::enum(Frequency::class)],
            'amount_type' => ['required', Rule::enum(AmountType::class)],
            'amount' => $amountRules,
            // Only meaningful for rent-based expenses; null falls back to current rent.
            'rent_basis' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

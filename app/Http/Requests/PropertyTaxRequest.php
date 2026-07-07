<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyTaxRequest extends FormRequest
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
        $propertyId = $this->input('property_id') ?? $this->route('propertyTax')?->property_id;

        return [
            // Must reference a property owned by the current user.
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('user_id', $this->user()->id),
            ],
            'year' => [
                'required',
                'integer',
                'min:1900',
                'max:2100',
                // Only one tax figure per property per year.
                Rule::unique('property_taxes')
                    ->where('property_id', $propertyId)
                    ->ignore($this->route('propertyTax')),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'estimated' => ['boolean'],
            'bill_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.unique' => 'A tax amount for this property and year already exists.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = Str::upper(trim((string) $this->input('code', '')));

        $this->merge([
            'code' => $code,
            'type' => trim((string) $this->input('type', '')),
            'is_active' => $this->boolean('is_active'),
            'usage_limit' => $this->filled('usage_limit') ? (int) $this->input('usage_limit') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('coupons', 'code')],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') === 'percentage' && (float) $this->input('value') > 100) {
                $validator->errors()->add('value', 'El descuento porcentual no puede ser mayor a 100%.');
            }
        });
    }
}

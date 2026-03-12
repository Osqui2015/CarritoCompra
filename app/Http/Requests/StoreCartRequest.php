<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCartRequest extends FormRequest
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
        $customerPhone = trim((string) $this->input('customer_phone', ''));
        $notes = trim((string) $this->input('notes', ''));
        $couponCode = Str::upper(trim((string) $this->input('coupon_code', '')));

        $items = collect($this->input('items', []))
            ->filter(fn(mixed $item): bool => is_array($item))
            ->map(fn(array $item): array => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => max(0, (int) ($item['quantity'] ?? 0)),
            ])
            ->filter(fn(array $item): bool => $item['product_id'] > 0 && $item['quantity'] > 0)
            ->values()
            ->all();

        $this->merge([
            'customer_name' => trim((string) $this->input('customer_name', '')),
            'customer_email' => strtolower(trim((string) $this->input('customer_email', ''))),
            'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
            'shipping_address' => trim((string) $this->input('shipping_address', '')),
            'notes' => $notes !== '' ? $notes : null,
            'coupon_code' => $couponCode !== '' ? $couponCode : null,
            'items' => $items,
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
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Ingresa el nombre de contacto.',
            'customer_email.required' => 'Ingresa un correo electronico.',
            'customer_email.email' => 'Ingresa un correo electronico valido.',
            'shipping_address.required' => 'Ingresa la direccion de entrega.',
            'items.required' => 'Selecciona al menos un producto.',
            'items.min' => 'Selecciona al menos un producto.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados ya no esta disponible.',
            'items.*.quantity.min' => 'Cada producto debe tener al menos una unidad.',
        ];
    }
}

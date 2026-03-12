<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        $categoryName = trim((string) $this->input('category_name', ''));
        $secondaryCategoryName = trim((string) $this->input('secondary_category_name', ''));
        $heroTag = trim((string) $this->input('hero_tag', ''));
        $description = trim((string) $this->input('description', ''));
        $slug = Str::slug((string) $this->input('slug', ''));

        $categoryIds = collect((array) $this->input('category_ids', []))
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'slug' => $slug !== '' ? $slug : null,
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'category_name' => $categoryName !== '' ? $categoryName : null,
            'secondary_category_name' => $secondaryCategoryName !== '' ? $secondaryCategoryName : null,
            'category_ids' => $categoryIds,
            'hero_tag' => $heroTag !== '' ? $heroTag : null,
            'description' => $description !== '' ? $description : null,
            'is_active' => $this->boolean('is_active'),
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
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('products', 'slug')],
            'category_id' => ['nullable', 'integer', 'exists:categories,id', 'required_without:category_name'],
            'category_name' => ['nullable', 'string', 'max:120', 'required_without:category_id'],
            'secondary_category_name' => ['nullable', 'string', 'max:120'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'hero_tag' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShopItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:1', 'max:1000000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'Rasm hajmi 2 MB dan oshmasligi kerak.',
            'image.mimes' => 'Faqat JPG, PNG yoki WEBP rasm yuklash mumkin.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'mahsulot nomi',
            'description' => 'tavsif',
            'price' => 'narx',
            'stock' => 'soni',
            'image' => 'rasm',
        ];
    }

    /**
     * Attributes ready for ShopItem::create() / update(). An empty stock field means unlimited.
     */
    public function payload(): array
    {
        $data = $this->safe()->except('image');

        return [
            ...$data,
            'stock' => $this->filled('stock') ? (int) $this->input('stock') : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}

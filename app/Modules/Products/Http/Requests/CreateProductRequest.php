<?php

namespace App\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku'         => ['required', 'string'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'sku'         => ['description' => 'Identificador único do produto', 'example' => 'PROD-001'],
            'name'        => ['description' => 'Nome do produto', 'example' => 'Produto Teste'],
            'description' => ['description' => 'Descrição detalhada do produto', 'example' => 'Descrição do produto teste'],
            'price'       => ['description' => 'Preço do produto', 'example' => 149.90],
            'stock'       => ['description' => 'Quantidade em estoque', 'example' => 50],
        ];
    }
}

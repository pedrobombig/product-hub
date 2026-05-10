<?php

namespace App\Modules\Sqs\Http\Requests;

use App\Domain\JobLogs\Enums\JobTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishSqsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_type'         => ['required', 'string', 'in:' . implode(',', JobTypeEnum::getAllKeys())],
            'product_sku'      => ['required', 'string'],
            'data'             => ['required', 'array'],
            'data.stock'       => [Rule::requiredIf($this->job_type === 'update_stock'), 'integer', 'min:0'],
            'data.price'       => [Rule::requiredIf($this->job_type === 'update_price'), 'numeric', 'min:0'],
            'data.description' => [Rule::requiredIf($this->job_type === 'update_description'), 'string'],
            'data.images'      => [Rule::requiredIf($this->job_type === 'update_images'), 'array'],
            'data.images.*'    => ['url'],
            'data.tags'        => [Rule::requiredIf($this->job_type === 'update_tags'), 'array'],
            'data.tags.*'      => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'job_type.required'         => 'O tipo de job é obrigatório.',
            'job_type.in'               => 'O tipo de job informado é inválido.',
            'product_sku.required'      => 'O SKU do produto é obrigatório.',
            'data.required'             => 'Os dados do job são obrigatórios.',
            'data.array'                => 'O campo data deve ser um array.',
            'data.stock.required'       => 'O campo stock é obrigatório para update_stock.',
            'data.stock.integer'        => 'O campo stock deve ser um número inteiro.',
            'data.stock.min'            => 'O campo stock não pode ser negativo.',
            'data.price.required'       => 'O campo price é obrigatório para update_price.',
            'data.price.numeric'        => 'O campo price deve ser numérico.',
            'data.price.min'            => 'O campo price não pode ser negativo.',
            'data.description.required' => 'O campo description é obrigatório para update_description.',
            'data.description.string'   => 'O campo description deve ser uma string.',
            'data.images.required'      => 'O campo images é obrigatório para update_images.',
            'data.images.array'         => 'O campo images deve ser um array.',
            'data.images.*.url'         => 'Cada imagem deve ser uma URL válida.',
            'data.tags.required'        => 'O campo tags é obrigatório para update_tags.',
            'data.tags.array'           => 'O campo tags deve ser um array.',
            'data.tags.*.string'        => 'Cada tag deve ser uma string.',
        ];
    }
}

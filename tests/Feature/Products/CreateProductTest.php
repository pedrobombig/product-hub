<?php

use App\Data\Products\Models\Product;
use Illuminate\Testing\Fluent\AssertableJson;

describe('POST /api/v1/products', function () {

    it('cria um produto com sucesso', function () {
        $payload = [
            'sku'         => 'SKU-001',
            'name'        => 'Produto Teste',
            'description' => 'Descrição do produto',
            'price'       => 99.90,
            'stock'       => 10,
        ];

        $this->postJson('/api/v1/products', $payload)
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('message')
                     ->has('payload')
                     ->where('payload.sku', 'SKU-001')
                     ->where('payload.name', 'Produto Teste')
                     ->etc()
            );

        $this->assertDatabaseHas('products', ['sku' => 'SKU-001']);
    });

    it('retorna 409 quando SKU já existe', function () {
        Product::factory()->create(['sku' => 'SKU-DUP']);

        $this->postJson('/api/v1/products', [
            'sku'   => 'SKU-DUP',
            'name'  => 'Outro Produto',
            'description' => 'Qualquer descrição',
            'price' => 10.00,
            'stock' => 5,
        ])->assertConflict();
    });

    it('retorna 422 quando dados obrigatórios estão ausentes', function () {
        $this->postJson('/api/v1/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'name']);
    });

    it('retorna 422 para preço negativo', function () {
        $this->postJson('/api/v1/products', [
            'sku'   => 'SKU-NEG',
            'name'  => 'Produto',
            'price' => -1,
            'stock' => 0,
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['price']);
    });

    it('retorna 422 para estoque negativo', function () {
        $this->postJson('/api/v1/products', [
            'sku'   => 'SKU-NEG-STOCK',
            'name'  => 'Produto',
            'price' => 10.00,
            'stock' => -5,
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['stock']);
    });
});

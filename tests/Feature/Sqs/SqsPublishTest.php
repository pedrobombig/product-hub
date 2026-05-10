<?php

use Illuminate\Support\Facades\Queue;

describe('POST /api/v1/sqs/publish', function () {

    it('publica mensagem na fila com sucesso', function () {
        Queue::fake();

        $payload = [
            'job_type'    => 'update_stock',
            'product_sku' => 'SKU-001',
            'data'        => ['stock' => 50],
        ];

        $this->postJson('/api/v1/sqs/publish', $payload)
            ->assertCreated()
            ->assertJsonFragment(['message' => 'Mensagem publicada na fila SQS']);
    });

    it('retorna 422 para job_type inválido', function () {
        $this->postJson('/api/v1/sqs/publish', [
            'job_type'    => 'tipo_invalido',
            'product_sku' => 'SKU-001',
            'data'        => [],
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['job_type']);
    });

    it('retorna 422 quando product_sku está ausente', function () {
        $this->postJson('/api/v1/sqs/publish', [
            'job_type' => 'update_stock',
            'data'     => ['stock' => 10],
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['product_sku']);
    });

    it('retorna 422 quando data está ausente', function () {
        $this->postJson('/api/v1/sqs/publish', [
            'job_type'    => 'update_stock',
            'product_sku' => 'SKU-001',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['data']);
    });
});

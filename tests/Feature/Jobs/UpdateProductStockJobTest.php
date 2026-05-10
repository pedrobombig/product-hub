<?php

use App\Data\JobLogs\Models\JobLog;
use App\Data\Products\Models\Product;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\Products\Jobs\UpdateProductStockJob;

describe('UpdateProductStockJob', function () {
    it('atualiza o estoque e registra log de sucesso', function () {
        $product = Product::factory()->create(['sku' => 'SKU-ST01', 'stock' => 10]);

        $job = new UpdateProductStockJob('SKU-ST01', 50);
        $job->setJob(fakeJobWithId('msg-stock-001'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductStockAction::class),
        );

        expect($product->fresh()->stock)->toBe(50);

        $this->assertDatabaseHas('job_logs', [
            'product_sku'    => 'SKU-ST01',
            'product_id'     => $product->id,
            'job_type'       => JobTypeEnum::UPDATE_STOCK->value,
            'status'         => JobStatusEnum::SUCCESS->value,
            'sqs_message_id' => 'msg-stock-001',
        ]);
    });

    it('ignora job duplicado e mantém estoque original', function () {
        $product = Product::factory()->create(['sku' => 'SKU-ST02', 'stock' => 10]);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-ST02',
            'sqs_message_id' => 'msg-stock-dup',
            'status'         => JobStatusEnum::SUCCESS->value,
            'job_type'       => JobTypeEnum::UPDATE_STOCK->value,
            'payload'        => ['stock' => 50],
        ]);

        $job = new UpdateProductStockJob('SKU-ST02', 99);
        $job->setJob(fakeJobWithId('msg-stock-dup'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductStockAction::class),
        );

        expect($product->fresh()->stock)->toBe(10);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-stock-dup',
            'status'         => JobStatusEnum::DUPLICATED->value,
            'product_sku'    => 'SKU-ST02',
            'product_id'     => $product->id,
        ]);
    });

    it('lança exceção para produto inexistente', function () {
        $job = new UpdateProductStockJob('SKU-NOPE', 50);
        $job->setJob(fakeJobWithId('msg-stock-fail'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductStockAction::class),
        ))->toThrow(\DomainException::class, 'Produto não encontrado');
    });

    it('registra log de falha quando uma exceção inesperada ocorre', function () {
        $product = Product::factory()->create(['sku' => 'SKU-ST03', 'stock' => 0]);

        $this->mock(\App\Domain\Products\Actions\UpdateProductStockAction::class)
            ->shouldReceive('execute')
            ->andThrow(new \RuntimeException('Erro de banco de dados'));

        $job = new UpdateProductStockJob('SKU-ST03', 50);
        $job->setJob(fakeJobWithId('msg-stock-err'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductStockAction::class),
        ))->toThrow(\RuntimeException::class, 'Erro de banco de dados');

        // garante que saiu do pending para failed
        $this->assertDatabaseMissing('job_logs', [
            'sqs_message_id' => 'msg-stock-err',
            'status'         => JobStatusEnum::PENDING->value,
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-ST03',
        ]);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-stock-err',
            'status'         => JobStatusEnum::FAILED->value,
            'error_message'  => 'Erro de banco de dados',
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-ST03',
        ]);
    });

    it('reprocessa job que ficou preso em pending', function () {
        $product = Product::factory()->create(['sku' => 'SKU-ST04', 'stock' => 10]);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-ST04',
            'sqs_message_id' => 'msg-stock-pending',
            'status'         => JobStatusEnum::PENDING->value,
            'job_type'       => JobTypeEnum::UPDATE_STOCK->value,
            'payload'        => ['stock' => 50],
        ]);

        $job = new UpdateProductStockJob('SKU-ST04', 50);
        $job->setJob(fakeJobWithId('msg-stock-pending'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductStockAction::class),
        );

        expect($product->fresh()->stock)->toBe(50);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-stock-pending',
            'status'         => JobStatusEnum::SUCCESS->value,
            'product_sku'    => 'SKU-ST04',
            'product_id'     => $product->id,
        ]);
    });
});

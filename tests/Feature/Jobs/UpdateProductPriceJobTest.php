<?php

use App\Data\JobLogs\Models\JobLog;
use App\Data\Products\Models\Product;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\Products\Jobs\UpdateProductPriceJob;

describe('UpdateProductPriceJob', function () {
    it('atualiza o preço e registra log de sucesso', function () {
        $product = Product::factory()->create(['sku' => 'SKU-P01', 'price' => 10.00]);

        $job = new UpdateProductPriceJob('SKU-P01', 249.90);
        $job->setJob(fakeJobWithId('msg-price-001'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductPriceAction::class),
        );

        expect((float) $product->fresh()->price)->toBe(249.90);

        $this->assertDatabaseHas('job_logs', [
            'product_sku'    => 'SKU-P01',
            'product_id'     => $product->id,
            'job_type'       => JobTypeEnum::UPDATE_PRICE->value,
            'status'         => JobStatusEnum::SUCCESS->value,
            'sqs_message_id' => 'msg-price-001',
        ]);
    });

    it('ignora job duplicado e mantém preço original', function () {
        $product = Product::factory()->create(['sku' => 'SKU-P02', 'price' => 10.00]);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-P02',
            'sqs_message_id' => 'msg-price-dup',
            'status'         => JobStatusEnum::SUCCESS->value,
            'job_type'       => JobTypeEnum::UPDATE_PRICE->value,
            'payload'        => ['price' => 999.00],
        ]);

        $job = new UpdateProductPriceJob('SKU-P02', 999.00);
        $job->setJob(fakeJobWithId('msg-price-dup'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductPriceAction::class),
        );

        expect((float) $product->fresh()->price)->toBe(10.00);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-price-dup',
            'status'         => JobStatusEnum::DUPLICATED->value,
            'product_sku'    => 'SKU-P02',
            'product_id'     => $product->id,
        ]);
    });

    it('lança exceção para produto inexistente', function () {
        $job = new UpdateProductPriceJob('SKU-NOPE', 99.90);
        $job->setJob(fakeJobWithId('msg-price-fail'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductPriceAction::class),
        ))->toThrow(\DomainException::class);
    });

    it('registra log de falha quando uma exceção inesperada ocorre', function () {
        $product = Product::factory()->create(['sku' => 'SKU-P03', 'price' => 10.00]);

        $this->mock(\App\Domain\Products\Actions\UpdateProductPriceAction::class)
            ->shouldReceive('execute')
            ->andThrow(new \RuntimeException('Erro de banco de dados'));

        $job = new UpdateProductPriceJob('SKU-P03', 249.90);
        $job->setJob(fakeJobWithId('msg-price-err'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductPriceAction::class),
        ))->toThrow(\RuntimeException::class, 'Erro de banco de dados');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-price-err',
            'status'         => JobStatusEnum::FAILED->value,
            'error_message'  => 'Erro de banco de dados',
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-P03',
        ]);
    });

    it('reprocessa job que ficou preso em pending', function () {
        $product = Product::factory()->create(['sku' => 'SKU-P04', 'price' => 10.00]);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-P04',
            'sqs_message_id' => 'msg-price-pending',
            'status'         => JobStatusEnum::PENDING->value,
            'job_type'       => JobTypeEnum::UPDATE_PRICE->value,
            'payload'        => ['price' => 249.90],
        ]);

        $job = new UpdateProductPriceJob('SKU-P04', 249.90);
        $job->setJob(fakeJobWithId('msg-price-pending'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductPriceAction::class),
        );

        expect((float) $product->fresh()->price)->toBe(249.90);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-price-pending',
            'status'         => JobStatusEnum::SUCCESS->value,
            'product_sku'    => 'SKU-P04',
            'product_id'     => $product->id,
        ]);
    });
});

<?php

use App\Data\JobLogs\Models\JobLog;
use App\Data\Products\Models\Product;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\Products\Jobs\UpdateProductDescriptionJob;

describe('UpdateProductDescriptionJob', function () {
    it('atualiza a descrição e registra log de sucesso', function () {
        $product = Product::factory()->create(['sku' => 'SKU-D01', 'description' => 'Antiga']);

        $job = new UpdateProductDescriptionJob('SKU-D01', 'Nova descrição');
        $job->setJob(fakeJobWithId('msg-desc-001'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductDescriptionAction::class),
        );

        expect($product->fresh()->description)->toBe('Nova descrição');

        $this->assertDatabaseHas('job_logs', [
            'product_sku'    => 'SKU-D01',
            'product_id'     => $product->id,
            'job_type'       => JobTypeEnum::UPDATE_DESCRIPTION->value,
            'status'         => JobStatusEnum::SUCCESS->value,
            'sqs_message_id' => 'msg-desc-001',
        ]);
    });

    it('ignora job duplicado e não atualiza a descrição', function () {
        $product = Product::factory()->create(['sku' => 'SKU-D02', 'description' => 'Original']);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-D02',
            'sqs_message_id' => 'msg-desc-dup',
            'status'         => JobStatusEnum::SUCCESS->value,
            'job_type'       => JobTypeEnum::UPDATE_DESCRIPTION->value,
            'payload'        => ['description' => 'Tentativa anterior'],
        ]);

        $job = new UpdateProductDescriptionJob('SKU-D02', 'Tentativa duplicada');
        $job->setJob(fakeJobWithId('msg-desc-dup'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductDescriptionAction::class),
        );

        expect($product->fresh()->description)->toBe('Original');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-desc-dup',
            'status'         => JobStatusEnum::DUPLICATED->value,
            'product_sku'    => 'SKU-D02',
            'product_id'     => $product->id,
        ]);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-desc-dup',
            'status'         => JobStatusEnum::SUCCESS->value,
            'product_sku'    => 'SKU-D02',
            'product_id'     => $product->id,
        ]);
    });

    it('lança exceção para produto inexistente', function () {
        $job = new UpdateProductDescriptionJob('SKU-NOPE', 'Qualquer coisa');
        $job->setJob(fakeJobWithId('msg-desc-fail'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductDescriptionAction::class),
        ))->toThrow(\DomainException::class);
    });

    it('registra log de falha quando uma exceção inesperada ocorre', function () {
        $product = Product::factory()->create(['sku' => 'SKU-D03', 'description' => 'Original']);

        $this->mock(\App\Domain\Products\Actions\UpdateProductDescriptionAction::class)
            ->shouldReceive('execute')
            ->andThrow(new \RuntimeException('Erro de banco de dados'));

        $job = new UpdateProductDescriptionJob('SKU-D03', 'Nova');
        $job->setJob(fakeJobWithId('msg-desc-err'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductDescriptionAction::class),
        ))->toThrow(\RuntimeException::class, 'Erro de banco de dados');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-desc-err',
            'status'         => JobStatusEnum::FAILED->value,
            'error_message'  => 'Erro de banco de dados',
            'product_id'     => $product->id,
        ]);
    });

    it('reprocessa job que ficou preso em pending', function () {
        $product = Product::factory()->create(['sku' => 'SKU-D04', 'description' => 'Antiga']);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-D04',
            'sqs_message_id' => 'msg-desc-pending',
            'status'         => JobStatusEnum::PENDING->value,
            'job_type'       => JobTypeEnum::UPDATE_DESCRIPTION->value,
            'payload'        => ['description' => 'Nova descrição'],
        ]);

        $job = new UpdateProductDescriptionJob('SKU-D04', 'Nova descrição');
        $job->setJob(fakeJobWithId('msg-desc-pending'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\UpdateProductDescriptionAction::class),
        );

        expect($product->fresh()->description)->toBe('Nova descrição');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-desc-pending',
            'status'         => JobStatusEnum::SUCCESS->value,
            'product_sku'    => 'SKU-D04',
            'product_id'     => $product->id,
        ]);
    });
});

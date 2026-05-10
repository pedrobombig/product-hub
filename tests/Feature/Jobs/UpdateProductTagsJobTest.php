<?php

use App\Data\JobLogs\Models\JobLog;
use App\Data\Products\Models\Product;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\Products\Jobs\UpdateProductTagsJob;

describe('UpdateProductTagsJob', function () {
    it('adiciona tags ao produto e registra log de sucesso', function () {
        $product = Product::factory()->create(['sku' => 'SKU-T01']);

        $job = new UpdateProductTagsJob('SKU-T01', ['eletrônico', 'premium']);
        $job->setJob(fakeJobWithId('msg-tags-001'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductTagsAction::class),
        );

        expect($product->fresh()->tags->pluck('name')->all())
            ->toContain('eletrônico', 'premium');

        $this->assertDatabaseHas('job_logs', [
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-T01',
            'job_type'       => JobTypeEnum::UPDATE_TAGS->value,
            'status'         => JobStatusEnum::SUCCESS->value,
            'sqs_message_id' => 'msg-tags-001',
        ]);
    });

    it('não duplica tags já existentes', function () {
        $product = Product::factory()->create(['sku' => 'SKU-T02']);
        $product->tags()->create(['name' => 'eletrônico']);

        $job = new UpdateProductTagsJob('SKU-T02', ['eletrônico', 'novo']);
        $job->setJob(fakeJobWithId('msg-tags-002'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductTagsAction::class),
        );

        expect($product->fresh()->tags)->toHaveCount(2);
    });

    it('ignora job duplicado e não atualiza as tags', function () {
        $product = Product::factory()->create(['sku' => 'SKU-T03']);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-T03',
            'sqs_message_id' => 'msg-tags-dup',
            'status'         => JobStatusEnum::SUCCESS->value,
            'job_type'       => JobTypeEnum::UPDATE_TAGS->value,
            'payload'        => ['tags' => ['tentativa anterior']],
        ]);

        $job = new UpdateProductTagsJob('SKU-T03', ['tentativa duplicada']);
        $job->setJob(fakeJobWithId('msg-tags-dup'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductTagsAction::class),
        );

        expect($product->fresh()->tags)->toHaveCount(0);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-tags-dup',
            'status'         => JobStatusEnum::DUPLICATED->value,
            'product_sku'    => 'SKU-T03',
            'product_id'     => $product->id,
        ]);
    });

    it('lança exceção para produto inexistente', function () {
        $job = new UpdateProductTagsJob('SKU-NOPE', ['qualquer']);
        $job->setJob(fakeJobWithId('msg-tags-fail'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductTagsAction::class),
        ))->toThrow(\DomainException::class);
    });

    it('registra log de falha quando uma exceção inesperada ocorre', function () {
        $product = Product::factory()->create(['sku' => 'SKU-T04']);

        $this->mock(\App\Domain\Products\Actions\AddProductTagsAction::class)
            ->shouldReceive('execute')
            ->andThrow(new \RuntimeException('Erro de banco de dados'));

        $job = new UpdateProductTagsJob('SKU-T04', ['eletrônico']);
        $job->setJob(fakeJobWithId('msg-tags-err'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductTagsAction::class),
        ))->toThrow(\RuntimeException::class, 'Erro de banco de dados');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-tags-err',
            'status'         => JobStatusEnum::FAILED->value,
            'error_message'  => 'Erro de banco de dados',
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-T04',
        ]);
    });

    it('reprocessa job que ficou preso em pending', function () {
        $product = Product::factory()->create(['sku' => 'SKU-T05']);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-T05',
            'sqs_message_id' => 'msg-tags-pending',
            'status'         => JobStatusEnum::PENDING->value,
            'job_type'       => JobTypeEnum::UPDATE_TAGS->value,
            'payload'        => ['tags' => ['eletrônico', 'premium']],
        ]);

        $job = new UpdateProductTagsJob('SKU-T05', ['eletrônico', 'premium']);
        $job->setJob(fakeJobWithId('msg-tags-pending'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductTagsAction::class),
        );

        expect($product->fresh()->tags->pluck('name')->all())
            ->toContain('eletrônico', 'premium');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-tags-pending',
            'status'         => JobStatusEnum::SUCCESS->value,
            'product_sku'    => 'SKU-T05',
            'product_id'     => $product->id,
        ]);
    });
});

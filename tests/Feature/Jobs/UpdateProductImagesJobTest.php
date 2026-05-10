<?php

use App\Data\JobLogs\Models\JobLog;
use App\Data\Products\Models\Product;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\Products\Jobs\UpdateProductImagesJob;

describe('UpdateProductImagesJob', function () {
    it('adiciona imagens ao produto e registra log de sucesso', function () {
        $product = Product::factory()->create(['sku' => 'SKU-I01']);

        $job = new UpdateProductImagesJob('SKU-I01', ['https://img.com/a.jpg', 'https://img.com/b.jpg']);
        $job->setJob(fakeJobWithId('msg-images-001'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductImagesAction::class),
        );

        expect($product->fresh()->images->pluck('url')->all())
            ->toContain('https://img.com/a.jpg', 'https://img.com/b.jpg');

        $this->assertDatabaseHas('job_logs', [
            'product_sku'    => 'SKU-I01',
            'product_id'     => $product->id,
            'job_type'       => JobTypeEnum::UPDATE_IMAGES->value,
            'status'         => JobStatusEnum::SUCCESS->value,
            'sqs_message_id' => 'msg-images-001',
        ]);
    });

    it('não duplica imagens já existentes', function () {
        $product = Product::factory()->create(['sku' => 'SKU-I02']);
        $product->images()->create(['url' => 'https://img.com/a.jpg', 'order' => 0]);

        $job = new UpdateProductImagesJob('SKU-I02', ['https://img.com/a.jpg', 'https://img.com/b.jpg']);
        $job->setJob(fakeJobWithId('msg-images-002'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductImagesAction::class),
        );

        expect($product->fresh()->images)->toHaveCount(2);
    });

    it('ignora job duplicado e não atualiza as imagens', function () {
        $product = Product::factory()->create(['sku' => 'SKU-I03']);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-I03',
            'sqs_message_id' => 'msg-images-dup',
            'status'         => JobStatusEnum::SUCCESS->value,
            'job_type'       => JobTypeEnum::UPDATE_IMAGES->value,
            'payload'        => ['images' => ['https://img.com/antiga.jpg']],
        ]);

        $job = new UpdateProductImagesJob('SKU-I03', ['https://img.com/nova.jpg']);
        $job->setJob(fakeJobWithId('msg-images-dup'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductImagesAction::class),
        );

        expect($product->fresh()->images)->toHaveCount(0);

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-images-dup',
            'status'         => JobStatusEnum::DUPLICATED->value,
            'product_sku'    => 'SKU-I03',
            'product_id'     => $product->id,
        ]);
    });

    it('lança exceção para produto inexistente', function () {
        $job = new UpdateProductImagesJob('SKU-NOPE', ['https://img.com/qualquer.jpg']);
        $job->setJob(fakeJobWithId('msg-images-fail'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductImagesAction::class),
        ))->toThrow(\DomainException::class);
    });

    it('registra log de falha quando uma exceção inesperada ocorre', function () {
        $product = Product::factory()->create(['sku' => 'SKU-I04']);

        $this->mock(\App\Domain\Products\Actions\AddProductImagesAction::class)
            ->shouldReceive('execute')
            ->andThrow(new \RuntimeException('Erro de banco de dados'));

        $job = new UpdateProductImagesJob('SKU-I04', ['https://img.com/a.jpg']);
        $job->setJob(fakeJobWithId('msg-images-err'));

        expect(fn () => $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductImagesAction::class),
        ))->toThrow(\RuntimeException::class, 'Erro de banco de dados');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-images-err',
            'status'         => JobStatusEnum::FAILED->value,
            'error_message'  => 'Erro de banco de dados',
            'product_id'     => $product->id,
        ]);
    });

    it('reprocessa job que ficou preso em pending', function () {
        $product = Product::factory()->create(['sku' => 'SKU-I05']);

        JobLog::factory()->create([
            'product_id'     => $product->id,
            'product_sku'    => 'SKU-I05',
            'sqs_message_id' => 'msg-images-pending',
            'status'         => JobStatusEnum::PENDING->value,
            'job_type'       => JobTypeEnum::UPDATE_IMAGES->value,
            'payload'        => ['images' => ['https://img.com/a.jpg']],
        ]);

        $job = new UpdateProductImagesJob('SKU-I05', ['https://img.com/a.jpg']);
        $job->setJob(fakeJobWithId('msg-images-pending'));

        $job->handle(
            app(\App\Domain\JobLogs\Repositories\JobLogRepositoryInterface::class),
            app(\App\Domain\Products\Repositories\ProductRepositoryInterface::class),
            app(\App\Domain\JobLogs\Actions\CreateJobLogAction::class),
            app(\App\Domain\Products\Actions\AddProductImagesAction::class),
        );

        expect($product->fresh()->images->pluck('url')->all())
            ->toContain('https://img.com/a.jpg');

        $this->assertDatabaseHas('job_logs', [
            'sqs_message_id' => 'msg-images-pending',
            'status'         => JobStatusEnum::SUCCESS->value,
            'product_sku'    => 'SKU-I05',
            'product_id'     => $product->id,
        ]);
    });
});

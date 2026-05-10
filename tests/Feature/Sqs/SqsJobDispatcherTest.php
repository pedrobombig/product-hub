<?php

use App\Domain\Products\Jobs\UpdateProductDescriptionJob;
use App\Domain\Products\Jobs\UpdateProductImagesJob;
use App\Domain\Products\Jobs\UpdateProductPriceJob;
use App\Domain\Products\Jobs\UpdateProductSkuJob;
use App\Domain\Products\Jobs\UpdateProductStockJob;
use App\Domain\Products\Jobs\UpdateProductTagsJob;
use App\Infrastructure\Sqs\SqsJobDispatcher;
use Illuminate\Support\Facades\Queue;

describe('SqsJobDispatcher', function () {

    beforeEach(fn () => Queue::fake());

    it('despacha UpdateProductStockJob para job_type update_stock', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type'    => 'update_stock',
            'product_sku' => 'SKU-001',
            'data'        => ['stock' => 50],
        ]);

        Queue::assertPushed(UpdateProductStockJob::class);
    });

    it('despacha UpdateProductPriceJob para job_type update_price', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type'    => 'update_price',
            'product_sku' => 'SKU-001',
            'data'        => ['price' => 99.90],
        ]);

        Queue::assertPushed(UpdateProductPriceJob::class);
    });

    it('despacha UpdateProductDescriptionJob para job_type update_description', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type'    => 'update_description',
            'product_sku' => 'SKU-001',
            'data'        => ['description' => 'Nova'],
        ]);

        Queue::assertPushed(UpdateProductDescriptionJob::class);
    });

    it('despacha UpdateProductImagesJob para job_type update_images', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type'    => 'update_images',
            'product_sku' => 'SKU-001',
            'data'        => ['images' => ['https://img.com/1.jpg']],
        ]);

        Queue::assertPushed(UpdateProductImagesJob::class);
    });

    it('despacha UpdateProductTagsJob para job_type update_tags', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type'    => 'update_tags',
            'product_sku' => 'SKU-001',
            'data'        => ['tags' => ['promo']],
        ]);

        Queue::assertPushed(UpdateProductTagsJob::class);
    });

    it('não despacha nenhum job quando job_type é desconhecido', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type'    => 'tipo_invalido',
            'product_sku' => 'SKU-001',
            'data'        => [],
        ]);

        Queue::assertNothingPushed();
    });

    it('não despacha nenhum job quando product_sku está ausente', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'job_type' => 'update_stock',
            'data'     => ['stock' => 50],
        ]);

        Queue::assertNothingPushed();
    });

    it('não despacha nenhum job quando job_type está ausente', function () {
        $dispatcher = new SqsJobDispatcher();
        $dispatcher->dispatch([
            'product_sku' => 'SKU-001',
            'data'        => ['stock' => 50],
        ]);

        Queue::assertNothingPushed();
    });
});

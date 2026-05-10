<?php

namespace App\Infrastructure\Sqs;

use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\Products\Jobs\UpdateProductDescriptionJob;
use App\Domain\Products\Jobs\UpdateProductImagesJob;
use App\Domain\Products\Jobs\UpdateProductPriceJob;
use App\Domain\Products\Jobs\UpdateProductSkuJob;
use App\Domain\Products\Jobs\UpdateProductStockJob;
use App\Domain\Products\Jobs\UpdateProductTagsJob;
use Illuminate\Support\Facades\Log;

class SqsJobDispatcher
{
    public function fire($job, array $data): void
    {
        Log::info('Recebendo mensagem SQS', $data);
        $this->dispatch($data);

        $job->delete();
    }

    public function dispatch(array $message): void
    {
        $jobType = JobTypeEnum::tryFrom($message['job_type'] ?? '');
        $sku     = $message['product_sku'] ?? null;
        $data    = $message['data']        ?? [];

        if (!$jobType || !$sku) {
            Log::warning('Mensagem SQS inválida recebida', $message);
            return;
        }

        match ($jobType) {
            JobTypeEnum::UPDATE_DESCRIPTION => UpdateProductDescriptionJob::dispatch($sku, $data['description']),
            JobTypeEnum::UPDATE_IMAGES      => UpdateProductImagesJob::dispatch($sku, $data['images']),
            JobTypeEnum::UPDATE_PRICE       => UpdateProductPriceJob::dispatch($sku, $data['price']),
            JobTypeEnum::UPDATE_STOCK       => UpdateProductStockJob::dispatch($sku, $data['stock']),
            JobTypeEnum::UPDATE_TAGS        => UpdateProductTagsJob::dispatch($sku, $data['tags']),
            default => Log::warning("Tipo de job desconhecido: {$jobType}"),
        };
    }
}

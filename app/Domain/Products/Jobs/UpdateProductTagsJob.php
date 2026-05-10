<?php

namespace App\Domain\Products\Jobs;

use App\Domain\JobLogs\Actions\CreateJobLogAction;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;
use App\Domain\Products\Actions\AddProductTagsAction;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use App\Domain\Products\Jobs\Concerns\HandlesJobLogging;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProductTagsJob implements ShouldQueue
{
    use Queueable, HandlesJobLogging;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $productSku,
        private readonly array  $tags,
    ) {}

    public function handle(
        JobLogRepositoryInterface $jobLogRepository,
        ProductRepositoryInterface $productRepository,
        CreateJobLogAction $createJobLogAction,
        AddProductTagsAction $addProductTagsAction,
    ): void {
        $product = $productRepository->findBySku($this->productSku);

        if (!$product) {
            throw new \DomainException("Produto não encontrado: {$this->productSku}");
        }

        $this->executeWithLog(
            jobType:              JobTypeEnum::UPDATE_TAGS,
            productId:            $product->id,
            productSku:           $product->sku,
            payload:              ['tags' => $this->tags],
            jobLogRepository:     $jobLogRepository,
            createJobLogAction:   $createJobLogAction,
            action:               fn () => $addProductTagsAction->execute($product, $this->tags),
        );
    }
}

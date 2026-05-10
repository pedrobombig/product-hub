<?php

namespace App\Domain\Products\Jobs;

use App\Domain\JobLogs\Actions\CreateJobLogAction;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;
use App\Domain\Products\Actions\UpdateProductPriceAction;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use App\Domain\Products\Jobs\Concerns\HandlesJobLogging;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProductPriceJob implements ShouldQueue
{
    use Queueable, HandlesJobLogging;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $productSku,
        private readonly float  $price,
    ) {}

    public function handle(
        JobLogRepositoryInterface $jobLogRepository,
        ProductRepositoryInterface $productRepository,
        CreateJobLogAction $createJobLogAction,
        UpdateProductPriceAction $updateProductPriceAction,
    ): void {
        $product = $productRepository->findBySku($this->productSku);

        if (!$product) {
            throw new \DomainException("Produto não encontrado: {$this->productSku}");
        }

        $this->executeWithLog(
            jobType:              JobTypeEnum::UPDATE_PRICE,
            productId:            $product->id,
            productSku:           $product->sku,
            payload:              ['price' => $this->price],
            jobLogRepository:     $jobLogRepository,
            createJobLogAction:   $createJobLogAction,
            action:               fn () => $updateProductPriceAction->execute($product, $this->price),
        );
    }
}

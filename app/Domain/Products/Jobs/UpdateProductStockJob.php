<?php

namespace App\Domain\Products\Jobs;

use App\Domain\JobLogs\Actions\CreateJobLogAction;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;
use App\Domain\Products\Actions\UpdateProductStockAction;
use App\Domain\Products\Jobs\Concerns\HandlesJobLogging;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProductStockJob implements ShouldQueue
{
    use Queueable, HandlesJobLogging;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $productSku,
        private readonly int    $stock,
    ) {}

    public function handle(
        JobLogRepositoryInterface $jobLogRepository,
        ProductRepositoryInterface $productRepository,
        CreateJobLogAction $createJobLogAction,
        UpdateProductStockAction $updateProductStockAction,
    ): void {
        $product = $productRepository->findBySku($this->productSku);

        if (!$product) {
            throw new \DomainException("Produto não encontrado: {$this->productSku}");
        }

        $this->executeWithLog(
            jobType:              JobTypeEnum::UPDATE_STOCK,
            productId:            $product->id,
            productSku:           $product->sku,
            payload:              ['stock' => $this->stock],
            jobLogRepository:     $jobLogRepository,
            createJobLogAction:   $createJobLogAction,
            action:               fn () => $updateProductStockAction->execute($product, $this->stock),
        );
    }
}

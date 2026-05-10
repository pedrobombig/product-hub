<?php

namespace App\Domain\Products\Jobs;

use App\Domain\JobLogs\Actions\CreateJobLogAction;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;
use App\Domain\Products\Actions\UpdateProductDescriptionAction;
use App\Domain\Products\Jobs\Concerns\HandlesJobLogging;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProductDescriptionJob implements ShouldQueue
{
    use Queueable, HandlesJobLogging;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $productSku,
        private readonly string $description,
    ) {}

    public function handle(
        JobLogRepositoryInterface $jobLogRepository,
        ProductRepositoryInterface $productRepository,
        CreateJobLogAction $createJobLogAction,
        UpdateProductDescriptionAction $updateProductDescriptionAction,
    ): void {
        $product = $productRepository->findBySku($this->productSku);

        if (!$product) {
            throw new \DomainException("Produto não encontrado: {$this->productSku}");
        }

        $this->executeWithLog(
            jobType:              JobTypeEnum::UPDATE_DESCRIPTION,
            productId:            $product->id,
            productSku:           $product->sku,
            payload:              ['description' => $this->description],
            jobLogRepository:     $jobLogRepository,
            createJobLogAction:   $createJobLogAction,
            action:               fn () => $updateProductDescriptionAction->execute($product, $this->description),
        );
    }
}

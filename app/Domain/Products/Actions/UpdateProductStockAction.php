<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;

class UpdateProductStockAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, int $stock): Product
    {
        return $this->repository->updateStock($product, $stock);
    }
}

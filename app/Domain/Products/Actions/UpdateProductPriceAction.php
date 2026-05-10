<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;

class UpdateProductPriceAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, float $price): Product
    {
        return $this->repository->updatePrice($product, $price);
    }
}

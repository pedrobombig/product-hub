<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;

class FindProductBySkuAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(string $sku): Product
    {
        $product = $this->repository->findBySku($sku);

        if (!$product) {
            throw new \InvalidArgumentException("Product not found");
        }

        return $product;
    }
}

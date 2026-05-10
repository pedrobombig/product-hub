<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;

class UpdateProductDescriptionAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, string $description): Product
    {
        return $this->repository->updateDescription($product, $description);
    }
}

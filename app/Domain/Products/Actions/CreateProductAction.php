<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use DomainException;

class CreateProductAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(array $data): Product
    {
        $product = $this->repository->findBySku($data['sku']);

        if ($product) {
            throw new DomainException('Já existe um produto com este SKU.');
        }

        return $this->repository->create($data);
    }
}

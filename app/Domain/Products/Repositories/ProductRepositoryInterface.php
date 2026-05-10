<?php

namespace App\Domain\Products\Repositories;

use App\Data\Products\Models\Product;
use Illuminate\Contracts\Pagination\Paginator;

interface ProductRepositoryInterface
{
    public function simplePaginate(int $perPage = 15): Paginator;
    public function findById(int $id): ?Product;
    public function findBySku(string $sku): ?Product;
    public function create(array $data): Product;
    public function updateStock(Product $product, int $stock): Product;
    public function updatePrice(Product $product, float $price): Product;
    public function updateDescription(Product $product, string $description): Product;
    public function updateSku(Product $product, string $sku): Product;
    public function delete(Product $product): void;
    public function deleteImagesByUrl(Product $product, array $urls): void;
    public function createImage(Product $product, string $url, int $order): void;
    public function deleteTagsByName(Product $product, array $names): void;
    public function createTag(Product $product, string $name): void;
}

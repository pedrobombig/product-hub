<?php

namespace App\Data\Products\Repositories;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private Product $product
    ) {}

    public function simplePaginate(int $perPage = 10): Paginator
    {
        return $this->product
            ->with(['images', 'tags'])
            ->simplePaginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->product
            ->with(['images', 'tags'])
            ->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->product
            ->with(['images', 'tags'])
            ->where('sku', $sku)
            ->first();
    }

    public function create(array $data): Product
    {
        return $this->product->create($data);
    }

    public function updateStock(Product $product, int $stock): Product
    {
        $product->update(['stock' => $stock]);

        return $product;
    }

    public function updatePrice(Product $product, float $price): Product
    {
        $product->update(['price' => $price]);

        return $product;
    }

    public function updateDescription(Product $product, string $description): Product
    {
        $product->update(['description' => $description]);

        return $product;
    }

    public function updateSku(Product $product, string $sku): Product
    {
        $product->update(['sku' => $sku]);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Images
     */
    public function deleteImagesByUrl(Product $product, array $urls): void
    {
        $product->images()
            ->whereIn('url', $urls)
            ->delete();
    }

    public function createImage(Product $product, string $url, int $order): void
    {
        $product->images()->create([
            'url' => $url,
            'order' => $order,
        ]);
    }

    /**
     * Tags
     */
    public function deleteTagsByName(Product $product, array $names): void
    {
        $product->tags()
            ->whereIn('name', $names)
            ->delete();
    }

    public function createTag(Product $product, string $name): void
    {
        $product->tags()->create([
            'name' => $name,
        ]);
    }
}

<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;

class AddProductImagesAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, array $images): Product
    {
        $existing = $product->images->pluck('url')->toArray();
        $newImages = array_diff($images, $existing);

        foreach ($newImages as $index => $url) {
            $this->repository->createImage($product, $url, $index);
        }

        return $product->load(['images']);
    }
}

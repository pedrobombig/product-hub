<?php

namespace App\Domain\Products\Actions;

use App\Data\Products\Models\Product;
use App\Domain\Products\Repositories\ProductRepositoryInterface;

class AddProductTagsAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, array $tags): Product
    {
        $existing = $product->tags->pluck('name')->toArray();
        $newTags = array_diff($tags, $existing);

        foreach ($newTags as $name) {
            $this->repository->createTag($product, $name);
        }

        return $product->load(['tags']);
    }
}

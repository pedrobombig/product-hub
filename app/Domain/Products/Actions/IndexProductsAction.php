<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\Paginator;

class IndexProductsAction
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(int $perPage = 10): Paginator
    {
        return $this->repository->simplePaginate($perPage);
    }
}

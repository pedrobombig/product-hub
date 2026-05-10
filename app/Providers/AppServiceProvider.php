<?php

namespace App\Providers;

use App\Data\JobLogs\Repositories\JobLogRepository;
use App\Data\Products\Repositories\ProductRepository;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;
use App\Domain\Products\Repositories\ProductRepositoryInterface;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(JobLogRepositoryInterface::class, JobLogRepository::class);
    }

    public function boot(): void
    {
        //
    }
}

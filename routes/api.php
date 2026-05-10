<?php

use App\Modules\Products\Http\Controllers\ProductController;
use App\Modules\Sqs\Http\Controllers\SqsTestController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'products'], function () {
        Route::post('', [ProductController::class, 'store'])
            ->name('products.store');
    });

    Route::group(['prefix' => 'sqs'], function () {
        Route::post('publish', [SqsTestController::class, 'publish'])
            ->name('sqs.publish');
    });
});

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();
            $table->enum('job_type', [
                'update_description',
                'update_images',
                'update_price',
                'update_stock',
                'update_tags'
            ]);

            $table->string('product_sku');
            $table->json('payload');
            $table->enum('status', ['pending','success', 'failed', 'retried', 'duplicated']);
            $table->text('error_message')->nullable();
            $table->string('sqs_message_id')->nullable();

            $table->timestamps();

            $table->index('sqs_message_id');
            $table->index('status');
            $table->index('job_type');
            $table->index(['job_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_logs');
    }
};

<?php

namespace App\Data\Products\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'product_id',
        'url',
        'order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Data\JobLogs\Models;

use Database\Factories\JobLogFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobLog extends Model
{
    use HasFactory;

    protected $table = 'job_logs';
    protected $fillable = [
        'job_type',
        'product_id',
        'product_sku',
        'payload',
        'status',
        'error_message',
        'sqs_message_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];


    protected static function newFactory(): JobLogFactory
    {
        return JobLogFactory::new();
    }
}

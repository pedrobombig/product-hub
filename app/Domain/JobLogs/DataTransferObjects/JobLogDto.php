<?php

namespace App\Domain\JobLogs\DataTransferObjects;

use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;

class JobLogDto
{
    public function __construct(
        public readonly JobTypeEnum $job_type,
        public readonly int $product_id,
        public readonly string $product_sku,
        public readonly array $payload,
        public readonly JobStatusEnum $status,
        public readonly ?string $sqs_message_id = null,
        public readonly ?string $error_message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'job_type'        => $this->job_type,
            'product_id'      => $this->product_id,
            'product_sku'     => $this->product_sku,
            'payload'         => $this->payload,
            'status'          => $this->status,
            'sqs_message_id'  => $this->sqs_message_id,
            'error_message'   => $this->error_message,
        ];
    }

}

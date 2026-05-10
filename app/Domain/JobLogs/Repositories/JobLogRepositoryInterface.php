<?php

namespace App\Domain\JobLogs\Repositories;

use App\Data\JobLogs\Models\JobLog;
use App\Domain\JobLogs\Enums\JobStatusEnum;

interface JobLogRepositoryInterface
{
    public function existsSuccessByMessageId(string $messageId): bool;
    public function create(array $data): JobLog;
    public function updateStatusByMessageId(string $messageId, JobStatusEnum $status, ?string $errorMessage = null): void;
    public function existsByMessageId(string $messageId): bool;
}

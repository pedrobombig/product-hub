<?php

namespace App\Data\JobLogs\Repositories;

use App\Data\JobLogs\Models\JobLog;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;

class JobLogRepository implements JobLogRepositoryInterface
{
    public function __construct(
        private JobLog $jobLog
    ) {}

    public function existsSuccessByMessageId(string $messageId): bool
    {
        return $this->jobLog
            ->where('sqs_message_id', $messageId)
            ->where('status', 'success')
            ->exists();
    }

    public function create(array $data): JobLog
    {
        return $this->jobLog->create($data);
    }

    public function updateStatusByMessageId(
        string $messageId,
        JobStatusEnum $status,
        ?string $errorMessage = null
    ): void {
        $this->jobLog->where('sqs_message_id', $messageId)
            ->update([
                'status'        => $status,
                'error_message' => $errorMessage,
            ]);
    }

    public function existsByMessageId(string $messageId): bool
    {
        return $this->jobLog->where('sqs_message_id', $messageId)->exists();
    }
}

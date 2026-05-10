<?php

namespace App\Domain\Products\Jobs\Concerns;

use App\Domain\JobLogs\Actions\CreateJobLogAction;
use App\Domain\JobLogs\DataTransferObjects\JobLogDto;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;

trait HandlesJobLogging
{
    protected function executeWithLog(
        JobTypeEnum $jobType,
        int $productId,
        string $productSku,
        array $payload,
        JobLogRepositoryInterface $jobLogRepository,
        CreateJobLogAction $createJobLogAction,
        callable $action
    ): void {
        $messageId = $this->job->getJobId();

        if ($jobLogRepository->existsSuccessByMessageId($messageId)) {
            $createJobLogAction->execute(new JobLogDto(
                job_type:       $jobType,
                product_id:     $productId,
                product_sku:    $productSku,
                payload:        $payload,
                status:         JobStatusEnum::DUPLICATED,
                sqs_message_id: $messageId,
            ));
            return;
        }

        if (!$jobLogRepository->existsByMessageId($messageId)) {
            $createJobLogAction->execute(new JobLogDto(
                job_type:       $jobType,
                product_id:     $productId,
                product_sku:    $productSku,
                payload:        $payload,
                status:         JobStatusEnum::PENDING,
                sqs_message_id: $messageId,
            ));
        }

        try {
            $action();

            $jobLogRepository->updateStatusByMessageId($messageId, JobStatusEnum::SUCCESS);
        } catch (\Throwable $e) {
            $jobLogRepository->updateStatusByMessageId($messageId, JobStatusEnum::FAILED, $e->getMessage());
            throw $e;
        }
    }
}

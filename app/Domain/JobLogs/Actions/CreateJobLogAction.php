<?php

namespace App\Domain\JobLogs\Actions;

use App\Data\JobLogs\Models\JobLog;
use App\Domain\JobLogs\DataTransferObjects\JobLogDto;
use App\Domain\JobLogs\Repositories\JobLogRepositoryInterface;

class CreateJobLogAction
{
    public function __construct(
        private JobLogRepositoryInterface $repository
    ) {}

    public function execute(JobLogDto $dto): JobLog
    {
        return $this->repository->create($dto->toArray());
    }
}

<?php

namespace App\Infrastructure\Sqs\Commands;

use App\Data\JobLogs\Models\JobLog;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Infrastructure\Sqs\SqsJobDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedJobsCommand extends Command
{
    protected $signature   = 'jobs:retry-failed';
    protected $description = 'Reprocessa jobs que falharam na última hora';

    public function handle(SqsJobDispatcher $dispatcher): void
    {
        $failed = JobLog::where('status', JobStatusEnum::FAILED)
            ->where('created_at', '>=', now()->subHour())
            ->get();

        if ($failed->isEmpty()) {
            $this->info('Nenhum job falho encontrado.');
            return;
        }

        foreach ($failed as $log) {
            $this->info("Reprocessando: {$log->sqs_message_id}");

            $dispatcher->dispatch([
                'job_type'    => $log->job_type,
                'product_sku' => $log->product_sku,
                'data'        => $log->payload,
            ]);
            $log->update(['status' => JobStatusEnum::RETRIED]);
        }

        $this->info("{$failed->count()} job(s) recolocados na fila.");
    }
}

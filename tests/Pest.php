<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

function fakeJobWithId(string $id = 'msg-test-123'): \Illuminate\Contracts\Queue\Job
{
    $mockJob = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
    $mockJob->shouldReceive('getJobId')->andReturn($id);
    $mockJob->shouldReceive('delete')->andReturn(null);
    $mockJob->shouldReceive('release')->andReturn(null);
    $mockJob->shouldReceive('hasFailed')->andReturn(false);
    $mockJob->shouldReceive('markAsFailed')->andReturn(null);

    return $mockJob;
}

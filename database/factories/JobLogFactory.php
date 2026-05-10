<?php

namespace Database\Factories;

use App\Data\JobLogs\Models\JobLog;
use App\Domain\JobLogs\Enums\JobStatusEnum;
use App\Domain\JobLogs\Enums\JobTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobLogFactory extends Factory
{
    protected $model = JobLog::class;

    public function definition(): array
    {
        return [
            'job_type'       => $this->faker->randomElement(JobTypeEnum::cases())->value,
            'product_id'     => \App\Data\Products\Models\Product::factory(),
            'product_sku'    => strtoupper($this->faker->bothify('SKU-####')),
            'payload'        => ['stock' => $this->faker->numberBetween(1, 100)],
            'status'         => JobStatusEnum::SUCCESS->value,
            'error_message'  => null,
            'sqs_message_id' => $this->faker->uuid(),
        ];
    }

    public function success(): static
    {
        return $this->state(['status' => JobStatusEnum::SUCCESS->value, 'error_message' => null]);
    }

    public function failed(string $message = 'Unexpected error'): static
    {
        return $this->state(['status' => JobStatusEnum::FAILED->value, 'error_message' => $message]);
    }

    public function duplicated(): static
    {
        return $this->state(['status' => JobStatusEnum::DUPLICATED->value]);
    }
}

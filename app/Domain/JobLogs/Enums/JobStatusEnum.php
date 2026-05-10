<?php

namespace App\Domain\JobLogs\Enums;

enum JobStatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESS    = 'success';
    case FAILED     = 'failed';
    case RETRIED    = 'retried';
    case DUPLICATED = 'duplicated';

    public function label(): string
    {
        return match($this) {
            self::PENDING    => 'Pendente',
            self::SUCCESS    => 'Sucesso',
            self::FAILED     => 'Falhou',
            self::RETRIED    => 'Reprocessado',
            self::DUPLICATED => 'Duplicado',
        };
    }
}

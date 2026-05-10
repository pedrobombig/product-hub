<?php

namespace App\Domain\JobLogs\Enums;

enum JobTypeEnum: string
{
    case UPDATE_DESCRIPTION = 'update_description';
    case UPDATE_IMAGES      = 'update_images';
    case UPDATE_PRICE       = 'update_price';
    case UPDATE_STOCK       = 'update_stock';
    case UPDATE_TAGS        = 'update_tags';

    public function label(): string
    {
        return match($this) {
            self::UPDATE_DESCRIPTION => 'Atualizar Descrição',
            self::UPDATE_IMAGES      => 'Atualizar Imagens',
            self::UPDATE_PRICE       => 'Atualizar Preço',
            self::UPDATE_STOCK       => 'Atualizar Estoque',
            self::UPDATE_TAGS        => 'Atualizar Tags',
        };
    }

    public static function getAllKeys(): array
    {
        return array_column(self::cases(), 'value');
    }
}

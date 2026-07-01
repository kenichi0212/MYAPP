<?php

namespace App\Enums;

enum ProcessType: string
{
    case Disposal = 'disposal';
    case Discount = 'discount';
    case Return   = 'return';
    case Other    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Disposal => '廃棄',
            self::Discount => '値引き',
            self::Return   => '返品',
            self::Other    => 'その他',
        };
    }
}

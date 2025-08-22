<?php

namespace App\Enums;

enum CustomerRole: string
{
    case BASIC = 'customer.basic';
    case PREMIUM = 'customer.premium';

    public function getPermissions(): array
    {
        return match ($this) {
            self::BASIC => ['customer:read', 'customer:update'],
            self::PREMIUM => ['customer:read', 'customer:update', 'customer:premium'],
        };
    }
}

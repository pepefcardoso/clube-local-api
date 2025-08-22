<?php

namespace App\Enums;

enum BusinessUserRole: string
{
    case EMPLOYEE = 'business.employee';
    case MANAGER = 'business.manager';

    public function getPermissions(): array
    {
        return match ($this) {
            self::EMPLOYEE => ['business:read', 'business:update'],
            self::MANAGER => ['business:read', 'business:update', 'business:manage'],
        };
    }
}

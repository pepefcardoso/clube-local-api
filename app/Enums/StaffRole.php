<?php

namespace App\Enums;

enum StaffRole: string
{
    case SUPPORT = 'staff.support';
    case ADMIN = 'staff.admin';

    public function getPermissions(): array
    {
        return match ($this) {
            self::SUPPORT => ['staff:read', 'staff:update'],
            self::ADMIN => ['staff:read', 'staff:update', 'staff:admin', 'system:manage'],
        };
    }
}

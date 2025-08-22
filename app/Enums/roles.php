<?php

namespace App\Enums;

enum UserType: string
{
    case CUSTOMER = 'customer';
    case BUSINESS_USER = 'business_user';
    case STAFF_USER = 'staff_user';

    public function getModelClass(): string
    {
        return match ($this) {
            self::CUSTOMER => \App\Models\Customer::class,
            self::BUSINESS_USER => \App\Models\BusinessUser::class,
            self::STAFF_USER => \App\Models\StaffUser::class,
        };
    }

    public function getDefaultRole(): string
    {
        return match ($this) {
            self::CUSTOMER => CustomerRole::BASIC->value,
            self::BUSINESS_USER => BusinessUserRole::EMPLOYEE->value,
            self::STAFF_USER => StaffRole::SUPPORT->value,
        };
    }
}

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

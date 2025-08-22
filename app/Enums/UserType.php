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

<?php

namespace App\Enums;

use App\Models\BusinessUserProfile;
use App\Models\CustomerProfile;
use App\Models\StaffUserProfile;

enum ProfileType: string
{
    case Customer = 'customer';
    case Business = 'business';
    case Staff = 'staff';

    public function getModelClass(): string
    {
        return match ($this) {
            self::Customer => CustomerProfile::class,
            self::Business => BusinessUserProfile::class,
            self::Staff => StaffUserProfile::class,
        };
    }
}

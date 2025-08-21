<?php

namespace App\Enums;

enum CustomerRole: string
{
    case BASIC = 'customer.basic';
    case PREMIUM = 'customer.premium';
}

enum BusinessUserRole: string
{
    case EMPLOYEE = 'company.employee';
    case MANAGER = 'company.manager';
}

enum StaffRole: string
{
    case SUPPORT = 'internal.support';
    case ADMIN = 'internal.admin';
}

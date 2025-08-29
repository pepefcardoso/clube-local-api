<?php

namespace App\Enums;

enum CustomerAccessLevel: string
{
    case Basic = 'basic';
    case Premium = 'premium';
    case VIP = 'vip';
}

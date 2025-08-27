<?php

namespace App\Enums;

enum StaffAccessLevel: string
{
    case Basic = 'basic';
    case Advanced = 'advanced';
    case Admin = 'admin';
}

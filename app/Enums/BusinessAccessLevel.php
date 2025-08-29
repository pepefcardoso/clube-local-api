<?php

namespace App\Enums;

enum BusinessAccessLevel: string
{
    case User = 'user';
    case Manager = 'manager';
    case Admin = 'admin';
}

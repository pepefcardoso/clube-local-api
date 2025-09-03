<?php

namespace App\Enums;

enum AddressType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Billing = 'billing';
    case Shipping = 'shipping';
}

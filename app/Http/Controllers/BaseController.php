<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseRoutingController;

class BaseController extends BaseRoutingController
{
    use AuthorizesRequests, ValidatesRequests;
}

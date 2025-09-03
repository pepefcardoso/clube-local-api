<?php

namespace App\Services\Address;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

class CreateAddress
{
    public function create(array $data): Address
    {
        return DB::transaction(function () use ($data) {
            $address = Address::create($data);
            return $address->load('addressable');
        });
    }
}

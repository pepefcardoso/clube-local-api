<?php

namespace App\Services\Address;

use App\Models\Address;
use Illuminate\Support\Facades\DB;


class UpdateAddress
{
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            $address->update($data);
            return $address->load('addressable');
        });
    }
}

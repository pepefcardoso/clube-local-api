<?php

namespace App\Services\Address;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

class CreateAddress
{
    public function create(array $data): Address
    {
        return DB::transaction(function () use ($data) {
            // If this address is being set as primary, remove primary flag from others
            if (isset($data['is_primary']) && $data['is_primary']) {
                // This will be implemented when relationships are added
                // For now, we'll just create the address
            }

            $address = Address::create($data);

            return $address;
        });
    }
}

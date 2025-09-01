<?php

namespace App\Services\Address;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

class UpdateAddress
{
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            // If this address is being set as primary, remove primary flag from others
            if (isset($data['is_primary']) && $data['is_primary'] && !$address->is_primary) {
                // This will be implemented when relationships are added
                // For now, we'll just update the address
            }

            $address->update($data);

            return $address;
        });
    }
}

<?php

namespace App\Services\Address;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

class DeleteAddress
{
    public function delete(Address $address): void
    {
        DB::transaction(function () use ($address) {
            $wasPrimary = $address->is_primary;

            $address->delete();

            // If the deleted address was primary, set another address as primary
            if ($wasPrimary) {
                // This will be implemented when relationships are added
                // We would find the next address for the same owner and set it as primary
            }
        });
    }
}

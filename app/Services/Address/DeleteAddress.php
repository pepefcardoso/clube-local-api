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
            $addressableId = $address->addressable_id;
            $addressableType = $address->addressable_type;

            $address->delete();

            if ($wasPrimary && $addressableId && $addressableType) {
                $nextAddress = Address::where('addressable_id', $addressableId)
                    ->where('addressable_type', $addressableType)
                    ->first();

                if ($nextAddress) {
                    $nextAddress->update(['is_primary' => true]);
                }
            }
        });
    }
}

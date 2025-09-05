<?php

namespace Tests\Integration\Services;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\Business;
use App\Models\CustomerProfile;
use App\Services\Address\CreateAddress;
use App\Services\Address\DeleteAddress;
use App\Services\Address\ListAddresses;
use App\Services\Address\UpdateAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddressServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreateAddress $createAddressService;
    protected UpdateAddress $updateAddressService;
    protected DeleteAddress $deleteAddressService;
    protected ListAddresses $listAddressesService;
    protected Business $business;
    protected CustomerProfile $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAddressService = new CreateAddress();
        $this->updateAddressService = new UpdateAddress();
        $this->deleteAddressService = new DeleteAddress();
        $this->listAddressesService = new ListAddresses();

        $this->business = Business::factory()->create();
        $this->customer = CustomerProfile::factory()->create();
    }

    public function test_create_address_service_creates_address_with_relationship(): void
    {
        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Service Test Street',
            'number' => '999',
            'neighborhood' => 'Service District',
            'city' => 'Service City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'type' => AddressType::Commercial,
        ];

        $address = $this->createAddressService->create($addressData);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('Service Test Street', $address->street);
        $this->assertEquals(AddressType::Commercial, $address->type);
        $this->assertTrue($address->relationLoaded('addressable'));
        $this->assertEquals($this->business->id, $address->addressable->id);

        $this->assertDatabaseHas('addresses', [
            'street' => 'Service Test Street',
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);
    }

    public function test_create_address_service_handles_primary_address_logic(): void
    {
        // Create first address as primary
        $firstAddressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'First Street',
            'number' => '111',
            'neighborhood' => 'First District',
            'city' => 'First City',
            'state' => 'SP',
            'zip_code' => '11111111',
            'type' => AddressType::Commercial,
            'is_primary' => true,
        ];

        $firstAddress = $this->createAddressService->create($firstAddressData);
        $this->assertTrue($firstAddress->is_primary);

        // Create second address as primary
        $secondAddressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Second Street',
            'number' => '222',
            'neighborhood' => 'Second District',
            'city' => 'Second City',
            'state' => 'RJ',
            'zip_code' => '22222222',
            'type' => AddressType::Residential,
            'is_primary' => true,
        ];

        $secondAddress = $this->createAddressService->create($secondAddressData);

        // First address should no longer be primary
        $this->assertFalse($firstAddress->fresh()->is_primary);
        $this->assertTrue($secondAddress->is_primary);
    }

    public function test_update_address_service_updates_address_with_relationship(): void
    {
        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $updateData = [
            'street' => 'Updated Street Name',
            'number' => '888',
            'city' => 'Updated City',
        ];

        $updatedAddress = $this->updateAddressService->update($address, $updateData);

        $this->assertEquals('Updated Street Name', $updatedAddress->street);
        $this->assertEquals('888', $updatedAddress->number);
        $this->assertEquals('Updated City', $updatedAddress->city);
        $this->assertTrue($updatedAddress->relationLoaded('addressable'));

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street' => 'Updated Street Name',
            'number' => '888',
            'city' => 'Updated City',
        ]);
    }

    public function test_delete_address_service_removes_address(): void
    {
        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $addressId = $address->id;

        $this->deleteAddressService->delete($address);

        $this->assertDatabaseMissing('addresses', [
            'id' => $addressId,
        ]);
    }

    public function test_delete_address_service_promotes_next_address_when_primary_is_deleted(): void
    {
        $primaryAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
            'type' => AddressType::Commercial,
        ]);

        $secondaryAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
            'type' => AddressType::Residential,
        ]);

        $this->deleteAddressService->delete($primaryAddress);

        $this->assertDatabaseMissing('addresses', [
            'id' => $primaryAddress->id,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $secondaryAddress->id,
            'is_primary' => true,
        ]);
    }

    public function test_delete_address_service_doesnt_affect_other_entities_addresses(): void
    {
        $businessAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        $customerAddress = Address::factory()->create([
            'addressable_id' => $this->customer->id,
            'addressable_type' => CustomerProfile::class,
            'is_primary' => true,
        ]);

        $this->deleteAddressService->delete($businessAddress);

        // Customer address should remain unchanged
        $this->assertDatabaseHas('addresses', [
            'id' => $customerAddress->id,
            'is_primary' => true,
        ]);
    }

    public function test_list_addresses_service_returns_paginated_results(): void
    {
        Address::factory()->count(25)->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $result = $this->listAddressesService->list(['per_page' => 10]);

        $this->assertEquals(10, $result->count());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(1, $result->currentPage());
    }

    public function test_list_addresses_service_filters_by_addressable(): void
    {
        // Create addresses for business
        Address::factory()->count(3)->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        // Create addresses for customer
        Address::factory()->count(2)->create([
            'addressable_id' => $this->customer->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        $filters = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ];

        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(3, $result->total());

        foreach ($result->items() as $address) {
            $this->assertEquals($this->business->id, $address->addressable_id);
            $this->assertEquals(Business::class, $address->addressable_type);
        }
    }

    public function test_list_addresses_service_filters_by_search(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Rua das Flores',
            'city' => 'São Paulo',
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Avenida Brasil',
            'city' => 'Rio de Janeiro',
        ]);

        $filters = ['search' => 'Flores'];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('Flores', $result->first()->street);
    }

    public function test_list_addresses_service_filters_by_type(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Commercial,
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Residential,
        ]);

        $filters = ['type' => AddressType::Commercial->value];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals(AddressType::Commercial, $result->first()->type);
    }

    public function test_list_addresses_service_filters_by_city(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'city' => 'São Paulo',
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'city' => 'Rio de Janeiro',
        ]);

        $filters = ['city' => 'São Paulo'];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('São Paulo', $result->first()->city);
    }

    public function test_list_addresses_service_filters_by_state(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'state' => 'SP',
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'state' => 'RJ',
        ]);

        $filters = ['state' => 'SP'];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('SP', $result->first()->state);
    }

    public function test_list_addresses_service_filters_by_primary_status(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
        ]);

        // Filter for primary addresses
        $filters = ['is_primary' => true];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertTrue($result->first()->is_primary);

        // Filter for non-primary addresses
        $filters = ['is_primary' => false];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertFalse($result->first()->is_primary);
    }

    public function test_list_addresses_service_filters_by_coordinates_presence(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'latitude' => null,
            'longitude' => null,
        ]);

        // Filter for addresses with coordinates
        $filters = ['has_coordinates' => true];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertNotNull($result->first()->latitude);
        $this->assertNotNull($result->first()->longitude);

        // Filter for addresses without coordinates
        $filters = ['has_coordinates' => false];
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $address = $result->first();
        $this->assertTrue(is_null($address->latitude) || is_null($address->longitude));
    }

    public function test_list_addresses_service_sorts_results(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'city' => 'Zacarias',
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'city' => 'Aracaju',
        ]);

        // Sort by city ascending
        $filters = [
            'sort_by' => 'city',
            'sort_direction' => 'asc',
        ];
        $result = $this->listAddressesService->list($filters);

        $addresses = $result->items();
        $this->assertEquals('Aracaju', $addresses[0]->city);
        $this->assertEquals('Zacarias', $addresses[1]->city);

        // Sort by city descending
        $filters['sort_direction'] = 'desc';
        $result = $this->listAddressesService->list($filters);

        $addresses = $result->items();
        $this->assertEquals('Zacarias', $addresses[0]->city);
        $this->assertEquals('Aracaju', $addresses[1]->city);
    }

    public function test_list_addresses_service_default_sorting(): void
    {
        $primaryAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        sleep(1); // Ensure different timestamps

        $secondaryAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
        ]);

        $result = $this->listAddressesService->list([]);

        $addresses = $result->items();
        // Primary address should come first, then by created_at desc
        $this->assertEquals($primaryAddress->id, $addresses[0]->id);
    }

    public function test_list_addresses_service_loads_relationships(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $result = $this->listAddressesService->list([]);

        $address = $result->first();
        $this->assertTrue($address->relationLoaded('addressable'));
        $this->assertInstanceOf(Business::class, $address->addressable);
    }

    public function test_list_addresses_service_filters_by_zip_code(): void
    {
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'zip_code' => '12345678',
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'zip_code' => '87654321',
        ]);

        $filters = ['zip_code' => '12345-678']; // Test with formatting
        $result = $this->listAddressesService->list($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('12345678', $result->first()->zip_code);
    }

    public function test_services_work_with_transactions(): void
    {
        $this->expectException(\Exception::class);

        DB::transaction(function () {
            $addressData = [
                'addressable_id' => $this->business->id,
                'addressable_type' => Business::class,
                'street' => 'Transaction Test Street',
                'number' => '123',
                'neighborhood' => 'Test',
                'city' => 'Test City',
                'state' => 'SP',
                'zip_code' => '12345678',
                'type' => AddressType::Commercial,
            ];

            $address = $this->createAddressService->create($addressData);

            // Simulate an error to test transaction rollback
            throw new \Exception('Test rollback');
        });

        // Address should not exist due to transaction rollback
        $this->assertDatabaseMissing('addresses', [
            'street' => 'Transaction Test Street',
        ]);
    }

    public function test_update_service_maintains_data_integrity(): void
    {
        $originalAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Original Street',
            'type' => AddressType::Commercial,
            'is_primary' => true,
        ]);

        $updateData = [
            'street' => 'Updated Street',
            // Not changing type or primary status
        ];

        $updatedAddress = $this->updateAddressService->update($originalAddress, $updateData);

        // Verify only specified fields were updated
        $this->assertEquals('Updated Street', $updatedAddress->street);
        $this->assertEquals(AddressType::Commercial, $updatedAddress->type);
        $this->assertTrue($updatedAddress->is_primary);
        $this->assertEquals($this->business->id, $updatedAddress->addressable_id);
    }

    public function test_create_service_handles_minimal_data(): void
    {
        $minimalData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Minimal Street',
            'number' => '1',
            'neighborhood' => 'Minimal',
            'city' => 'Minimal City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'type' => AddressType::Residential,
        ];

        $address = $this->createAddressService->create($minimalData);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('BR', $address->country); // Default value
        $this->assertFalse($address->is_primary); // Default value
        $this->assertNull($address->complement);
        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
    }

    public function test_create_service_handles_complete_data(): void
    {
        $completeData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Complete Street',
            'number' => '999',
            'complement' => 'Suite 100',
            'neighborhood' => 'Complete District',
            'city' => 'Complete City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'country' => 'US',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'is_primary' => true,
            'type' => AddressType::Commercial,
        ];

        $address = $this->createAddressService->create($completeData);

        $this->assertEquals('Complete Street', $address->street);
        $this->assertEquals('Suite 100', $address->complement);
        $this->assertEquals('US', $address->country);
        $this->assertEquals(40.7128, $address->latitude);
        $this->assertEquals(-74.0060, $address->longitude);
        $this->assertTrue($address->is_primary);
        $this->assertEquals(AddressType::Commercial, $address->type);
    }
}

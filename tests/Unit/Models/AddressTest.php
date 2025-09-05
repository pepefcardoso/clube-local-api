<?php

namespace Tests\Unit\Models;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\Business;
use App\Models\CustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_address_can_be_created_with_required_fields(): void
    {
        $business = Business::factory()->create();

        $address = Address::create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'street' => 'Rua das Flores',
            'number' => '123',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234567',
            'type' => AddressType::Commercial,
        ]);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('Rua das Flores', $address->street);
        $this->assertEquals('123', $address->number);
        $this->assertEquals('Centro', $address->neighborhood);
        $this->assertEquals('São Paulo', $address->city);
        $this->assertEquals('SP', $address->state);
        $this->assertEquals('01234567', $address->zip_code);
        $this->assertEquals(AddressType::Commercial, $address->type);
        $this->assertEquals('BR', $address->country); // Default value
        $this->assertFalse($address->is_primary); // Default value
    }

    public function test_address_casts_are_working_correctly(): void
    {
        $business = Business::factory()->create();

        $address = Address::create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'type' => 'commercial',
            'is_primary' => 1,
            'latitude' => '23.5505',
            'longitude' => '46.6333',
        ]);

        $this->assertInstanceOf(AddressType::class, $address->type);
        $this->assertEquals(AddressType::Commercial, $address->type);
        $this->assertIsBool($address->is_primary);
        $this->assertTrue($address->is_primary);
        $this->assertIsFloat($address->latitude);
        $this->assertIsFloat($address->longitude);
    }

    public function test_addressable_relationship(): void
    {
        $business = Business::factory()->create();

        $address = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
        ]);

        $this->assertInstanceOf(Business::class, $address->addressable);
        $this->assertEquals($business->id, $address->addressable->id);
    }

    public function test_full_address_attribute(): void
    {
        $address = Address::factory()->make([
            'street' => 'Rua das Flores',
            'number' => '123',
            'complement' => 'Apto 45',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234567',
            'country' => 'BR',
        ]);

        $expected = 'Rua das Flores, 123, Apto 45, Centro, São Paulo - SP, 01234567';
        $this->assertEquals($expected, $address->full_address);
    }

    public function test_full_address_attribute_without_complement(): void
    {
        $address = Address::factory()->make([
            'street' => 'Rua das Flores',
            'number' => '123',
            'complement' => null,
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234567',
            'country' => 'BR',
        ]);

        $expected = 'Rua das Flores, 123, Centro, São Paulo - SP, 01234567';
        $this->assertEquals($expected, $address->full_address);
    }

    public function test_full_address_attribute_with_foreign_country(): void
    {
        $address = Address::factory()->make([
            'street' => 'Main Street',
            'number' => '123',
            'neighborhood' => 'Downtown',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'country' => 'US',
        ]);

        $expected = 'Main Street, 123, Downtown, New York - NY, 10001, US';
        $this->assertEquals($expected, $address->full_address);
    }

    public function test_formatted_zip_code_attribute(): void
    {
        $address = Address::factory()->make(['zip_code' => '01234567']);
        $this->assertEquals('01234-567', $address->formatted_zip_code);

        $address = Address::factory()->make(['zip_code' => '12345']);
        $this->assertEquals('12345', $address->formatted_zip_code);
    }

    public function test_primary_scope(): void
    {
        $business = Business::factory()->create();

        Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
        ]);

        $primaryAddress = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        $result = Address::primary()->first();
        $this->assertEquals($primaryAddress->id, $result->id);
    }

    public function test_by_type_scope(): void
    {
        $business = Business::factory()->create();

        Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Residential,
        ]);

        $commercialAddress = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Commercial,
        ]);

        $result = Address::byType(AddressType::Commercial->value)->first();
        $this->assertEquals($commercialAddress->id, $result->id);
    }

    public function test_by_city_scope(): void
    {
        $business = Business::factory()->create();

        Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'city' => 'Rio de Janeiro',
        ]);

        $spAddress = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'city' => 'São Paulo',
        ]);

        $result = Address::byCity('São Paulo')->first();
        $this->assertEquals($spAddress->id, $result->id);

        // Test partial match
        $result = Address::byCity('São')->first();
        $this->assertEquals($spAddress->id, $result->id);
    }

    public function test_by_state_scope(): void
    {
        $business = Business::factory()->create();

        Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'state' => 'RJ',
        ]);

        $spAddress = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'state' => 'SP',
        ]);

        $result = Address::byState('SP')->first();
        $this->assertEquals($spAddress->id, $result->id);
    }

    public function test_by_zip_code_scope(): void
    {
        $business = Business::factory()->create();

        Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'zip_code' => '01234567',
        ]);

        $targetAddress = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'zip_code' => '12345678',
        ]);

        $result = Address::byZipCode('12345-678')->first();
        $this->assertEquals($targetAddress->id, $result->id);

        // Test without formatting
        $result = Address::byZipCode('12345678')->first();
        $this->assertEquals($targetAddress->id, $result->id);
    }

    public function test_for_entity_scope(): void
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        Address::factory()->create([
            'addressable_id' => $business2->id,
            'addressable_type' => Business::class,
        ]);

        $business1Address = Address::factory()->create([
            'addressable_id' => $business1->id,
            'addressable_type' => Business::class,
        ]);

        $result = Address::forEntity($business1)->first();
        $this->assertEquals($business1Address->id, $result->id);
    }

    public function test_address_type_check_methods(): void
    {
        $residentialAddress = Address::factory()->make(['type' => AddressType::Residential]);
        $commercialAddress = Address::factory()->make(['type' => AddressType::Commercial]);
        $billingAddress = Address::factory()->make(['type' => AddressType::Billing]);
        $shippingAddress = Address::factory()->make(['type' => AddressType::Shipping]);

        $this->assertTrue($residentialAddress->isResidential());
        $this->assertFalse($residentialAddress->isCommercial());
        $this->assertFalse($residentialAddress->isBilling());
        $this->assertFalse($residentialAddress->isShipping());

        $this->assertTrue($commercialAddress->isCommercial());
        $this->assertFalse($commercialAddress->isResidential());

        $this->assertTrue($billingAddress->isBilling());
        $this->assertFalse($billingAddress->isResidential());

        $this->assertTrue($shippingAddress->isShipping());
        $this->assertFalse($shippingAddress->isResidential());
    }

    public function test_has_coordinates_method(): void
    {
        $addressWithCoordinates = Address::factory()->make([
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ]);

        $addressWithoutCoordinates = Address::factory()->make([
            'latitude' => null,
            'longitude' => null,
        ]);

        $addressWithPartialCoordinates = Address::factory()->make([
            'latitude' => -23.5505,
            'longitude' => null,
        ]);

        $this->assertTrue($addressWithCoordinates->hasCoordinates());
        $this->assertFalse($addressWithoutCoordinates->hasCoordinates());
        $this->assertFalse($addressWithPartialCoordinates->hasCoordinates());
    }

    public function test_setting_primary_address_removes_other_primary_addresses(): void
    {
        $business = Business::factory()->create();

        $address1 = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        $this->assertTrue($address1->fresh()->is_primary);

        // Create second address as primary
        $address2 = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        // First address should no longer be primary
        $this->assertFalse($address1->fresh()->is_primary);
        $this->assertTrue($address2->fresh()->is_primary);
    }

    public function test_updating_address_to_primary_removes_other_primary_addresses(): void
    {
        $business = Business::factory()->create();

        $address1 = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        $address2 = Address::factory()->create([
            'addressable_id' => $business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
        ]);

        $this->assertTrue($address1->fresh()->is_primary);
        $this->assertFalse($address2->fresh()->is_primary);

        // Update second address to primary
        $address2->update(['is_primary' => true]);

        $this->assertFalse($address1->fresh()->is_primary);
        $this->assertTrue($address2->fresh()->is_primary);
    }

    public function test_primary_address_logic_only_affects_same_entity(): void
    {
        $business1 = Business::factory()->create();
        $business2 = Business::factory()->create();

        $business1Address = Address::factory()->create([
            'addressable_id' => $business1->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        $business2Address = Address::factory()->create([
            'addressable_id' => $business2->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        // Both should remain primary since they belong to different entities
        $this->assertTrue($business1Address->fresh()->is_primary);
        $this->assertTrue($business2Address->fresh()->is_primary);
    }

    public function test_address_can_belong_to_customer_profile(): void
    {
        $customer = CustomerProfile::factory()->create();

        $address = Address::factory()->create([
            'addressable_id' => $customer->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        $this->assertInstanceOf(CustomerProfile::class, $address->addressable);
        $this->assertEquals($customer->id, $address->addressable->id);
    }
}

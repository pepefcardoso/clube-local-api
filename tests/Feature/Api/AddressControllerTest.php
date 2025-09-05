<?php

namespace Tests\Feature\Api;

use App\Enums\AddressType;
use App\Enums\BusinessAccessLevel;
use App\Enums\BusinessStatus;
use App\Models\Address;
use App\Models\Business;
use App\Models\BusinessUserProfile;
use App\Models\CustomerProfile;
use App\Models\StaffUserProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $businessUser;
    protected User $customerUser;
    protected Business $business;
    protected CustomerProfile $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::factory()->create(['is_active' => true]);
        StaffUserProfile::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_level' => 'admin'
        ]);

        // Create business and business user
        $this->business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $this->businessUser = User::factory()->create(['is_active' => true]);
        BusinessUserProfile::factory()->create([
            'user_id' => $this->businessUser->id,
            'business_id' => $this->business->id,
            'access_level' => BusinessAccessLevel::Admin
        ]);

        // Create customer
        $this->customerUser = User::factory()->create(['is_active' => true]);
        $this->customer = CustomerProfile::factory()->create([
            'user_id' => $this->customerUser->id,
        ]);
    }

    public function test_unauthorized_user_cannot_access_addresses(): void
    {
        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $response = $this->getJson('/api/addresses');
        $response->assertUnauthorized();

        $response = $this->getJson("/api/addresses/{$address->id}");
        $response->assertUnauthorized();

        $response = $this->postJson('/api/addresses', []);
        $response->assertUnauthorized();

        $response = $this->patchJson("/api/addresses/{$address->id}", []);
        $response->assertUnauthorized();

        $response = $this->deleteJson("/api/addresses/{$address->id}");
        $response->assertUnauthorized();
    }

    public function test_inactive_user_cannot_access_addresses(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        StaffUserProfile::factory()->create([
            'user_id' => $inactiveUser->id,
            'access_level' => 'admin'
        ]);

        Sanctum::actingAs($inactiveUser);

        $response = $this->getJson('/api/addresses');
        $response->assertForbidden();
    }

    public function test_business_user_cannot_access_other_business_addresses(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherAddress = Address::factory()->create([
            'addressable_id' => $otherBusiness->id,
            'addressable_type' => Business::class,
        ]);

        Sanctum::actingAs($this->businessUser);

        $response = $this->getJson("/api/businesses/{$otherBusiness->id}/addresses");
        $response->assertForbidden();

        $response = $this->postJson("/api/businesses/{$otherBusiness->id}/addresses", [
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value,
        ]);
        $response->assertForbidden();
    }

    public function test_customer_cannot_access_other_customer_addresses(): void
    {
        $otherCustomer = CustomerProfile::factory()->create();
        $otherAddress = Address::factory()->create([
            'addressable_id' => $otherCustomer->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        Sanctum::actingAs($this->customerUser);

        $response = $this->getJson("/api/customers/{$otherCustomer->id}/addresses");
        $response->assertForbidden();
    }

    public function test_address_ordering_by_primary_and_type(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create addresses in specific order to test sorting
        $commercialAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Commercial,
            'is_primary' => false,
        ]);

        $primaryResidentialAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Residential,
            'is_primary' => true,
        ]);

        $shippingAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Shipping,
            'is_primary' => false,
        ]);

        $response = $this->getJson("/api/businesses/{$this->business->id}/addresses");

        $response->assertOk();

        $addresses = $response->json('data');

        // Primary address should be first
        $this->assertTrue($addresses[0]['is_primary']);
        $this->assertEquals($primaryResidentialAddress->id, $addresses[0]['id']);
    }

    public function test_address_coordinates_validation(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Test invalid latitude
        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value,
            'latitude' => 95, // Invalid latitude (> 90)
            'longitude' => -46.6333,
        ];

        $response = $this->postJson('/api/addresses', $addressData);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);

        // Test invalid longitude
        $addressData['latitude'] = -23.5505;
        $addressData['longitude'] = 185; // Invalid longitude (> 180)

        $response = $this->postJson('/api/addresses', $addressData);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);

        // Test valid coordinates
        $addressData['longitude'] = -46.6333;

        $response = $this->postJson('/api/addresses', $addressData);
        $response->assertCreated();
    }

    public function test_address_state_normalization(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'sp', // Lowercase
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertCreated();

        $this->assertDatabaseHas('addresses', [
            'state' => 'SP', // Should be uppercase
        ]);
    }

    public function test_zip_code_normalization(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345-678', // With dash
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertCreated();

        $this->assertDatabaseHas('addresses', [
            'zip_code' => '12345678', // Should be numbers only
        ]);
    }

    public function test_country_defaults_to_br(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'country' => 'BR',
                ]
            ]);
    }

    public function test_deleting_primary_address_promotes_another_address(): void
    {
        Sanctum::actingAs($this->adminUser);

        $primaryAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => true,
        ]);

        $secondaryAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
        ]);

        $response = $this->deleteJson("/api/addresses/{$primaryAddress->id}");

        $response->assertStatus(204);

        // The secondary address should now be primary
        $this->assertDatabaseHas('addresses', [
            'id' => $secondaryAddress->id,
            'is_primary' => true,
        ]);
    }

    public function test_pagination_works_correctly(): void
    {
        Sanctum::actingAs($this->adminUser);

        Address::factory()->count(25)->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $response = $this->getJson('/api/addresses?per_page=10&page=1');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);
    }

    public function test_filtering_by_coordinates(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressWithCoordinates = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ]);

        $addressWithoutCoordinates = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'latitude' => null,
            'longitude' => null,
        ]);

        // Filter addresses with coordinates
        $response = $this->getJson('/api/addresses?has_coordinates=true');
        $response->assertOk()->assertJsonCount(1, 'data');

        // Filter addresses without coordinates
        $response = $this->getJson('/api/addresses?has_coordinates=false');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_sorting_addresses(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressA = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'city' => 'Aracaju',
        ]);

        $addressZ = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'city' => 'Zacarias',
        ]);

        // Sort by city ascending
        $response = $this->getJson('/api/addresses?sort_by=city&sort_direction=asc');
        $response->assertOk();
        $addresses = $response->json('data');
        $this->assertEquals('Aracaju', $addresses[0]['city']);

        // Sort by city descending
        $response = $this->getJson('/api/addresses?sort_by=city&sort_direction=desc');
        $response->assertOk();
        $addresses = $response->json('data');
        $this->assertEquals('Zacarias', $addresses[0]['city']);
    }

    public function test_admin_can_list_addresses(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addresses = Address::factory()->count(3)->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $response = $this->getJson('/api/addresses');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'street',
                        'number',
                        'city',
                        'state',
                        'zip_code',
                        'type',
                        'is_primary',
                        'full_address',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_can_filter_addresses_by_type(): void
    {
        Sanctum::actingAs($this->adminUser);

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

        $response = $this->getJson('/api/addresses?type=commercial');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'type' => 'commercial'
                    ]
                ]
            ]);
    }

    public function test_can_filter_addresses_by_search(): void
    {
        Sanctum::actingAs($this->adminUser);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Rua das Flores',
        ]);

        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Avenida Brasil',
        ]);

        $response = $this->getJson('/api/addresses?search=Flores');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_address(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Rua das Flores',
            'number' => '123',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Endereço criado com sucesso',
                'data' => [
                    'street' => 'Rua das Flores',
                    'number' => '123',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                    'zip_code' => '01234567', // Should be cleaned
                    'type' => 'commercial'
                ]
            ]);

        $this->assertDatabaseHas('addresses', [
            'street' => 'Rua das Flores',
            'zip_code' => '01234567',
        ]);
    }

    public function test_create_address_validates_required_fields(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/addresses', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'addressable_id',
                'addressable_type',
                'street',
                'number',
                'neighborhood',
                'city',
                'state',
                'zip_code',
                'type'
            ]);
    }

    public function test_create_address_validates_zip_code_format(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '123', // Invalid format
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['zip_code']);
    }

    public function test_create_address_validates_state_format(): void
    {
        Sanctum::actingAs($this->adminUser);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test',
            'city' => 'Test City',
            'state' => 'SAO', // Should be 2 characters
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['state']);
    }

    public function test_create_address_prevents_duplicate_type_for_same_entity(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create first address
        Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Commercial,
        ]);

        $addressData = [
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'street' => 'Another Street',
            'number' => '456',
            'neighborhood' => 'Another Neighborhood',
            'city' => 'Another City',
            'state' => 'RJ',
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value, // Same type
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_admin_can_view_address(): void
    {
        Sanctum::actingAs($this->adminUser);

        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $response = $this->getJson("/api/addresses/{$address->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $address->id,
                'street' => $address->street,
            ]);
    }

    public function test_admin_can_update_address(): void
    {
        Sanctum::actingAs($this->adminUser);

        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $updateData = [
            'street' => 'Updated Street',
            'number' => '999',
        ];

        $response = $this->patchJson("/api/addresses/{$address->id}", $updateData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Endereço atualizado com sucesso',
                'data' => [
                    'street' => 'Updated Street',
                    'number' => '999',
                ]
            ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street' => 'Updated Street',
            'number' => '999',
        ]);
    }

    public function test_update_address_validates_unique_type_constraint(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create two addresses with different types
        $address1 = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Commercial,
        ]);

        $address2 = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'type' => AddressType::Residential,
        ]);

        // Try to update address2 to have the same type as address1
        $response = $this->patchJson("/api/addresses/{$address2->id}", [
            'type' => AddressType::Commercial->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_admin_can_delete_address(): void
    {
        Sanctum::actingAs($this->adminUser);

        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $response = $this->deleteJson("/api/addresses/{$address->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_can_set_address_as_primary(): void
    {
        Sanctum::actingAs($this->adminUser);

        $address = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
            'is_primary' => false,
        ]);

        $response = $this->patchJson("/api/addresses/{$address->id}/set-primary");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Endereço definido como principal',
                'data' => [
                    'is_primary' => true,
                ]
            ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'is_primary' => true,
        ]);
    }

    public function test_business_user_can_get_business_addresses(): void
    {
        Sanctum::actingAs($this->businessUser);

        $addresses = Address::factory()->count(2)->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        $response = $this->getJson("/api/businesses/{$this->business->id}/addresses");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_business_user_can_create_business_address(): void
    {
        Sanctum::actingAs($this->businessUser);

        $addressData = [
            'street' => 'Business Street',
            'number' => '100',
            'neighborhood' => 'Business District',
            'city' => 'Business City',
            'state' => 'SP',
            'zip_code' => '12345-678',
            'type' => AddressType::Commercial->value,
        ];

        $response = $this->postJson("/api/businesses/{$this->business->id}/addresses", $addressData);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Endereço da empresa criado com sucesso',
                'data' => [
                    'street' => 'Business Street',
                    'addressable_id' => $this->business->id,
                    'addressable_type' => Business::class,
                ]
            ]);
    }

    public function test_customer_can_get_their_addresses(): void
    {
        Sanctum::actingAs($this->customerUser);

        $addresses = Address::factory()->count(2)->create([
            'addressable_id' => $this->customer->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        $response = $this->getJson("/api/customers/{$this->customer->id}/addresses");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_create_their_address(): void
    {
        Sanctum::actingAs($this->customerUser);

        $addressData = [
            'street' => 'Customer Street',
            'number' => '200',
            'neighborhood' => 'Customer District',
            'city' => 'Customer City',
            'state' => 'RJ',
            'zip_code' => '87654-321',
            'type' => AddressType::Residential->value,
        ];

        $response = $this->postJson("/api/customers/{$this->customer->id}/addresses", $addressData);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Endereço do cliente criado com sucesso',
                'data' => [
                    'street' => 'Customer Street',
                    'addressable_id' => $this->customer->id,
                    'addressable_type' => CustomerProfile::class,
                ]
            ]);
    }

}

<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_can_register_customer()
    {
        $customerData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => $this->faker->phoneNumber,
            'birth_date' => $this->faker->date(),
            'address' => $this->faker->address,
        ];

        $response = $this->postJson('/api/v1/customers/register', $customerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'type', 'roles'],
                'token',
                'expires_at'
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => $customerData['email'],
            'name' => $customerData['name'],
        ]);
    }

    public function test_staff_can_list_customers_with_pagination()
    {
        $staff = StaffUser::factory()->create();
        $staff->assignRole('internal.admin');
        Sanctum::actingAs($staff, ['staff:read']);

        Customer::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/customers?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'customers' => [
                        '*' => ['id', 'name', 'email', 'subscription_type']
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to'
                ]
            ]);

        $this->assertEquals(2, $response->json('meta.current_page'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    public function test_can_filter_customers_by_search()
    {
        $staff = StaffUser::factory()->create();
        $staff->assignRole('internal.admin');
        Sanctum::actingAs($staff, ['staff:read']);

        $customer1 = Customer::factory()->create(['name' => 'John Doe']);
        $customer2 = Customer::factory()->create(['name' => 'Jane Smith']);
        $customer3 = Customer::factory()->create(['email' => 'john@example.com']);

        $response = $this->getJson('/api/v1/customers?q=john');

        $response->assertStatus(200);

        $customerNames = collect($response->json('data.customers'))->pluck('name');
        $this->assertTrue($customerNames->contains('John Doe'));
        $this->assertFalse($customerNames->contains('Jane Smith'));
    }

    public function test_can_sort_customers()
    {
        $staff = StaffUser::factory()->create();
        $staff->assignRole('internal.admin');
        Sanctum::actingAs($staff, ['staff:read']);

        Customer::factory()->create(['name' => 'Alice']);
        Customer::factory()->create(['name' => 'Bob']);
        Customer::factory()->create(['name' => 'Charlie']);

        $response = $this->getJson('/api/v1/customers?sort=name&order=asc');

        $response->assertStatus(200);

        $customerNames = collect($response->json('data.customers'))->pluck('name');
        $this->assertEquals('Alice', $customerNames->first());
        $this->assertEquals('Charlie', $customerNames->last());
    }

    public function test_can_filter_customers_by_subscription_type()
    {
        $staff = StaffUser::factory()->create();
        $staff->assignRole('internal.admin');
        Sanctum::actingAs($staff, ['staff:read']);

        Customer::factory()->create(['subscription_type' => 'basic']);
        Customer::factory()->create(['subscription_type' => 'premium']);
        Customer::factory()->create(['subscription_type' => 'basic']);

        $response = $this->getJson('/api/v1/customers?filter[subscription_type]=premium');

        $response->assertStatus(200);

        $customers = $response->json('data.customers');
        $this->assertCount(1, $customers);
        $this->assertEquals('premium', $customers[0]['subscription_type']);
    }

    public function test_customer_can_view_own_profile()
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['customer:read']);

        $response = $this->getJson("/api/v1/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ]);
    }

    public function test_customer_cannot_view_other_customer_profile()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        Sanctum::actingAs($customer1, ['customer:read']);

        $response = $this->getJson("/api/v1/customers/{$customer2->id}");

        $response->assertStatus(403);
    }

    public function test_customer_can_update_own_profile()
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['customer:update']);

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '+1234567890',
        ];

        $response = $this->putJson("/api/v1/customers/{$customer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Customer updated successfully']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'phone' => '+1234567890',
        ]);
    }

    public function test_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/v1/customers/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}

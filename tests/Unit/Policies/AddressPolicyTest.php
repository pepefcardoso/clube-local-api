<?php

namespace Tests\Unit\Policies;

use App\Enums\BusinessAccessLevel;
use App\Enums\BusinessStatus;
use App\Models\Address;
use App\Models\Business;
use App\Models\BusinessUserProfile;
use App\Models\CustomerProfile;
use App\Models\StaffUserProfile;
use App\Models\User;
use App\Policies\AddressPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected AddressPolicy $policy;
    protected User $adminStaff;
    protected User $advancedStaff;
    protected User $basicStaff;
    protected User $businessAdmin;
    protected User $businessManager;
    protected User $businessUser;
    protected User $customer;
    protected User $otherCustomer;
    protected Business $business;
    protected Business $otherBusiness;
    protected CustomerProfile $customerProfile;
    protected CustomerProfile $otherCustomerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AddressPolicy();

        // Create staff users
        $this->adminStaff = User::factory()->create(['is_active' => true]);
        StaffUserProfile::factory()->create([
            'user_id' => $this->adminStaff->id,
            'access_level' => 'admin'
        ]);

        $this->advancedStaff = User::factory()->create(['is_active' => true]);
        StaffUserProfile::factory()->create([
            'user_id' => $this->advancedStaff->id,
            'access_level' => 'advanced'
        ]);

        $this->basicStaff = User::factory()->create(['is_active' => true]);
        StaffUserProfile::factory()->create([
            'user_id' => $this->basicStaff->id,
            'access_level' => 'basic'
        ]);

        // Create businesses
        $this->business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $this->otherBusiness = Business::factory()->create(['status' => BusinessStatus::Active]);

        // Create business users
        $this->businessAdmin = User::factory()->create(['is_active' => true]);
        BusinessUserProfile::factory()->create([
            'user_id' => $this->businessAdmin->id,
            'business_id' => $this->business->id,
            'access_level' => BusinessAccessLevel::Admin
        ]);

        $this->businessManager = User::factory()->create(['is_active' => true]);
        BusinessUserProfile::factory()->create([
            'user_id' => $this->businessManager->id,
            'business_id' => $this->business->id,
            'access_level' => BusinessAccessLevel::Manager
        ]);

        $this->businessUser = User::factory()->create(['is_active' => true]);
        BusinessUserProfile::factory()->create([
            'user_id' => $this->businessUser->id,
            'business_id' => $this->business->id,
            'access_level' => BusinessAccessLevel::User
        ]);

        // Create customers
        $this->customer = User::factory()->create(['is_active' => true]);
        $this->customerProfile = CustomerProfile::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $this->otherCustomer = User::factory()->create(['is_active' => true]);
        $this->otherCustomerProfile = CustomerProfile::factory()->create([
            'user_id' => $this->otherCustomer->id,
        ]);
    }

    public function test_view_any_addresses_permissions(): void
    {
        // Admin staff can view any addresses
        $this->assertTrue($this->policy->viewAny($this->adminStaff));

        // Advanced staff can view any addresses
        $this->assertTrue($this->policy->viewAny($this->advancedStaff));

        // Basic staff cannot view any addresses
        $this->assertFalse($this->policy->viewAny($this->basicStaff));

        // Business admin can view addresses
        $this->assertTrue($this->policy->viewAny($this->businessAdmin));

        // Business manager can view addresses
        $this->assertTrue($this->policy->viewAny($this->businessManager));

        // Business user cannot view addresses
        $this->assertFalse($this->policy->viewAny($this->businessUser));

        // Customer can view addresses
        $this->assertTrue($this->policy->viewAny($this->customer));
    }

    public function test_view_business_address_permissions(): void
    {
        $businessAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        // Admin staff can view any business address
        $this->assertTrue($this->policy->view($this->adminStaff, $businessAddress));

        // Advanced staff can view any business address
        $this->assertTrue($this->policy->view($this->advancedStaff, $businessAddress));

        // Basic staff cannot view business addresses
        $this->assertFalse($this->policy->view($this->basicStaff, $businessAddress));

        // Business admin can view their business address
        $this->assertTrue($this->policy->view($this->businessAdmin, $businessAddress));

        // Business manager can view their business address
        $this->assertTrue($this->policy->view($this->businessManager, $businessAddress));

        // Business user cannot view business addresses
        $this->assertFalse($this->policy->view($this->businessUser, $businessAddress));

        // Customer cannot view business addresses
        $this->assertFalse($this->policy->view($this->customer, $businessAddress));
    }

    public function test_view_other_business_address_permissions(): void
    {
        $otherBusinessAddress = Address::factory()->create([
            'addressable_id' => $this->otherBusiness->id,
            'addressable_type' => Business::class,
        ]);

        // Business users cannot view other business addresses
        $this->assertFalse($this->policy->view($this->businessAdmin, $otherBusinessAddress));
        $this->assertFalse($this->policy->view($this->businessManager, $otherBusinessAddress));
        $this->assertFalse($this->policy->view($this->businessUser, $otherBusinessAddress));
    }

    public function test_view_customer_address_permissions(): void
    {
        $customerAddress = Address::factory()->create([
            'addressable_id' => $this->customerProfile->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        // Admin staff can view any customer address
        $this->assertTrue($this->policy->view($this->adminStaff, $customerAddress));

        // Advanced staff can view any customer address
        $this->assertTrue($this->policy->view($this->advancedStaff, $customerAddress));

        // Customer can view their own address
        $this->assertTrue($this->policy->view($this->customer, $customerAddress));

        // Other customer cannot view other customer's address
        $this->assertFalse($this->policy->view($this->otherCustomer, $customerAddress));

        // Business users cannot view customer addresses unless they have access to that customer
        $this->assertFalse($this->policy->view($this->businessAdmin, $customerAddress));
    }

    public function test_view_customer_address_with_business_relationship(): void
    {
        // Associate customer with business
        $this->customerProfile->businesses()->attach($this->business->id);

        $customerAddress = Address::factory()->create([
            'addressable_id' => $this->customerProfile->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        // Business admin can view customer address if customer is associated with business
        $this->assertTrue($this->policy->view($this->businessAdmin, $customerAddress));

        // Business manager can view customer address if customer is associated with business
        $this->assertTrue($this->policy->view($this->businessManager, $customerAddress));

        // Business user still cannot view customer addresses
        $this->assertFalse($this->policy->view($this->businessUser, $customerAddress));
    }

    public function test_create_address_permissions(): void
    {
        // Admin staff can create addresses
        $this->assertTrue($this->policy->create($this->adminStaff));

        // Advanced staff cannot create addresses
        $this->assertFalse($this->policy->create($this->advancedStaff));

        // Basic staff cannot create addresses
        $this->assertFalse($this->policy->create($this->basicStaff));

        // Business admin can create addresses
        $this->assertTrue($this->policy->create($this->businessAdmin));

        // Business manager can create addresses
        $this->assertTrue($this->policy->create($this->businessManager));

        // Business user cannot create addresses
        $this->assertFalse($this->policy->create($this->businessUser));

        // Customer can create addresses
        $this->assertTrue($this->policy->create($this->customer));
    }

    public function test_update_business_address_permissions(): void
    {
        $businessAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        // Admin staff can update any business address
        $this->assertTrue($this->policy->update($this->adminStaff, $businessAddress));

        // Advanced staff cannot update business addresses
        $this->assertFalse($this->policy->update($this->advancedStaff, $businessAddress));

        // Business admin can update their business address
        $this->assertTrue($this->policy->update($this->businessAdmin, $businessAddress));

        // Business manager can update their business address
        $this->assertTrue($this->policy->update($this->businessManager, $businessAddress));

        // Business user cannot update business addresses
        $this->assertFalse($this->policy->update($this->businessUser, $businessAddress));
    }

    public function test_update_customer_address_permissions(): void
    {
        $customerAddress = Address::factory()->create([
            'addressable_id' => $this->customerProfile->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        // Admin staff can update any customer address
        $this->assertTrue($this->policy->update($this->adminStaff, $customerAddress));

        // Customer can update their own address
        $this->assertTrue($this->policy->update($this->customer, $customerAddress));

        // Other customer cannot update other customer's address
        $this->assertFalse($this->policy->update($this->otherCustomer, $customerAddress));

        // Business users cannot update customer addresses without proper relationship
        $this->assertFalse($this->policy->update($this->businessAdmin, $customerAddress));
    }

    public function test_update_customer_address_with_business_relationship(): void
    {
        $this->customerProfile->businesses()->attach($this->business->id);

        $customerAddress = Address::factory()->create([
            'addressable_id' => $this->customerProfile->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        // Business admin can update customer address if customer is associated
        $this->assertTrue($this->policy->update($this->businessAdmin, $customerAddress));

        // Business manager can update customer address if customer is associated
        $this->assertTrue($this->policy->update($this->businessManager, $customerAddress));

        // Business user still cannot update customer addresses
        $this->assertFalse($this->policy->update($this->businessUser, $customerAddress));
    }

    public function test_delete_business_address_permissions(): void
    {
        $businessAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        // Admin staff can delete any business address
        $this->assertTrue($this->policy->delete($this->adminStaff, $businessAddress));

        // Advanced staff cannot delete business addresses
        $this->assertFalse($this->policy->delete($this->advancedStaff, $businessAddress));

        // Business admin can delete their business address
        $this->assertTrue($this->policy->delete($this->businessAdmin, $businessAddress));

        // Business manager can delete their business address
        $this->assertTrue($this->policy->delete($this->businessManager, $businessAddress));

        // Business user cannot delete business addresses
        $this->assertFalse($this->policy->delete($this->businessUser, $businessAddress));
    }

    public function test_delete_customer_address_permissions(): void
    {
        $customerAddress = Address::factory()->create([
            'addressable_id' => $this->customerProfile->id,
            'addressable_type' => CustomerProfile::class,
        ]);

        // Admin staff can delete any customer address
        $this->assertTrue($this->policy->delete($this->adminStaff, $customerAddress));

        // Customer can delete their own address
        $this->assertTrue($this->policy->delete($this->customer, $customerAddress));

        // Other customer cannot delete other customer's address
        $this->assertFalse($this->policy->delete($this->otherCustomer, $customerAddress));

        // Business users cannot delete customer addresses without proper relationship
        $this->assertFalse($this->policy->delete($this->businessAdmin, $customerAddress));
    }

    public function test_policy_handles_null_addressable(): void
    {
        $addressWithoutAddressable = new Address([
            'addressable_id' => 999999,
            'addressable_type' => Business::class,
            'street' => 'Test Street',
        ]);

        // Should return false when addressable doesn't exist
        $this->assertFalse($this->policy->view($this->businessAdmin, $addressWithoutAddressable));
        $this->assertFalse($this->policy->update($this->businessAdmin, $addressWithoutAddressable));
        $this->assertFalse($this->policy->delete($this->businessAdmin, $addressWithoutAddressable));
    }

    public function test_policy_with_different_access_levels(): void
    {
        $businessAddress = Address::factory()->create([
            'addressable_id' => $this->business->id,
            'addressable_type' => Business::class,
        ]);

        // Test business user access levels
        $this->assertTrue($this->policy->view($this->businessAdmin, $businessAddress));
        $this->assertTrue($this->policy->view($this->businessManager, $businessAddress));
        $this->assertFalse($this->policy->view($this->businessUser, $businessAddress));

        $this->assertTrue($this->policy->update($this->businessAdmin, $businessAddress));
        $this->assertTrue($this->policy->update($this->businessManager, $businessAddress));
        $this->assertFalse($this->policy->update($this->businessUser, $businessAddress));

        $this->assertTrue($this->policy->delete($this->businessAdmin, $businessAddress));
        $this->assertTrue($this->policy->delete($this->businessManager, $businessAddress));
        $this->assertFalse($this->policy->delete($this->businessUser, $businessAddress));
    }
}

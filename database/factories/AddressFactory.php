<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\Business;
use App\Models\CustomerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'street' => $this->faker->streetName(),
            'number' => $this->faker->buildingNumber(),
            'complement' => $this->faker->optional(0.3)->secondaryAddress(),
            'neighborhood' => $this->faker->citySuffix(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip_code' => $this->faker->numerify('########'),
            'country' => 'BR',
            'latitude' => $this->faker->optional(0.7)->latitude(-33.5, -1),
            'longitude' => $this->faker->optional(0.7)->longitude(-73, -34),
            'is_primary' => false,
            'type' => $this->faker->randomElement(AddressType::cases()),
            'addressable_id' => Business::factory(),
            'addressable_type' => Business::class,
        ];
    }

    /**
     * Indicate that the address is primary.
     */
    public function primary(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the address is residential.
     */
    public function residential(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => AddressType::Residential,
        ]);
    }

    /**
     * Indicate that the address is commercial.
     */
    public function commercial(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => AddressType::Commercial,
        ]);
    }

    /**
     * Indicate that the address is for billing.
     */
    public function billing(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => AddressType::Billing,
        ]);
    }

    /**
     * Indicate that the address is for shipping.
     */
    public function shipping(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => AddressType::Shipping,
        ]);
    }

    /**
     * Indicate that the address belongs to a business.
     */
    public function forBusiness(Business $business = null): static
    {
        return $this->state(fn(array $attributes) => [
            'addressable_id' => $business?->id ?? Business::factory(),
            'addressable_type' => Business::class,
        ]);
    }

    /**
     * Indicate that the address belongs to a customer.
     */
    public function forCustomer(CustomerProfile $customer = null): static
    {
        return $this->state(fn(array $attributes) => [
            'addressable_id' => $customer?->id ?? CustomerProfile::factory(),
            'addressable_type' => CustomerProfile::class,
        ]);
    }

    /**
     * Indicate that the address has coordinates.
     */
    public function withCoordinates(float $latitude = null, float $longitude = null): static
    {
        return $this->state(fn(array $attributes) => [
            'latitude' => $latitude ?? $this->faker->latitude(-33.5, -1),
            'longitude' => $longitude ?? $this->faker->longitude(-73, -34),
        ]);
    }

    /**
     * Indicate that the address has no coordinates.
     */
    public function withoutCoordinates(): static
    {
        return $this->state(fn(array $attributes) => [
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    /**
     * Create an address in São Paulo state.
     */
    public function inSaoPaulo(): static
    {
        return $this->state(fn(array $attributes) => [
            'city' => $this->faker->randomElement([
                'São Paulo',
                'Campinas',
                'Santos',
                'São Bernardo do Campo',
                'Santo André',
                'Osasco',
                'Ribeirão Preto',
                'Sorocaba'
            ]),
            'state' => 'SP',
            'zip_code' => $this->faker->numerify('0####-###'),
        ]);
    }

    /**
     * Create an address in Rio de Janeiro state.
     */
    public function inRioDeJaneiro(): static
    {
        return $this->state(fn(array $attributes) => [
            'city' => $this->faker->randomElement([
                'Rio de Janeiro',
                'Niterói',
                'Nova Iguaçu',
                'Duque de Caxias',
                'Campos dos Goytacazes',
                'Petrópolis',
                'Volta Redonda'
            ]),
            'state' => 'RJ',
            'zip_code' => $this->faker->numerify('2####-###'),
        ]);
    }

    /**
     * Create a complete address with all optional fields.
     */
    public function complete(): static
    {
        return $this->state(fn(array $attributes) => [
            'complement' => $this->faker->secondaryAddress(),
            'latitude' => $this->faker->latitude(-33.5, -1),
            'longitude' => $this->faker->longitude(-73, -34),
        ]);
    }

    /**
     * Create a minimal address with only required fields.
     */
    public function minimal(): static
    {
        return $this->state(fn(array $attributes) => [
            'complement' => null,
            'latitude' => null,
            'longitude' => null,
        ]);
    }
}

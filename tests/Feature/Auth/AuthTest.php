<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\BusinessUser;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure stateless authentication for consistent testing
        config(['sanctum.stateful' => []]);
    }

    /**
     * Dataset para os tipos de utilizador
     */
    public static function userTypesProvider(): array
    {
        return [
            'customer' => [UserType::CUSTOMER],
            'business_user' => [UserType::BUSINESS_USER],
            'staff_user' => [UserType::STAFF_USER],
        ];
    }

    //======================================================================
    // TESTES DE REGISTO
    //======================================================================

    #[DataProvider('userTypesProvider')]
    public function test_a_user_can_register_successfully(UserType $userType): void
    {
        // Arrange
        $password = 'Password123';
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password,
            'password_confirmation' => $password,
            'user_type' => $userType->value,
        ];

        // Act
        $response = $this->postJson(route('api.v1.auth.register'), $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'type'],
                    'token',
                    'expires_at',
                    'abilities',
                ],
            ])
            ->assertJsonPath('data.user.email', $userData['email'])
            ->assertJsonPath('data.user.type', $userType->value);

        $modelClass = $userType->getModelClass();
        $tableName = (new $modelClass)->getTable();

        $this->assertDatabaseHas($tableName, [
            'email' => $userData['email'],
            'name' => $userData['name'],
        ]);
    }

    public function test_registration_fails_with_validation_errors(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_type', 'name', 'email', 'password']);
    }

    //======================================================================
    // TESTES DE LOGIN
    //======================================================================

    #[DataProvider('userTypesProvider')]
    public function test_a_user_can_log_in_successfully(UserType $userType): void
    {
        // Arrange
        $modelClass = $userType->getModelClass();
        $user = $modelClass::factory()->withPassword('Password123')->create();

        $userTypeMapping = [
            'customer' => 'customer',
            'business_user' => 'business_user',
            'staff_user' => 'staff_user',
        ];

        $loginUserType = $userTypeMapping[$userType->value];

        // Act
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => $loginUserType,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token', 'expires_at', 'abilities'],
            ]);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        $user = Customer::factory()->withPassword('Password123')->create();

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'WrongPassword',
            'user_type' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_inactive_users(): void
    {
        $user = BusinessUser::factory()->inactive()->withPassword('Password123')->create();

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'business_user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        $user = Customer::factory()->create();

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.auth.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
                'user_type' => 'customer',
            ])->assertStatus(422);
        }

        // 6th attempt should be rate limited
        $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
            'user_type' => 'customer',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Too many login attempts. Please try again later.');
    }

    //======================================================================
    // TESTES DE ENDPOINTS AUTENTICADOS
    //======================================================================

    #[DataProvider('userTypesProvider')]
    public function test_an_authenticated_user_can_get_their_profile(UserType $userType): void
    {
        $modelClass = $userType->getModelClass();
        $user = $modelClass::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(route('api.v1.auth.me'));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_an_authenticated_user_can_logout(): void
    {
        $user = Customer::factory()->withPassword('Password123')->create();

        $loginResponse = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);

        $token = $loginResponse->json('data.token');

        // Verify token works
        $this->withToken($token)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(200);

        // Logout
        $this->withToken($token)
            ->postJson(route('api.v1.auth.logout'))
            ->assertStatus(200);

        // Token should no longer work
        $this->withToken($token)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401);

        // Verify token was removed from database
        $tokenId = explode('|', $token)[0];
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId
        ]);
    }

    public function test_an_authenticated_user_can_refresh_their_token(): void
    {
        $user = Customer::factory()->withPassword('Password123')->create();

        $loginResponse = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);

        $oldToken = $loginResponse->json('data.token');

        // Refresh token
        $response = $this->withToken($oldToken)
            ->postJson(route('api.v1.auth.refresh'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);

        $newToken = $response->json('data.token');
        $this->assertNotEquals($newToken, $oldToken);

        // New token should work
        $this->withToken($newToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(200);

        // Old token should not work
        $this->withToken($oldToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401);

        // Verify old token is gone from database
        $oldTokenId = explode('|', $oldToken)[0];
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $oldTokenId
        ]);
    }

    public function test_an_authenticated_user_can_logout_from_all_devices(): void
    {
        $user = Customer::factory()->withPassword('Password123')->create();

        // Create two tokens
        $login1Response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);
        $token1 = $login1Response->json('data.token');

        $login2Response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);
        $token2 = $login2Response->json('data.token');

        // Verify both tokens work
        $this->withToken($token1)->getJson(route('api.v1.auth.me'))->assertStatus(200);
        $this->withToken($token2)->getJson(route('api.v1.auth.me'))->assertStatus(200);

        // Logout all
        $this->withToken($token1)
            ->postJson(route('api.v1.auth.logout-all'))
            ->assertStatus(200);

        // Both tokens should be invalid
        $this->withToken($token1)->getJson(route('api.v1.auth.me'))->assertStatus(401);
        $this->withToken($token2)->getJson(route('api.v1.auth.me'))->assertStatus(401);

        // Verify all tokens removed from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user)
        ]);
    }
}

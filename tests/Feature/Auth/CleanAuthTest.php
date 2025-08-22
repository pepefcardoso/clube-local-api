<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\BusinessUser;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CleanAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Force stateless authentication for tests
        config([
            'sanctum.stateful' => [],
            'session.driver' => 'array',
        ]);

        // Clear any existing auth state
        auth()->logout();
        auth('sanctum')->logout();
    }

    protected function tearDown(): void
    {
        // Clear auth state after each test
        auth()->logout();
        auth('sanctum')->logout();

        parent::tearDown();
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
        // Arrange: Prepara os dados do utilizador a ser registado.
        $password = 'Password123';
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password,
            'password_confirmation' => $password,
            'user_type' => $userType->value,
        ];

        // Act: Faz uma requisição POST para a rota de registo.
        $response = $this->postJson(route('api.v1.auth.register'), $userData);

        // Assert: Verifica se a resposta e o estado da base de dados estão corretos.
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

        // Verifica se o utilizador foi criado na tabela correta.
        $modelClass = $userType->getModelClass();
        $tableName = (new $modelClass)->getTable();

        $this->assertDatabaseHas($tableName, [
            'email' => $userData['email'],
            'name' => $userData['name'],
        ]);
    }

    public function test_registration_fails_with_validation_errors(): void
    {
        // Act: Tenta registar com dados em falta.
        $response = $this->postJson(route('api.v1.auth.register'), [
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        // Assert: Verifica se a API retorna os erros de validação esperados.
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_type', 'name', 'email', 'password']);
    }

    //======================================================================
    // TESTES DE LOGIN
    //======================================================================

    #[DataProvider('userTypesProvider')]
    public function test_a_user_can_log_in_successfully(UserType $userType): void
    {
        // Arrange: Cria um utilizador do tipo especificado.
        $modelClass = $userType->getModelClass();
        $user = $modelClass::factory()->withPassword('Password123')->create();

        // Mapear os tipos de utilizador para os valores esperados pelo login
        $userTypeMapping = [
            'customer' => 'customer',
            'business_user' => 'business',
            'staff_user' => 'staff',
        ];

        $loginUserType = $userTypeMapping[$userType->value];

        // Act: Tenta fazer login com as credenciais corretas.
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => $loginUserType,
        ]);

        // Assert: Verifica se o login foi bem-sucedido e retornou um token.
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'token', 'expires_at', 'abilities'],
            ]);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        // Arrange: Cria um utilizador Customer.
        $user = Customer::factory()->withPassword('Password123')->create();

        // Act: Tenta fazer login com a password errada.
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'WrongPassword',
            'user_type' => 'customer',
        ]);

        // Assert: Verifica a resposta de erro de validação.
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_inactive_users(): void
    {
        // Arrange: Cria um BusinessUser inativo.
        $user = BusinessUser::factory()->inactive()->withPassword('Password123')->create();

        // Act: Tenta fazer login.
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'business',
        ]);

        // Assert: Verifica a mensagem de erro para contas desativadas.
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        // Arrange: Cria um utilizador para o teste.
        $user = Customer::factory()->create();

        // Act: Simula 5 tentativas de login falhadas.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.auth.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
                'user_type' => 'customer',
            ])->assertStatus(422);
        }

        // Act & Assert: A 6ª tentativa deve ser bloqueada com uma mensagem de "Too many attempts".
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
        // Arrange: Cria e autentica um utilizador.
        $modelClass = $userType->getModelClass();
        $user = $modelClass::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Act: Acede à rota 'me'.
        $response = $this->getJson(route('api.v1.auth.me'));

        // Assert: Verifica se os dados do utilizador correto são retornados.
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_an_authenticated_user_can_logout(): void
    {
        // Create a fresh application instance for this test
        $this->refreshApplication();

        $user = Customer::factory()->withPassword('Password123')->create();

        // Make login request
        $loginResponse = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);

        $token = $loginResponse->json('data.token');

        // Verify token works initially
        $this->withToken($token)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(200);

        // Make logout request
        $this->withToken($token)
            ->postJson(route('api.v1.auth.logout'))
            ->assertStatus(200);

        // Create a new application instance to ensure clean state
        $this->refreshApplication();

        // Verify token no longer works
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
        $this->refreshApplication();

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

        $response->assertStatus(200);
        $newToken = $response->json('data.token');

        $this->assertNotEquals($newToken, $oldToken);

        // Refresh application to ensure clean state
        $this->refreshApplication();

        // New token should work
        $this->withToken($newToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(200);

        // Old token should not work
        $this->withToken($oldToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401);
    }

    public function test_an_authenticated_user_can_logout_from_all_devices(): void
    {
        $this->refreshApplication();

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

        // Refresh application
        $this->refreshApplication();

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

<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\BusinessUser;
use App\Models\Customer;
use App\Models\StaffUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
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

    /**
     * @dataProvider userTypesProvider
     */
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

    /**
     * @dataProvider userTypesProvider
     */
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

    /**
     * @dataProvider userTypesProvider
     */
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
        // Arrange: Cria um utilizador e obtém um token de acesso.
        $user = Customer::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act: Faz logout usando o token.
        $this->withToken($token)
            ->postJson(route('api.v1.auth.logout'))
            ->assertStatus(200);

        // Assert: O token não deve mais ser válido.
        $this->withToken($token)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401); // Não autorizado
    }

    public function test_an_authenticated_user_can_refresh_their_token(): void
    {
        // Arrange: Cria um utilizador e obtém um token.
        $user = Customer::factory()->create();
        $oldToken = $user->createToken('test-token')->plainTextToken;

        // Act: Faz a requisição para a rota de refresh.
        $response = $this->withToken($oldToken)
            ->postJson(route('api.v1.auth.refresh'));

        // Assert: Verifica se um novo token foi retornado.
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
        $newToken = $response->json('data.token');
        $this->assertNotEquals($newToken, $oldToken);

        // O novo token deve ser válido.
        $this->withToken($newToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(200);

        // O token antigo deve ter sido invalidado.
        $this->withToken($oldToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401);
    }

    public function test_an_authenticated_user_can_logout_from_all_devices(): void
    {
        // Arrange: Cria um utilizador e dois tokens (simulando dois logins).
        $user = Customer::factory()->create();
        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;

        // Act: Usa um dos tokens para fazer logout de todos os dispositivos.
        $this->withToken($token1)
            ->postJson(route('api.v1.auth.logout-all'))
            ->assertStatus(200);

        // Assert: Nenhum dos tokens deve ser mais válido.
        $this->withToken($token1)->getJson(route('api.v1.auth.me'))->assertStatus(401);
        $this->withToken($token2)->getJson(route('api.v1.auth.me'))->assertStatus(401);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\BusinessUser;
use App\Models\Customer;
use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, WithFaker::class);

/**
 * Define o nosso dataset numa variável para ser reutilizada e para
 * satisfazer o analisador de código do editor.
 */
$userTypesDataset = [
    'customer' => [UserType::CUSTOMER],
    'business_user' => [UserType::BUSINESS_USER],
    'staff_user' => [UserType::STAFF_USER],
];

/**
 * Opcional: Ainda podemos registar como um dataset partilhado se quisermos usá-lo
 * noutros ficheiros de teste com ->with('user_types').
 */
dataset('user_types', $userTypesDataset);

//======================================================================
// TESTES DE REGISTO
//======================================================================

test('a user can register successfully', function (UserType $userType) {
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
    $this->assertDatabaseHas($userType->getModelClass(), [
        'email' => $userData['email'],
    ]);
})->with($userTypesDataset); // <-- ALTERAÇÃO AQUI

test('registration fails with validation errors', function () {
    // Act: Tenta registar com dados em falta.
    $response = $this->postJson(route('api.v1.auth.register'), [
        'email' => 'not-an-email',
        'password' => 'short',
    ]);

    // Assert: Verifica se a API retorna os erros de validação esperados.
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_type', 'name', 'email', 'password']);
});


//======================================================================
// TESTES DE LOGIN
//======================================================================

test('a user can log in successfully', function (UserType $userType) {
    // Arrange: Cria um utilizador do tipo especificado.
    $modelClass = $userType->getModelClass();
    $user = $modelClass::factory()->create([
        'password' => 'Password123',
    ]);

    // Act: Tenta fazer login com as credenciais corretas.
    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'Password123',
        'user_type' => $userType->value,
    ]);

    // Assert: Verifica se o login foi bem-sucedido e retornou um token.
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['user', 'token', 'expires_at', 'abilities'],
        ]);
})->with($userTypesDataset); // <-- ALTERAÇÃO AQUI

test('login fails with incorrect password', function () {
    // Arrange: Cria um utilizador Customer.
    $user = Customer::factory()->create([
        'password' => 'Password123',
    ]);

    // Act: Tenta fazer login com a password errada.
    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'WrongPassword',
        'user_type' => 'customer',
    ]);

    // Assert: Verifica a resposta de erro de validação.
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login fails for inactive users', function () {
    // Arrange: Cria um BusinessUser inativo.
    $user = BusinessUser::factory()->create([
        'is_active' => false,
        'password' => 'Password123',
    ]);

    // Act: Tenta fazer login.
    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => $user->email,
        'password' => 'Password123',
        'user_type' => 'business_user',
    ]);

    // Assert: Verifica a mensagem de erro para contas desativadas.
    $response->assertStatus(422)
        ->assertJsonValidationErrorFor('email', 'errors')
        ->assertJsonPath('errors.email.0', 'Your account has been deactivated.');
});

test('login is rate limited after too many failed attempts', function () {
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
});


//======================================================================
// TESTES DE ENDPOINTS AUTENTICADOS
//======================================================================

test('an authenticated user can get their profile', function (UserType $userType) {
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
})->with($userTypesDataset); // <-- ALTERAÇÃO AQUI


test('an authenticated user can logout', function () {
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
});

test('an authenticated user can refresh their token', function () {
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
    expect($newToken)->not->toBe($oldToken);

    // O novo token deve ser válido.
    $this->withToken($newToken)
        ->getJson(route('api.v1.auth.me'))
        ->assertStatus(200);

    // O token antigo deve ter sido invalidado.
    $this->withToken($oldToken)
        ->getJson(route('api.v1.auth.me'))
        ->assertStatus(401);
});

test('an authenticated user can logout from all devices', function () {
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
});

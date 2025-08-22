<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DetailedDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_detailed_sanctum_debug(): void
    {
        // Check Sanctum configuration
        dump("Sanctum stateful domains: " . json_encode(config('sanctum.stateful')));
        dump("Sanctum guard: " . json_encode(config('sanctum.guard')));

        // Create user and login
        $user = Customer::factory()->withPassword('Password123')->create();

        $loginResponse = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);

        $token = $loginResponse->json('data.token');
        dump("Full token: " . $token);

        // Parse token
        $tokenParts = explode('|', $token);
        $tokenId = $tokenParts[0];
        $plainToken = $tokenParts[1];
        $hashedToken = hash('sha256', $plainToken);

        dump("Token ID: " . $tokenId);
        dump("Plain token part: " . $plainToken);
        dump("Hashed token: " . $hashedToken);

        // Check token in database
        $dbToken = PersonalAccessToken::find($tokenId);
        dump("Token found in DB: " . ($dbToken ? 'Yes' : 'No'));
        if ($dbToken) {
            dump("DB Token hash matches: " . ($dbToken->token === $hashedToken ? 'Yes' : 'No'));
            dump("DB Token user ID: " . $dbToken->tokenable_id);
            dump("DB Token user type: " . $dbToken->tokenable_type);
            dump("DB Token abilities: " . json_encode($dbToken->abilities));
        }

        // Test authentication before logout
        $beforeResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson(route('api.v1.auth.me'));

        dump("Status before logout: " . $beforeResponse->getStatusCode());
        dump("User from /me before: " . $beforeResponse->json('data.id'));

        // Check what user Sanctum finds
        $request = request();
        $request->headers->set('Authorization', 'Bearer ' . $token);

        // Manually test Sanctum resolution
        try {
            $sanctumUser = auth('sanctum')->user();
            dump("Sanctum user found: " . ($sanctumUser ? $sanctumUser->id : 'None'));
        } catch (\Exception $e) {
            dump("Sanctum error: " . $e->getMessage());
        }

        // Now logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson(route('api.v1.auth.logout'));

        dump("Logout response status: " . $logoutResponse->getStatusCode());

        // Check token in database after logout
        $tokensAfterLogout = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        dump("Tokens in DB after logout: " . $tokensAfterLogout);

        // Try to use token again - this is where the problem occurs
        $afterResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson(route('api.v1.auth.me'));

        dump("Status after logout: " . $afterResponse->getStatusCode());

        // Let's try a different route to see if it's specific to /me
        $testResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/customers');

        dump("Status on /customers after logout: " . $testResponse->getStatusCode());

        // Try with a completely fresh token to see if that works
        $freshToken = $user->createToken('fresh-token')->plainTextToken;
        $freshResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $freshToken,
            'Accept' => 'application/json',
        ])->getJson(route('api.v1.auth.me'));

        dump("Fresh token works: " . ($freshResponse->getStatusCode() === 200 ? 'Yes' : 'No'));

        // Check if there are any remaining tokens
        $allTokens = PersonalAccessToken::all();
        dump("Total tokens in system: " . $allTokens->count());
        foreach ($allTokens as $dbToken) {
            dump("Token {$dbToken->id}: User {$dbToken->tokenable_id}, Name: {$dbToken->name}");
        }

        $this->assertEquals(401, $afterResponse->getStatusCode());
    }
}

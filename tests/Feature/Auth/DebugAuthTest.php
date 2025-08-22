<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DebugAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_token_invalidation(): void
    {
        // Create user and login
        $user = Customer::factory()->withPassword('Password123')->create();

        $loginResponse = $this->postJson(route('api.v1.auth.login'), [
            'email' => $user->email,
            'password' => 'Password123',
            'user_type' => 'customer',
        ]);

        $token = $loginResponse->json('data.token');

        // Debug: Check token structure
        dump("Token: " . $token);

        // Check how many tokens exist in database before logout
        $tokensBeforeLogout = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        dump("Tokens before logout: " . $tokensBeforeLogout);

        // Verify token works
        $meResponse = $this->withToken($token)->getJson(route('api.v1.auth.me'));
        dump("Me response status before logout: " . $meResponse->getStatusCode());

        // Get the actual token model to debug
        $tokenParts = explode('|', $token);
        if (count($tokenParts) >= 2) {
            $tokenId = $tokenParts[0];
            $tokenModel = PersonalAccessToken::find($tokenId);
            dump("Token model found: " . ($tokenModel ? 'Yes' : 'No'));
            if ($tokenModel) {
                dump("Token model ID: " . $tokenModel->id);
                dump("Token name: " . $tokenModel->name);
            }
        }

        // Try logout
        $logoutResponse = $this->withToken($token)->postJson(route('api.v1.auth.logout'));
        dump("Logout response status: " . $logoutResponse->getStatusCode());

        // Check tokens after logout
        $tokensAfterLogout = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        dump("Tokens after logout: " . $tokensAfterLogout);

        // Try to use token again
        $meResponseAfterLogout = $this->withToken($token)->getJson(route('api.v1.auth.me'));
        dump("Me response status after logout: " . $meResponseAfterLogout->getStatusCode());

        // This should fail, but let's see what happens
        $this->assertEquals(401, $meResponseAfterLogout->getStatusCode());
    }
}

<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\AuthenticateToken;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    private function issueToken(User $user, array $attrs = []): string
    {
        $plain = Str::random(60);
        ApiToken::create($attrs + [
            'user_id' => $user->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        return $plain;
    }

    public function test_un_token_recent_reste_valide(): void
    {
        $token = $this->issueToken(User::factory()->create());

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();
    }

    public function test_un_token_inactif_depuis_trop_longtemps_est_supprime(): void
    {
        $token = $this->issueToken(User::factory()->create(), [
            'last_used_at' => now()->subDays(AuthenticateToken::IDLE_DAYS + 1),
        ]);

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
        $this->assertSame(0, ApiToken::count());
    }

    public function test_l_utilisation_rafraichit_la_date_d_activite(): void
    {
        $user = User::factory()->create();
        $token = $this->issueToken($user, [
            'last_used_at' => now()->subDays(AuthenticateToken::IDLE_DAYS - 1),
        ]);

        $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $this->assertTrue(ApiToken::first()->last_used_at->gt(now()->subMinute()));
    }
}

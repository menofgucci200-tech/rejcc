<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_login_est_limite_a_5_tentatives_par_minute(): void
    {
        $credentials = ['email' => 'inconnu@example.com', 'password' => 'mauvais'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', $credentials)->assertStatus(401);
        }

        $this->postJson('/api/auth/login', $credentials)->assertStatus(429);
    }

    public function test_les_formulaires_publics_sont_limites_a_10_envois_par_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/newsletter', ['email' => "abonne{$i}@example.com"])->assertOk();
        }

        $this->postJson('/api/newsletter', ['email' => 'onzieme@example.com'])->assertStatus(429);
    }
}

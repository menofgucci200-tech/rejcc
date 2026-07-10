<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $overrides = [])
    {
        return $this->postJson('/api/auth/register', $overrides + [
            'prenom' => 'Jean',
            'nom' => 'Kouassi',
            'email' => 'jean@example.com',
            'telephone' => '0102030405',
            'password' => 'motdepasse123',
        ]);
    }

    public function test_register_cree_un_membre_et_delivre_un_token(): void
    {
        $response = $this->register();

        $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('user.role', 'member');

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Le token délivré donne bien accès aux routes protégées.
        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'jean@example.com');
    }

    public function test_register_refuse_un_telephone_invalide(): void
    {
        $this->register(['telephone' => 'abc'])->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_register_refuse_un_email_deja_utilise(): void
    {
        User::factory()->create(['email' => 'jean@example.com']);

        $this->register()->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_login_avec_bons_identifiants(): void
    {
        $user = User::factory()->create(['email' => 'membre@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'membre@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('api_tokens', ['user_id' => $user->id]);
    }

    public function test_login_refuse_un_mauvais_mot_de_passe(): void
    {
        User::factory()->create(['email' => 'membre@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'membre@example.com',
            'password' => 'mauvais-mdp',
        ])->assertStatus(401)->assertJsonPath('ok', false);
    }

    public function test_login_refuse_un_compte_suspendu(): void
    {
        User::factory()->create(['email' => 'suspendu@example.com', 'is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email' => 'suspendu@example.com',
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_les_routes_protegees_exigent_un_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
        $this->withToken('token-bidon')->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_logout_invalide_le_token(): void
    {
        $token = $this->register()->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
        $this->assertSame(0, ApiToken::count());
    }
}

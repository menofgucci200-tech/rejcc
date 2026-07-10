<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        return $plain;
    }

    public function test_les_routes_admin_exigent_un_token(): void
    {
        $this->getJson('/api/admin/stats')->assertStatus(401);
    }

    public function test_un_membre_ne_peut_pas_acceder_aux_routes_admin(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $this->withToken($token)->getJson('/api/admin/stats')->assertStatus(403);
    }

    public function test_un_admin_accede_aux_routes_admin(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($token)->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['stats' => ['membres', 'admins', 'adhesions']]);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    private function memberToken(?User &$user = null): string
    {
        $user = User::factory()->create(['role' => 'member']);
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_les_16_groupes_sectoriels_sont_disponibles(): void
    {
        $groups = $this->withToken($this->memberToken())->getJson('/api/groups')
            ->assertOk()->json('groups');

        $this->assertCount(16, $groups);
        $this->assertSame('Agriculture & Pêche', $groups[0]['name']);
        $this->assertSame('Action Sociale & Solidarité', $groups[15]['name']);
        $this->assertSame(0, $groups[0]['members']);
        $this->assertFalse($groups[0]['joined']);
    }

    public function test_un_membre_rejoint_et_quitte_plusieurs_groupes(): void
    {
        $token = $this->memberToken($user);

        // Adhésion multiple (ex. suivre plusieurs formations en parallèle).
        $this->withToken($token)->postJson('/api/groups/1/join')->assertOk();
        $this->withToken($token)->postJson('/api/groups/2/join')->assertOk();
        // Rejoindre deux fois le même groupe reste idempotent.
        $this->withToken($token)->postJson('/api/groups/1/join')->assertOk()
            ->assertJsonPath('members', 1);

        $groups = collect($this->withToken($token)->getJson('/api/groups')->json('groups'));
        $this->assertTrue($groups->firstWhere('id', 1)['joined']);
        $this->assertTrue($groups->firstWhere('id', 2)['joined']);
        $this->assertSame(2, $user->groups()->count());

        // Quitter un groupe.
        $this->withToken($token)->postJson('/api/groups/1/leave')->assertOk()
            ->assertJsonPath('members', 0);
        $this->assertSame(1, $user->fresh()->groups()->count());

        // Groupe inexistant.
        $this->withToken($token)->postJson('/api/groups/999/join')->assertStatus(404);
    }

    public function test_les_groupes_exigent_une_authentification(): void
    {
        $this->getJson('/api/groups')->assertStatus(401);
    }
}

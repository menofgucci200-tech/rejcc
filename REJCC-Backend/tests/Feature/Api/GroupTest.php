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

        $specialite = 'Plombier spécialisé en dépannage sanitaire et chauffage central.';

        // Adhésion multiple (ex. suivre plusieurs formations en parallèle).
        $this->withToken($token)->postJson('/api/groups/1/join', ['specialite' => $specialite])->assertOk();
        $this->withToken($token)->postJson('/api/groups/2/join', ['specialite' => $specialite])->assertOk();
        // Rejoindre deux fois le même groupe reste idempotent (met à jour la spécialité).
        $this->withToken($token)->postJson('/api/groups/1/join', ['specialite' => $specialite])->assertOk()
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

    public function test_la_specialite_est_obligatoire_pour_rejoindre_un_groupe(): void
    {
        $token = $this->memberToken();

        $this->withToken($token)->postJson('/api/groups/1/join')
            ->assertStatus(422);

        $this->withToken($token)->postJson('/api/groups/1/join', ['specialite' => 'court'])
            ->assertStatus(422);
    }

    public function test_le_trombinoscope_est_reserve_aux_abonnes_a_jour(): void
    {
        $token = $this->memberToken(); // pas d'abonnement

        $this->withToken($token)->getJson('/api/groups/1/members')
            ->assertStatus(402)
            ->assertJsonPath('code', 'subscription_required');
    }

    public function test_le_trombinoscope_affiche_les_membres_et_leur_specialite(): void
    {
        $viewerToken = $this->memberToken($viewer);
        $viewer->subscription_expires_at = now()->addYear();
        $viewer->save();

        $plombier = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Koffi', 'ville' => 'Abidjan']);
        $plombierToken = $this->tokenFor($plombier);
        $this->withToken($plombierToken)->postJson('/api/groups/8/join', [
            'specialite' => 'Plombier spécialisé en dépannage sanitaire et chauffage central.',
        ])->assertOk();

        $roster = $this->withToken($viewerToken)->getJson('/api/groups/8/members')
            ->assertOk()->json();

        $this->assertSame('BTP & Construction', $roster['group']['name']);
        $this->assertCount(1, $roster['members']);
        $this->assertSame('Awa', $roster['members'][0]['prenom']);
        $this->assertStringContainsString('dépannage sanitaire', $roster['members'][0]['specialite']);

        // Recherche par spécialité.
        $recherche = $this->withToken($viewerToken)->getJson('/api/groups/8/members?q=chauffage')
            ->assertOk()->json('members');
        $this->assertCount(1, $recherche);
    }

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }
}

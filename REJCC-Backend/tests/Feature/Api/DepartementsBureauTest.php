<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use App\Support\Departements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Comptes des départements du Bureau (dashboard) : rubriques par défaut,
 * espace limité à leur département, et droits d'administration réservés à
 * l'administrateur général.
 */
class DepartementsBureauTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function nouveauCompte(array $extra = []): array
    {
        return [
            'prenom' => 'Awa', 'nom' => 'Koné', 'email' => 'awa'.Str::random(4).'@exemple.ci',
            'telephone' => '0700000000', 'password' => 'motdepasse', 'role' => 'admin', ...$extra,
        ];
    }

    public function test_il_y_a_onze_departements_sans_la_presidence(): void
    {
        $this->assertCount(11, Departements::slugs());
        $this->assertNotContains('presidence', Departements::slugs());
    }

    public function test_un_compte_de_departement_recoit_les_rubriques_de_son_departement(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));

        $r = $this->withToken($admin)->postJson('/api/admin/members', $this->nouveauCompte(['bureau_departement' => 'communication']))->assertOk();
        $this->assertSame('communication', $r->json('member.bureau_departement'));
        $this->assertSame(['actualites', 'evenements', 'contenu'], $r->json('member.permissions'));

        $compte = User::find($r->json('member.id'));
        $this->assertFalse($compte->estAdminComplet());

        $token = $this->tokenFor($compte);
        $this->withToken($token)->getJson('/api/admin/events')->assertOk();
        $this->withToken($token)->getJson('/api/admin/members')->assertStatus(403);
        $this->withToken($token)->getJson('/api/auth/me')->assertJsonPath('user.bureau_departement_label', 'Communication et Marketing');
    }

    public function test_un_departement_ne_voit_que_son_espace(): void
    {
        $compte = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'juridique', 'permissions' => Departements::sections('juridique')]);
        User::factory()->create(['role' => 'admin', 'bureau_departement' => 'tresorerie', 'permissions' => ['membres']]);

        $r = $this->withToken($this->tokenFor($compte))->getJson('/api/admin/departements')->assertOk();
        $this->assertSame(['juridique'], collect($r->json('departements'))->pluck('slug')->all());

        $general = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));
        $tous = $this->withToken($general)->getJson('/api/admin/departements')->assertOk();
        $this->assertCount(11, $tous->json('departements'));
        $this->assertCount(1, collect($tous->json('departements'))->firstWhere('slug', 'tresorerie')['membres']);
    }

    public function test_un_admin_limite_ne_peut_ni_creer_un_admin_ni_s_elever(): void
    {
        $tresorier = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'tresorerie', 'permissions' => ['membres']]);
        $token = $this->tokenFor($tresorier);

        $this->withToken($token)->postJson('/api/admin/members', $this->nouveauCompte(['permissions' => null]))->assertStatus(403);
        $this->withToken($token)->putJson('/api/admin/members/'.$tresorier->id, ['permissions' => null])->assertStatus(403);
        $this->assertSame(['membres'], $tresorier->fresh()->permissions);

        // Un membre ordinaire reste gérable par la Trésorerie (rubrique Membres).
        $membre = User::factory()->create(['role' => 'member']);
        $this->withToken($token)->putJson('/api/admin/members/'.$membre->id, ['ville' => 'Bouaké'])->assertOk();
        $this->withToken($token)->putJson('/api/admin/members/'.$membre->id, ['role' => 'admin'])->assertStatus(403);
    }

    public function test_changer_de_departement_applique_ses_rubriques(): void
    {
        $general = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => null]));
        $compte = User::factory()->create(['role' => 'admin', 'bureau_departement' => 'organisation', 'permissions' => ['evenements']]);

        $this->withToken($general)->putJson('/api/admin/members/'.$compte->id, ['bureau_departement' => 'projets'])->assertOk();
        $this->assertSame(Departements::sections('projets'), $compte->fresh()->permissions);

        $this->withToken($general)->putJson('/api/admin/members/'.$compte->id, ['role' => 'member'])->assertOk();
        $this->assertNull($compte->fresh()->bureau_departement);
    }
}

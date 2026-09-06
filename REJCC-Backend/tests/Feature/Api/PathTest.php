<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Path;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PathTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function formation(string $title): Formation
    {
        return Formation::create(['title' => $title, 'category' => 'Entrepreneuriat', 'modules_count' => 1]);
    }

    public function test_un_admin_cree_un_parcours_et_y_attache_des_formations_ordonnees(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $f1 = $this->formation('Les bases');
        $f2 = $this->formation('Aller plus loin');

        $pathId = $this->withToken($token)->postJson('/api/admin/paths', [
            'title' => 'Parcours Entrepreneur Junior',
            'objectif' => 'Lancer son projet en 2 formations.',
        ])->assertOk()->json('path.id');

        $this->assertNotNull(Path::find($pathId)->slug);

        $this->withToken($token)->putJson("/api/admin/paths/{$pathId}/formations", [
            'formation_ids' => [$f2->id, $f1->id],
        ])->assertOk();

        $ordre = Path::find($pathId)->formations()->pluck('formations.title')->all();
        $this->assertSame(['Aller plus loin', 'Les bases'], $ordre);
    }

    public function test_les_formations_d_un_parcours_se_debloquent_progressivement(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $f1 = $this->formation('Les bases');
        $f2 = $this->formation('Aller plus loin');

        $pathId = $this->withToken($admin)->postJson('/api/admin/paths', ['title' => 'Parcours Test'])
            ->json('path.id');
        $this->withToken($admin)->putJson("/api/admin/paths/{$pathId}/formations", [
            'formation_ids' => [$f1->id, $f2->id],
        ]);

        $membre = User::factory()->create();
        $token = $this->tokenFor($membre);

        // Avant toute inscription : la 1ère formation est déverrouillée, la 2e non.
        $detail = $this->withToken($token)->getJson("/api/paths/{$pathId}")->assertOk()->json();
        $this->assertFalse($detail['formations'][0]['verrouille']);
        $this->assertTrue($detail['formations'][1]['verrouille']);
        $this->assertFalse($detail['path']['badge_obtenu']);

        // On termine la 1ère formation.
        FormationEnrollment::create(['formation_id' => $f1->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);

        $detail = $this->withToken($token)->getJson("/api/paths/{$pathId}")->json();
        $this->assertFalse($detail['formations'][1]['verrouille']);
        $this->assertFalse($detail['path']['badge_obtenu']); // la 2e n'est pas encore terminée

        // On termine la 2e : le badge du parcours est obtenu.
        FormationEnrollment::create(['formation_id' => $f2->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);

        $liste = $this->withToken($token)->getJson('/api/paths')->assertOk()->json('paths');
        $this->assertTrue($liste[0]['badge_obtenu']);
        $this->assertSame(2, $liste[0]['formations_terminees']);
    }

    public function test_un_parcours_non_publie_est_invisible_des_membres(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->withToken($admin)->postJson('/api/admin/paths', ['title' => 'Brouillon', 'is_published' => false]);

        $membre = $this->tokenFor(User::factory()->create());
        $paths = $this->withToken($membre)->getJson('/api/paths')->assertOk()->json('paths');

        $this->assertCount(0, $paths);
    }

    public function test_un_membre_ne_peut_pas_gerer_les_parcours(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson('/api/admin/paths', ['title' => 'Intrusion'])
            ->assertStatus(403);
    }
}

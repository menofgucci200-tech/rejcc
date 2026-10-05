<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\MemberNotification;
use App\Models\Path;
use App\Models\PathBadge;
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

    private function formation(string $title, bool $contenu = true): Formation
    {
        $f = Formation::create(['title' => $title, 'category' => 'Entrepreneuriat', 'modules_count' => 1, 'is_published' => true]);
        if ($contenu) {
            $f->modules()->create(['titre' => 'Module 1', 'contenu' => 'Leçon', 'ordre' => 1]);
        }

        return $f;
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

    public function test_un_parcours_conseille_un_ordre_sans_verrouiller(): void
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

        // Avant toute inscription : la 1ère formation est conseillée, la 2e
        // reste accessible mais conseillée après la 1ère.
        $detail = $this->withToken($token)->getJson("/api/paths/{$pathId}")->assertOk()->json();
        $this->assertTrue($detail['formations'][0]['conseillee']);
        $this->assertFalse($detail['formations'][1]['conseillee']);
        $this->assertSame('Les bases', $detail['formations'][1]['conseillee_apres']);
        $this->assertArrayNotHasKey('verrouille', $detail['formations'][1]);
        $this->assertFalse($detail['path']['badge_obtenu']);

        // On termine la 1ère formation.
        FormationEnrollment::create(['formation_id' => $f1->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);

        $detail = $this->withToken($token)->getJson("/api/paths/{$pathId}")->json();
        $this->assertTrue($detail['formations'][1]['conseillee']);
        $this->assertNull($detail['formations'][1]['conseillee_apres']);
        $this->assertFalse($detail['path']['badge_obtenu']); // la 2e n'est pas encore terminée

        // On termine la 2e : le badge du parcours est obtenu.
        FormationEnrollment::create(['formation_id' => $f2->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);

        $liste = $this->withToken($token)->getJson('/api/paths')->assertOk()->json('paths');
        $this->assertTrue($liste[0]['badge_obtenu']);
        $this->assertSame(2, $liste[0]['formations_terminees']);
    }

    public function test_le_badge_est_enregistre_notifie_et_conserve(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $f1 = $this->formation('Les bases');
        $f1->update(['duration' => '4 semaines']);
        $f2 = $this->formation('Aller plus loin');
        $f2->update(['duration' => '2 semaines']);

        $pathId = $this->withToken($admin)->postJson('/api/admin/paths', ['title' => 'Parcours Test'])->json('path.id');
        $this->withToken($admin)->putJson("/api/admin/paths/{$pathId}/formations", ['formation_ids' => [$f1->id, $f2->id]]);

        $membre = User::factory()->create();
        $token = $this->tokenFor($membre);

        $liste = $this->withToken($token)->getJson('/api/paths')->json('paths');
        $this->assertSame('6 semaines', $liste[0]['duree']);
        $this->assertFalse($liste[0]['commence']);

        FormationEnrollment::create(['formation_id' => $f1->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);
        $this->assertSame(0, PathBadge::count());

        // La 2e formation se termine : le badge est enregistré et le membre notifié, une seule fois.
        $e = FormationEnrollment::create(['formation_id' => $f2->id, 'user_id' => $membre->id, 'progress' => 50]);
        $e->update(['progress' => 100, 'completed_at' => now()]);
        $this->assertSame(1, PathBadge::where('user_id', $membre->id)->count());
        $this->assertSame(1, MemberNotification::where('user_id', $membre->id)->where('title', 'like', 'Badge%')->count());

        // Le badge reste acquis si une nouvelle formation est ajoutée au parcours.
        $f3 = $this->formation('Nouveauté');
        $this->withToken($admin)->putJson("/api/admin/paths/{$pathId}/formations", ['formation_ids' => [$f1->id, $f2->id, $f3->id]]);
        $liste = $this->withToken($token)->getJson('/api/paths')->json('paths');
        $this->assertTrue($liste[0]['badge_obtenu']);
        $this->assertTrue($liste[0]['commence']);

        // Visible sur la page biographique et dans le fil d'activité.
        $membre->forceFill(['subscription_expires_at' => now()->addYear()])->save();
        $this->getJson('/api/member-card/'.$membre->id)->assertJsonPath('card.badges_parcours.0.titre', 'Parcours Test');
        $this->assertStringContainsString('Parcours Test', json_encode($this->withToken($token)->getJson('/api/my-activity')->json('activity')));
    }

    public function test_une_etape_sans_contenu_ne_bloque_pas_et_ne_compte_pas(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $f1 = $this->formation('Sans contenu', contenu: false);
        $f2 = $this->formation('Avec contenu');
        $f3 = $this->formation('Brouillon');
        $f3->update(['is_published' => false]);

        $pathId = $this->withToken($admin)->postJson('/api/admin/paths', ['title' => 'Parcours Test'])->json('path.id');
        $this->withToken($admin)->putJson("/api/admin/paths/{$pathId}/formations", ['formation_ids' => [$f1->id, $f2->id, $f3->id]]);

        $membre = User::factory()->create();
        $token = $this->tokenFor($membre);

        // Une ancienne validation « au clic » d'une formation sans contenu ne compte pas.
        FormationEnrollment::create(['formation_id' => $f1->id, 'user_id' => $membre->id, 'progress' => 100, 'completed_at' => now()]);

        $detail = $this->withToken($token)->getJson("/api/paths/{$pathId}")->assertOk()->json();
        $this->assertFalse($detail['formations'][0]['disponible']);
        $this->assertFalse($detail['formations'][0]['completed']);
        $this->assertFalse($detail['formations'][0]['is_certifying']);
        $this->assertTrue($detail['formations'][1]['conseillee']); // l'étape sans contenu est sautée
        $this->assertNull($detail['formations'][0]['numero']);
        $this->assertSame(1, $detail['formations'][1]['numero']);
        $this->assertFalse($detail['formations'][2]['disponible']);

        $liste = $this->withToken($token)->getJson('/api/paths')->json('paths');
        $this->assertSame(1, $liste[0]['total_formations']);
        $this->assertSame(0, $liste[0]['formations_terminees']);
        $this->assertSame(2, $liste[0]['a_venir']);

        $admins = $this->withToken($admin)->getJson('/api/admin/paths')->assertOk()->json('paths');
        $this->assertSame(2, $admins[0]['formations_indisponibles_count']);
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

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormationTest extends TestCase
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

    private function formation(array $overrides = []): Formation
    {
        return Formation::create($overrides + [
            'title' => 'Prise de parole en public',
            'category' => 'Communication',
            'duration' => '3 semaines',
            'level' => 'Débutant',
            'modules_count' => 6,
        ]);
    }

    public function test_le_catalogue_ne_montre_que_les_formations_publiees(): void
    {
        $this->formation(['title' => 'Publiée']);
        $this->formation(['title' => 'Brouillon', 'is_published' => false]);
        $token = $this->tokenFor(User::factory()->create());

        $response = $this->withToken($token)->getJson('/api/formations')->assertOk();

        $this->assertSame(['Publiée'], array_column($response->json('formations'), 'title'));
    }

    public function test_un_membre_peut_s_inscrire_une_seule_fois(): void
    {
        $formation = $this->formation();
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/enroll")->assertOk();
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/enroll")->assertOk();

        $this->assertSame(1, FormationEnrollment::count());

        $catalogue = $this->withToken($token)->getJson('/api/formations')->json('formations');
        $this->assertTrue($catalogue[0]['enrolled']);
    }

    public function test_impossible_de_s_inscrire_a_une_formation_non_publiee(): void
    {
        $formation = $this->formation(['is_published' => false]);
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/enroll")->assertStatus(404);
    }

    public function test_mes_formations_liste_les_inscriptions_avec_la_progression(): void
    {
        $formation = $this->formation();
        $user = User::factory()->create();
        FormationEnrollment::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
            'progress' => 40,
        ]);

        $response = $this->withToken($this->tokenFor($user))->getJson('/api/my-formations')->assertOk();

        $this->assertSame(40, $response->json('formations.0.progress'));
        $this->assertFalse($response->json('formations.0.completed'));
    }

    public function test_valider_un_module_fait_avancer_la_progression(): void
    {
        $formation = $this->formation(['modules_count' => 4]);
        $user = User::factory()->create();
        FormationEnrollment::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
        ]);
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/complete-module")
            ->assertOk()->assertJsonPath('progress', 25)->assertJsonPath('completed', false);

        // Valider les 3 modules restants termine la formation.
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withToken($token)->postJson("/api/formations/{$formation->id}/complete-module");
        }
        $response->assertJsonPath('progress', 100)->assertJsonPath('completed', true);

        // Un clic de plus ne dépasse pas 100 %.
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/complete-module")
            ->assertJsonPath('progress', 100);
    }

    public function test_valider_un_module_exige_d_etre_inscrit(): void
    {
        $formation = $this->formation();
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/complete-module")
            ->assertStatus(404);
    }

    public function test_un_admin_gere_le_cycle_de_vie_d_une_formation(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        // Création
        $id = $this->withToken($token)->postJson('/api/admin/formations', [
            'title' => 'Nouvelle formation',
            'category' => 'Finance',
            'modules_count' => 4,
        ])->assertOk()->json('formation.id');

        // Dépublication
        $this->withToken($token)->putJson("/api/admin/formations/{$id}", [
            'title' => 'Nouvelle formation',
            'category' => 'Finance',
            'is_published' => false,
        ])->assertOk();
        $this->assertFalse(Formation::find($id)->is_published);

        // Suppression
        $this->withToken($token)->deleteJson("/api/admin/formations/{$id}")->assertOk();
        $this->assertNull(Formation::find($id));
    }

    public function test_la_creation_est_validee(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($token)->postJson('/api/admin/formations', ['title' => 'X'])
            ->assertStatus(422);
    }

    public function test_un_membre_ne_peut_pas_gerer_les_formations(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $this->withToken($token)->postJson('/api/admin/formations', [
            'title' => 'Intrusion',
            'category' => 'Test',
        ])->assertStatus(403);
    }

    // ------------------------------------------------------------ Modules réels

    public function test_un_admin_cree_des_modules_et_le_compteur_se_synchronise(): void
    {
        $formation = $this->formation(['modules_count' => 1]);
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin']));

        $this->withToken($token)->postJson("/api/admin/formations/{$formation->id}/modules", [
            'titre' => 'Introduction', 'description' => 'Les bases.', 'ordre' => 1,
        ])->assertOk();
        $this->withToken($token)->postJson("/api/admin/formations/{$formation->id}/modules", [
            'titre' => 'Aller plus loin', 'video_url' => 'https://youtube.com/x', 'ordre' => 2,
        ])->assertOk();

        $this->assertSame(2, $formation->fresh()->modules_count);
        $this->assertSame(2, $formation->modules()->count());
    }

    public function test_le_contenu_des_modules_exige_d_etre_inscrit(): void
    {
        $formation = $this->formation();
        $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->getJson("/api/formations/{$formation->id}/modules")->assertStatus(404);
    }

    public function test_les_modules_se_debloquent_dans_l_ordre(): void
    {
        $formation = $this->formation();
        $m1 = $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $m2 = $formation->modules()->create(['titre' => 'Module 2', 'ordre' => 2]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);
        $token = $this->tokenFor($user);

        $liste = $this->withToken($token)->getJson("/api/formations/{$formation->id}/modules")
            ->assertOk()->json('modules');

        $this->assertFalse($liste[0]['verrouille']);
        $this->assertTrue($liste[1]['verrouille']);

        // Impossible de valider le module 2 avant le module 1.
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$m2->id}/complete")
            ->assertStatus(422);

        // Le module 1 se valide, puis le module 2 devient accessible.
        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$m1->id}/complete")
            ->assertOk()->assertJsonPath('progress', 50)->assertJsonPath('completed', false);

        $this->withToken($token)->postJson("/api/formations/{$formation->id}/modules/{$m2->id}/complete")
            ->assertOk()->assertJsonPath('progress', 100)->assertJsonPath('completed', true);
    }

    public function test_l_ancien_compteur_est_desactive_des_qu_il_y_a_des_modules_reels(): void
    {
        $formation = $this->formation();
        $formation->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $user = User::factory()->create();
        FormationEnrollment::create(['formation_id' => $formation->id, 'user_id' => $user->id]);

        $this->withToken($this->tokenFor($user))->postJson("/api/formations/{$formation->id}/complete-module")
            ->assertStatus(422);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Lot3Test extends TestCase
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

    private function adminToken(): string
    {
        return $this->tokenFor(User::factory()->create(['role' => 'admin']));
    }

    // ------------------------------------------------------------ Certificats

    public function test_un_certificat_est_emis_pour_une_formation_certifiante_terminee(): void
    {
        $user = User::factory()->create(['prenom' => 'Marie', 'nom' => 'Aka']);
        $certifiante = Formation::create(['title' => 'Leadership', 'category' => 'Leadership', 'is_certifying' => true, 'modules_count' => 2]);
        $simple = Formation::create(['title' => 'Productivité', 'category' => 'Productivité', 'is_certifying' => false, 'modules_count' => 2]);

        FormationEnrollment::create(['formation_id' => $certifiante->id, 'user_id' => $user->id, 'progress' => 100, 'completed_at' => now()]);
        FormationEnrollment::create(['formation_id' => $simple->id, 'user_id' => $user->id, 'progress' => 100, 'completed_at' => now()]);

        $certs = $this->withToken($this->tokenFor($user))->getJson('/api/my-certificates')
            ->assertOk()->json('certificates');

        // Seule la formation certifiante donne un certificat.
        $this->assertCount(1, $certs);
        $this->assertSame('Leadership', $certs[0]['title']);
        $this->assertStringStartsWith('REJCC-CERT-', $certs[0]['reference']);

        // Le registre admin liste le certificat avec le nom du membre.
        $registre = $this->withToken($this->adminToken())->getJson('/api/admin/certificates')
            ->assertOk()->json('certificates');
        $this->assertSame('Marie Aka', $registre[0]['member']);
    }

    // ------------------------------------------------------------ Projets

    public function test_un_membre_propose_un_projet_qui_entre_en_evaluation(): void
    {
        $token = $this->tokenFor(User::factory()->abonne()->create());

        $project = $this->withToken($token)->postJson('/api/projects', [
            'title' => 'Coopérative agricole jeunesse',
            'description' => 'Structurer un circuit court de vente de produits maraîchers.',
            'members_count' => 6,
        ])->assertStatus(201)->json('project');

        $this->assertSame('En évaluation', $project['status']);

        $liste = $this->withToken($token)->getJson('/api/projects')->assertOk()->json('projects');
        $this->assertTrue($liste[0]['mine']);
    }

    public function test_un_membre_sans_abonnement_ne_peut_pas_proposer_de_projet(): void
    {
        $token = $this->tokenFor(User::factory()->create()); // pas d'abonnement

        $this->withToken($token)->postJson('/api/projects', [
            'title' => 'Coopérative agricole jeunesse',
            'description' => 'Structurer un circuit court de vente de produits maraîchers.',
            'members_count' => 6,
        ])->assertStatus(402);
    }

    public function test_l_admin_fait_evoluer_le_statut_d_un_projet(): void
    {
        $member = User::factory()->abonne()->create();
        $project = Project::create([
            'user_id' => $member->id,
            'title' => 'Atelier couture solidaire',
            'description' => 'Formation de jeunes femmes à la couture avec insertion professionnelle.',
        ]);

        $admin = $this->adminToken();

        $this->withToken($admin)->putJson("/api/admin/projects/{$project->id}", [
            'title' => $project->title,
            'description' => $project->description,
            'status' => 'Lancé',
        ])->assertOk()->assertJsonPath('project.status', 'Lancé');

        $this->assertSame('Lancé', $project->fresh()->status);
    }

    public function test_l_admin_supprime_un_projet(): void
    {
        $project = Project::create([
            'title' => 'Projet à supprimer',
            'description' => 'Description suffisamment longue pour la validation.',
        ]);

        $this->withToken($this->adminToken())->deleteJson("/api/admin/projects/{$project->id}")->assertOk();
        $this->assertNull(Project::find($project->id));
    }

    // ------------------------------------------------------------ Cloisonnement

    public function test_un_membre_ne_peut_pas_administrer_ces_sections(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $this->withToken($token)->getJson('/api/admin/certificates')->assertStatus(403);
        $this->withToken($token)->putJson('/api/admin/projects/1', [])->assertStatus(403);
    }
}

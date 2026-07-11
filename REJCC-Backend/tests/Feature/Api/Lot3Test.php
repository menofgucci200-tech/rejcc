<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Project;
use App\Models\Resource;
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

    // ------------------------------------------------------------ Ressources

    public function test_l_admin_gere_les_ressources_et_le_membre_les_telecharge(): void
    {
        $admin = $this->adminToken();

        $resource = $this->withToken($admin)->postJson('/api/admin/resources', [
            'title' => 'Guide business plan',
            'type' => 'Ebook',
            'url' => 'https://exemple.org/guide.pdf',
            'size' => '2 Mo',
        ])->assertOk()->json('resource');

        $member = $this->tokenFor(User::factory()->create());

        // Le membre voit la ressource et la télécharge (compteur incrémenté).
        $this->withToken($member)->getJson('/api/resources')->assertOk()
            ->assertJsonPath('resources.0.title', 'Guide business plan');
        $this->withToken($member)->postJson("/api/resources/{$resource['id']}/download")
            ->assertOk()->assertJsonPath('url', 'https://exemple.org/guide.pdf');
        $this->assertSame(1, Resource::find($resource['id'])->downloads);

        // Dépubliée, elle disparaît côté membre.
        $this->withToken($admin)->putJson("/api/admin/resources/{$resource['id']}", [
            'title' => 'Guide business plan',
            'type' => 'Ebook',
            'url' => 'https://exemple.org/guide.pdf',
            'is_published' => false,
        ])->assertOk();
        $this->assertSame([], $this->withToken($member)->getJson('/api/resources')->json('resources'));

        $this->withToken($admin)->deleteJson("/api/admin/resources/{$resource['id']}")->assertOk();
        $this->assertNull(Resource::find($resource['id']));
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
        $token = $this->tokenFor(User::factory()->create());

        $project = $this->withToken($token)->postJson('/api/projects', [
            'title' => 'Coopérative agricole jeunesse',
            'description' => 'Structurer un circuit court de vente de produits maraîchers.',
            'members_count' => 6,
        ])->assertStatus(201)->json('project');

        $this->assertSame('En évaluation', $project['status']);
        $this->assertCount(4, $project['milestones']);

        $liste = $this->withToken($token)->getJson('/api/projects')->assertOk()->json('projects');
        $this->assertTrue($liste[0]['mine']);
    }

    public function test_l_admin_valide_un_projet_et_le_suit_dans_l_incubateur(): void
    {
        $member = User::factory()->create();
        $project = Project::create([
            'user_id' => $member->id,
            'title' => 'Atelier couture solidaire',
            'description' => 'Formation de jeunes femmes à la couture avec insertion professionnelle.',
            'milestones' => Project::defaultMilestones(),
        ]);

        $admin = $this->adminToken();
        $milestones = Project::defaultMilestones();
        $milestones[0]['done'] = true;
        $milestones[1]['done'] = true;

        $this->withToken($admin)->putJson("/api/admin/projects/{$project->id}", [
            'title' => $project->title,
            'description' => $project->description,
            'status' => 'Financement en cours',
            'in_incubator' => true,
            'funding_goal' => 5000000,
            'funding_raised' => 3200000,
            'milestones' => $milestones,
        ])->assertOk();

        // Le projet apparaît dans l'incubateur avec financement et jalons.
        $incub = $this->withToken($this->tokenFor($member))->getJson('/api/incubator')
            ->assertOk()->json('projects');
        $this->assertCount(1, $incub);
        $this->assertSame(3200000, $incub[0]['funding_raised']);
        $this->assertTrue($incub[0]['milestones'][0]['done']);
        $this->assertFalse($incub[0]['milestones'][2]['done']);
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

        $this->withToken($token)->postJson('/api/admin/resources', [])->assertStatus(403);
        $this->withToken($token)->getJson('/api/admin/certificates')->assertStatus(403);
        $this->withToken($token)->putJson('/api/admin/projects/1', [])->assertStatus(403);
    }
}

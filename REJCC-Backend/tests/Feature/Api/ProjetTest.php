<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjetTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function donnees(array $o = []): array
    {
        return $o + [
            'title' => 'AgroVert — séchage de mangues',
            'accroche' => 'Des mangues séchées ivoiriennes pour l’export.',
            'description' => 'Unité de transformation de mangues séchées pour l’export, avec 12 emplois locaux.',
            'probleme' => 'Les mangues invendues pourrissent.',
            'solution' => 'Un séchoir solaire coopératif.',
            'group_id' => Group::firstOrCreate(['slug' => 'agro'], ['name' => 'Agriculture'])->id,
            'stade' => 'developpement',
            'ville' => 'Korhogo',
            'besoins' => ['partenaires', 'financement'],
        ];
    }

    public function test_seuls_les_projets_valides_sont_visibles_des_autres(): void
    {
        $porteur = User::factory()->abonne()->create();
        $autre = User::factory()->abonne()->create();
        $valide = Project::create(['user_id' => $porteur->id, 'title' => 'Projet validé', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
        $enCours = Project::create(['user_id' => $porteur->id, 'title' => 'Projet confidentiel', 'description' => str_repeat('b', 30), 'statut' => 'evaluation']);
        Project::create(['user_id' => $porteur->id, 'title' => 'Projet refusé', 'description' => str_repeat('c', 30), 'statut' => 'refuse', 'motif' => 'Hors cadre']);

        $t = $this->tokenFor($autre);
        $res = $this->withToken($t)->getJson('/api/projects')->assertOk()->json();
        $this->assertSame(['Projet validé'], array_column($res['projects'], 'title'));
        $this->assertSame([], $res['mes_projets']);
        $this->assertArrayNotHasKey('motif', $res['projects'][0]);
        $this->withToken($t)->getJson("/api/projects/{$enCours->id}")->assertStatus(404);
        $this->withToken($t)->getJson("/api/projects/{$valide->id}")->assertOk();
        $this->assertSame(1, $valide->fresh()->vues);

        // Le porteur voit tous les siens, avec le motif.
        $mes = $this->withToken($this->tokenFor($porteur))->getJson('/api/projects')->json('mes_projets');
        $this->assertCount(3, $mes);
        $this->assertSame('Hors cadre', collect($mes)->firstWhere('title', 'Projet refusé')['motif']);
    }

    public function test_formulaire_complet_et_validation(): void
    {
        $t = $this->tokenFor(User::factory()->abonne()->create());

        $this->withToken($t)->postJson('/api/projects', $this->donnees(['group_id' => null]))
            ->assertStatus(422)->assertJsonPath('message', 'Choisissez le secteur de votre projet.');
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => ['inconnu']]))->assertStatus(422);

        $p = $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => ['partenaires', 'financement', 'partenaires']]))
            ->assertCreated()->json('project');
        $this->assertSame(['partenaires', 'financement'], $p['besoins']);
        $this->assertSame('Agriculture', $p['groupe']['nom']);
        $this->assertSame('Un séchoir solaire coopératif.', $p['solution']);

        // Au plus 3 projets en évaluation en même temps.
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->assertCreated();
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->assertCreated();
        $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->assertStatus(422);
    }

    public function test_circuit_completer_resoumettre_valider_refuser(): void
    {
        $porteur = User::factory()->abonne()->create();
        $t = $this->tokenFor($porteur);
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $id = $this->withToken($t)->postJson('/api/projects', $this->donnees(['besoins' => []]))->json('project.id');

        // Précisions demandées : motif obligatoire, porteur notifié.
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'completer'])->assertStatus(422);
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'completer', 'motif' => 'Précisez votre public cible.'])
            ->assertOk()->assertJsonPath('project.statut', 'a_completer');
        $notif = MemberNotification::where('user_id', $porteur->id)->latest('id')->first();
        $this->assertStringStartsWith('Projet à compléter', $notif->title);
        $this->assertStringContainsString('Précisez votre public cible.', $notif->body);
        $this->assertSame("/espace-membre/projets?projet={$id}", $notif->link);

        // Le porteur complète : retour en évaluation.
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees(['cible' => 'Coopératives de femmes du Nord']))
            ->assertOk()->assertJsonPath('resoumis', true)->assertJsonPath('project.statut', 'evaluation');

        // Validation avec stade.
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'valider', 'stade' => 'lance'])->assertOk();
        $this->assertSame(['valide', 'lance', null], [Project::find($id)->statut, Project::find($id)->stade, Project::find($id)->motif]);
        $this->assertTrue(MemberNotification::where('user_id', $porteur->id)->where('title', 'like', 'Projet validé%')->exists());

        // Modifier un projet validé le laisse validé.
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees())->assertJsonPath('resoumis', false)->assertJsonPath('project.statut', 'valide');

        // Refus motivé, puis le porteur peut retravailler et resoumettre.
        $this->withToken($admin)->postJson("/api/admin/projects/{$id}/decision", ['decision' => 'refuser', 'motif' => 'Projet hors du cadre du réseau.'])->assertOk();
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees())->assertJsonPath('project.statut', 'evaluation');

        // Retrait : plus visible, plus modifiable.
        $this->withToken($t)->postJson("/api/projects/{$id}/retirer")->assertOk();
        $this->withToken($t)->putJson("/api/projects/{$id}", $this->donnees())->assertStatus(422);
        $this->withToken($t)->deleteJson("/api/projects/{$id}")->assertOk();
        $this->assertNull(Project::find($id));
    }

    public function test_un_membre_ne_modifie_pas_le_projet_d_un_autre(): void
    {
        $p = Project::create(['user_id' => User::factory()->create()->id, 'title' => 'Projet', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
        $t = $this->tokenFor(User::factory()->abonne()->create());

        $this->withToken($t)->putJson("/api/projects/{$p->id}", $this->donnees())->assertStatus(404);
        $this->withToken($t)->postJson("/api/projects/{$p->id}/retirer")->assertStatus(404);
        $this->withToken($t)->deleteJson("/api/projects/{$p->id}")->assertOk();
        $this->assertNotNull($p->fresh());
    }

    public function test_admin_liste_filtre_et_compteurs(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        Project::create(['title' => 'Alpha', 'description' => str_repeat('a', 30), 'statut' => 'valide']);
        Project::create(['title' => 'Bêta', 'description' => str_repeat('b', 30), 'statut' => 'evaluation', 'soumis_at' => now()]);

        $res = $this->withToken($admin)->getJson('/api/admin/projects')->assertOk()->json();
        $this->assertSame('Bêta', $res['projects'][0]['title']); // à traiter en premier
        $this->assertSame(['evaluation' => 1, 'valide' => 1], collect($res['compteurs'])->sortKeys()->all());
        $this->assertCount(1, $this->withToken($admin)->getJson('/api/admin/projects?statut=valide')->json('projects'));
        $this->assertCount(1, $this->withToken($admin)->getJson('/api/admin/projects?q=alph')->json('projects'));
    }
}

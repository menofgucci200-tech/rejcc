<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\NewsArticle;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminContentTest extends TestCase
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

    // ------------------------------------------------------------ Événements

    public function test_un_admin_cree_modifie_et_supprime_un_evenement(): void
    {
        $token = $this->adminToken();

        $event = $this->withToken($token)->postJson('/api/admin/events', [
            'title' => 'Café des entrepreneurs',
            'category' => 'Networking',
            'starts_at' => now()->addDays(10)->toDateTimeString(),
            'location' => 'Abidjan, Cocody',
            'time_label' => '9 h – 11 h',
        ])->assertOk()->json('event');

        $this->assertSame('cafe-des-entrepreneurs', $event['slug']);

        // L'événement apparaît sur la vitrine (endpoint public).
        $this->getJson('/api/public-events')->assertOk()
            ->assertJsonPath('events.0.title', 'Café des entrepreneurs');

        // Modification : le slug reste stable même si le titre change.
        $this->withToken($token)->putJson("/api/admin/events/{$event['id']}", [
            'title' => 'Café des entrepreneurs — édition 2',
            'category' => 'Networking',
            'starts_at' => now()->addDays(12)->toDateTimeString(),
        ])->assertOk();
        $this->assertSame('cafe-des-entrepreneurs', Event::find($event['id'])->slug);

        $this->withToken($token)->deleteJson("/api/admin/events/{$event['id']}")->assertOk();
        $this->assertNull(Event::find($event['id']));
    }

    public function test_deux_evenements_au_meme_titre_ont_des_slugs_distincts(): void
    {
        $token = $this->adminToken();
        $payload = [
            'title' => 'Atelier pitch',
            'category' => 'Atelier',
            'starts_at' => now()->addDays(5)->toDateTimeString(),
        ];

        $this->withToken($token)->postJson('/api/admin/events', $payload)->assertOk();
        $second = $this->withToken($token)->postJson('/api/admin/events', $payload)->assertOk()->json('event');

        $this->assertSame('atelier-pitch-2', $second['slug']);
    }

    public function test_un_membre_peut_s_inscrire_a_un_evenement_cree_par_l_admin(): void
    {
        $event = $this->withToken($this->adminToken())->postJson('/api/admin/events', [
            'title' => 'Masterclass finance',
            'category' => 'Masterclass',
            'starts_at' => now()->addDays(7)->toDateTimeString(),
        ])->json('event');

        $memberToken = $this->tokenFor(User::factory()->create());

        $this->withToken($memberToken)->postJson("/api/events/{$event['id']}/register")
            ->assertOk()->assertJsonPath('registered', true)->assertJsonPath('attendees_count', 1);
    }

    // ------------------------------------------------------------ Actualités

    public function test_un_admin_publie_un_article_visible_sur_la_vitrine(): void
    {
        $token = $this->adminToken();

        $article = $this->withToken($token)->postJson('/api/admin/news', [
            'title' => 'Le REJCC lance son incubateur',
            'category' => 'Réseau',
            'excerpt' => 'Une nouvelle étape pour les porteurs de projets du réseau.',
            'body' => "Premier paragraphe de l'annonce officielle.\n\nDeuxième paragraphe avec les détails pratiques.",
        ])->assertOk()->json('article');

        $this->assertSame('le-rejcc-lance-son-incubateur', $article['slug']);
        $this->assertCount(2, $article['body']);
        $this->assertNotEmpty($article['reading_time']);

        // Visible immédiatement sur l'endpoint public utilisé par la vitrine.
        $this->getJson("/api/news/{$article['slug']}")->assertOk()
            ->assertJsonPath('article.title', 'Le REJCC lance son incubateur');
    }

    public function test_un_admin_modifie_et_supprime_un_article(): void
    {
        $token = $this->adminToken();
        $id = $this->withToken($token)->postJson('/api/admin/news', [
            'title' => 'Brouillon',
            'category' => 'Réseau',
            'excerpt' => 'Un extrait suffisamment long pour la validation.',
            'body' => 'Un corps de texte suffisamment long pour être valide.',
        ])->json('article.id');

        $this->withToken($token)->putJson("/api/admin/news/{$id}", [
            'title' => 'Titre corrigé',
            'category' => 'Réseau',
            'excerpt' => 'Un extrait suffisamment long pour la validation.',
            'body' => 'Un corps de texte suffisamment long pour être valide.',
        ])->assertOk();
        $this->assertSame('Titre corrigé', NewsArticle::find($id)->title);

        $this->withToken($token)->deleteJson("/api/admin/news/{$id}")->assertOk();
        $this->assertNull(NewsArticle::find($id));
    }

    // ------------------------------------------------------------ Opportunités

    public function test_un_admin_modere_les_annonces_des_membres(): void
    {
        $member = User::factory()->create();
        $opp = Opportunity::create([
            'title' => 'Recherche développeur',
            'description' => 'Startup du réseau cherche un développeur web junior.',
            'type' => 'emploi',
            'author_id' => $member->id,
        ]);

        $token = $this->adminToken();

        $this->withToken($token)->putJson("/api/admin/opportunities/{$opp->id}", [
            'title' => 'Recherche développeur web',
            'description' => $opp->description,
            'type' => 'emploi',
            'entreprise' => 'Ivoire Tech',
            'site_url' => 'https://ivoire-tech.ci',
            'lieu' => 'Abidjan, Cocody',
        ])->assertOk();

        $fresh = $opp->fresh();
        $this->assertSame('Recherche développeur web', $fresh->title);
        $this->assertSame('Ivoire Tech', $fresh->entreprise);
        $this->assertSame('Abidjan, Cocody', $fresh->lieu);

        // L'offre enrichie ressort sur l'endpoint listé des membres.
        $listed = $this->withToken($this->tokenFor($member))->getJson('/api/opportunities')
            ->assertOk()->json('opportunities.0');
        $this->assertSame('https://ivoire-tech.ci', $listed['site_url']);
        $this->assertSame('Ivoire Tech', $listed['entreprise']);

        // Un lien de site invalide est refusé.
        $this->withToken($token)->putJson("/api/admin/opportunities/{$opp->id}", [
            'title' => 'Recherche développeur web',
            'description' => $opp->description,
            'type' => 'emploi',
            'site_url' => 'pas-une-url',
        ])->assertStatus(422);

        $this->withToken($token)->deleteJson("/api/admin/opportunities/{$opp->id}")->assertOk();
        $this->assertNull(Opportunity::find($opp->id));
    }

    // ------------------------------------------------------------ Cloisonnement

    public function test_un_membre_ne_peut_pas_gerer_le_contenu(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $this->withToken($token)->postJson('/api/admin/events', [])->assertStatus(403);
        $this->withToken($token)->postJson('/api/admin/news', [])->assertStatus(403);
        $this->withToken($token)->deleteJson('/api/admin/opportunities/1')->assertStatus(403);
    }
}

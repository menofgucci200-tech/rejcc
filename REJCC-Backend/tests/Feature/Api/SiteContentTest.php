<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Sector;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $plain = Str::random(60);
        ApiToken::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'token' => hash('sha256', $plain),
            'name' => 'test',
        ]);

        return $plain;
    }

    public function test_l_admin_gere_les_temoignages_du_site(): void
    {
        $token = $this->adminToken();

        // Création : les initiales sont dérivées du nom.
        $item = $this->withToken($token)->postJson('/api/admin/site-content/testimonials', [
            'name' => 'Marie Aka',
            'role' => 'Fondatrice, AgriPlus',
            'quote' => 'Le réseau a transformé mon projet en entreprise viable.',
        ])->assertOk()->json('item');

        $this->assertSame('MA', $item['initials']);

        // Modification.
        $this->withToken($token)->putJson("/api/admin/site-content/testimonials/{$item['id']}", [
            'name' => 'Marie Aka',
            'role' => 'CEO, AgriPlus',
            'quote' => 'Le réseau a transformé mon projet en entreprise viable.',
        ])->assertOk();
        $this->assertSame('CEO, AgriPlus', Testimonial::find($item['id'])->role);

        // Le témoignage est visible sur l'endpoint public de la vitrine.
        $this->getJson('/api/testimonials')->assertOk()->assertJsonPath('testimonials.0.name', 'Marie Aka');

        // Suppression.
        $this->withToken($token)->deleteJson("/api/admin/site-content/testimonials/{$item['id']}")->assertOk();
        $this->assertNull(Testimonial::find($item['id']));
    }

    public function test_l_admin_modifie_un_secteur_avec_ses_filieres(): void
    {
        $token = $this->adminToken();

        $item = $this->withToken($token)->postJson('/api/admin/site-content/sectors', [
            'icon' => 'sprout',
            'title' => 'Agriculture & Agro',
            'blurb' => 'Nourrir l\'avenir, de la terre à l\'assiette.',
            'items' => ['Agriculture', 'Élevage', 'Pêche'],
        ])->assertOk()->json('item');

        $this->assertSame(['Agriculture', 'Élevage', 'Pêche'], Sector::find($item['id'])->items);
    }

    public function test_l_admin_gere_la_galerie_photos(): void
    {
        $token = $this->adminToken();

        $item = $this->withToken($token)->postJson('/api/admin/site-content/gallery', [
            'url' => 'https://rejcc.site/uploads/bibliotheque/photo.jpg',
            'caption' => 'Rencontre annuelle 2026',
        ])->assertOk()->json('item');

        // La photo est visible sur l'endpoint public de la vitrine.
        $this->getJson('/api/gallery')->assertOk()
            ->assertJsonPath('photos.0.caption', 'Rencontre annuelle 2026');

        // Une URL invalide est refusée.
        $this->withToken($token)->postJson('/api/admin/site-content/gallery', [
            'url' => 'pas-une-url',
        ])->assertStatus(422);

        // Suppression.
        $this->withToken($token)->deleteJson("/api/admin/site-content/gallery/{$item['id']}")->assertOk();
        $this->getJson('/api/gallery')->assertOk()->assertJsonCount(0, 'photos');
    }

    public function test_un_type_inconnu_renvoie_404_et_un_membre_403(): void
    {
        $this->withToken($this->adminToken())->getJson('/api/admin/site-content/inconnu')->assertStatus(404);

        $memberPlain = Str::random(60);
        ApiToken::create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
            'token' => hash('sha256', $memberPlain),
            'name' => 'test',
        ]);
        $this->withToken($memberPlain)->getJson('/api/admin/site-content/testimonials')->assertStatus(403);
    }

    public function test_les_stats_du_dashboard_exposent_les_vraies_donnees(): void
    {
        $stats = $this->withToken($this->adminToken())->getJson('/api/admin/stats')
            ->assertOk()->json('stats');

        $this->assertArrayHasKey('formations', $stats);
        $this->assertArrayHasKey('certificats', $stats);
        $this->assertArrayHasKey('fonds_incubateur', $stats);
        $this->assertCount(12, $stats['croissance']);
    }
}

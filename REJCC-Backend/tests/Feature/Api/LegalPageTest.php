<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_les_six_pages_existent_et_restent_vides_tant_qu_elles_ne_sont_pas_publiees(): void
    {
        $pages = $this->getJson('/api/legal-pages')->assertOk()->json('pages');
        $this->assertSame(['mentions-legales', 'cgu', 'politique-de-confidentialite', 'politique-cookies', 'charte-du-membre', 'conditions-abonnement'], array_column($pages, 'slug'));
        $this->assertFalse($pages[1]['publie']);

        $this->getJson('/api/legal-pages/cgu')->assertOk()->assertJsonPath('page.contenu', null);
        $this->getJson('/api/legal-pages/inconnue')->assertStatus(404);
    }

    public function test_l_admin_redige_publie_et_versionne_une_page(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['contenu']]));

        // Brouillon : invisible du public.
        $this->withToken($token)->putJson('/api/admin/legal-pages/cgu', ['titre' => "Conditions générales d'utilisation", 'contenu' => "## Objet\nTexte provisoire."])->assertOk();
        $this->getJson('/api/legal-pages/cgu')->assertJsonPath('page.contenu', null);

        // Publication impossible sans contenu.
        $this->withToken($token)->putJson('/api/admin/legal-pages/charte-du-membre', ['titre' => 'Charte du membre', 'publier' => true])->assertStatus(422);

        $this->withToken($token)->putJson('/api/admin/legal-pages/cgu', ['titre' => "Conditions générales d'utilisation", 'contenu' => "## Objet\nTexte v1.", 'publier' => true])
            ->assertOk()->assertJsonPath('version', '1.0');
        $this->getJson('/api/legal-pages/cgu')->assertJsonPath('page.contenu', "## Objet\nTexte v1.")->assertJsonPath('page.version', '1.0');

        $this->withToken($token)->putJson('/api/admin/legal-pages/cgu', ['titre' => "Conditions générales d'utilisation", 'contenu' => "## Objet\nTexte v2.", 'publier' => true])
            ->assertJsonPath('version', '1.1');

        // Un membre ne peut pas modifier les pages.
        $this->withToken($this->tokenFor(User::factory()->create()))->putJson('/api/admin/legal-pages/cgu', ['titre' => 'Piratage'])->assertStatus(403);
    }
}

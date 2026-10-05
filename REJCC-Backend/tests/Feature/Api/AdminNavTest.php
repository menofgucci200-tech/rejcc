<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Contact;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminNavTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_a_traiter_respecte_les_permissions(): void
    {
        Contact::create(['nom' => 'Jean', 'email' => 'jean@example.com', 'sujet' => 'Question', 'message' => 'Bonjour', 'traite' => false]);

        $complet = $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin'])))->getJson('/api/admin/a-traiter')->assertOk()->json();
        $contacts = collect($complet['elements'])->firstWhere('cle', 'contacts');
        $this->assertSame(1, $contacts['nombre']);
        $this->assertGreaterThanOrEqual(1, $complet['total']);

        $restreint = $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['formations']])))->getJson('/api/admin/a-traiter')->json();
        $this->assertSame([], $restreint['elements']);
        $this->assertSame(0, $restreint['total']);

        $this->withToken($this->tokenFor(User::factory()->create()))->getJson('/api/admin/a-traiter')->assertStatus(403);
    }

    public function test_la_recherche_admin_trouve_comptes_et_formations_selon_les_droits(): void
    {
        User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré', 'email' => 'awa@example.com']);
        Formation::create(['title' => 'Gérer sa trésorerie', 'category' => 'Finance', 'modules_count' => 1]);

        $r = $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin'])))->getJson('/api/admin/recherche?q=awa traoré')->json('groupes');
        $this->assertSame('Awa Traoré', $r['Comptes'][0]['titre']);

        $r = $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['formations']])))->getJson('/api/admin/recherche?q=tréso')->json('groupes');
        $this->assertArrayNotHasKey('Comptes', $r);
        $this->assertSame('Gérer sa trésorerie', $r['Formations'][0]['titre']);
    }
}

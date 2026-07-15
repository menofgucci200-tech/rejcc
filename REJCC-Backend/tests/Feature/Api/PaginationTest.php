<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_l_annuaire_membre_est_pagine_et_exclut_soi_meme(): void
    {
        $me = User::factory()->create(['role' => 'member']);
        User::factory()->count(30)->create(['role' => 'member']);
        $token = $this->tokenFor($me);

        $res = $this->withToken($token)->getJson('/api/members')->assertOk()->json();

        $this->assertCount(24, $res['members']);      // per_page = 24
        $this->assertSame(30, $res['meta']['total']); // 30 autres membres (soi exclu)
        $this->assertSame(2, $res['meta']['last_page']);

        $res2 = $this->withToken($token)->getJson('/api/members?page=2')->json();
        $this->assertCount(6, $res2['members']);

        // Soi-même n'apparaît sur aucune page.
        $ids = collect($res['members'])->pluck('id')->merge(collect($res2['members'])->pluck('id'));
        $this->assertFalse($ids->contains($me->id));
    }

    public function test_l_annuaire_filtre_par_recherche_et_par_profil(): void
    {
        $me = User::factory()->create(['role' => 'member']);
        User::factory()->create(['role' => 'member', 'prenom' => 'Zacharie', 'nom' => 'Konan', 'secteur' => 'Agriculture', 'profil' => 'entrepreneur']);
        User::factory()->count(5)->create(['role' => 'member', 'profil' => 'etudiant']);
        $token = $this->tokenFor($me);

        $parNom = $this->withToken($token)->getJson('/api/members?q=Zacharie')->json();
        $this->assertSame(1, $parNom['meta']['total']);
        $this->assertSame('Zacharie', $parNom['members'][0]['prenom']);

        $parProfil = $this->withToken($token)->getJson('/api/members?profil=etudiant')->json();
        $this->assertSame(5, $parProfil['meta']['total']);
    }

    public function test_liste_admin_membres_paginee_filtree_et_triee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(35)->create(['role' => 'member']);
        User::factory()->count(2)->create(['role' => 'mentor']);
        $token = $this->tokenFor($admin);

        $res = $this->withToken($token)->getJson('/api/admin/members')->assertOk()->json();
        $this->assertCount(30, $res['members']);       // per_page = 30
        $this->assertSame(38, $res['meta']['total']);   // 35 + 2 + l'admin
        // Tri : l'administrateur ressort en tête.
        $this->assertSame('admin', $res['members'][0]['role']);

        $mentors = $this->withToken($token)->getJson('/api/admin/members?role=mentor')->json();
        $this->assertSame(2, $mentors['meta']['total']);

        $recherche = $this->withToken($token)->getJson('/api/admin/members?q='.$admin->email)->json();
        $this->assertSame(1, $recherche['meta']['total']);
    }
}

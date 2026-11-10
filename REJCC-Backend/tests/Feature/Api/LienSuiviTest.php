<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\LienSuivi;
use App\Models\LienSuiviClic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LienSuiviTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function admin(?array $permissions = null): string
    {
        return $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => $permissions]));
    }

    private function lien(array $attributs = []): LienSuivi
    {
        return LienSuivi::create($attributs + [
            'code' => 'gala-wa', 'cible' => 'https://rejcc.site/evenements/gala', 'libelle' => 'Gala – WhatsApp', 'canal' => 'whatsapp',
        ]);
    }

    public function test_rubrique_reservee_a_la_communication(): void
    {
        $this->withToken($this->admin(['formations']))->getJson('/api/admin/liens-suivi')->assertStatus(403);
        $this->withToken($this->admin(['formations']))->postJson('/api/admin/liens-suivi', [])->assertStatus(403);
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'member'])))->getJson('/api/admin/liens-suivi')->assertStatus(403);
        $this->withToken($this->admin(['communication']))->getJson('/api/admin/liens-suivi')->assertOk()->assertJsonPath('canaux.email', 'E-mail');
    }

    public function test_creer_modifier_desactiver_et_supprimer_un_lien(): void
    {
        $token = $this->admin(['communication']);

        $r = $this->withToken($token)->postJson('/api/admin/liens-suivi', [
            'code' => ' Gala-FB ', 'cible' => 'https://rejcc.site/evenements/gala', 'libelle' => 'Gala – Facebook', 'canal' => 'facebook',
        ])->assertCreated();
        $this->assertSame('gala-fb', $r->json('lien.code'));
        $this->assertTrue($r->json('lien.actif'));
        $id = $r->json('lien.id');
        $this->assertNotNull(LienSuivi::find($id)->cree_par);

        // Code déjà pris (la casse ne compte pas).
        $this->withToken($token)->postJson('/api/admin/liens-suivi', [
            'code' => 'GALA-FB', 'cible' => 'https://rejcc.site', 'libelle' => 'Doublon', 'canal' => 'facebook',
        ])->assertStatus(422)->assertJsonPath('message', 'Ce code est déjà utilisé par un autre lien.');

        $this->withToken($token)->putJson("/api/admin/liens-suivi/{$id}", ['libelle' => 'Gala – page Facebook', 'code' => 'gala-fb'])->assertOk()
            ->assertJsonPath('lien.libelle', 'Gala – page Facebook');
        $this->withToken($token)->putJson("/api/admin/liens-suivi/{$id}", ['actif' => false])->assertOk()->assertJsonPath('lien.actif', false);

        $this->withToken($token)->deleteJson("/api/admin/liens-suivi/{$id}")->assertOk();
        $this->assertNull(LienSuivi::find($id));
    }

    public function test_cible_invalide_refusee(): void
    {
        $token = $this->admin(['communication']);
        foreach (['javascript:alert(1)', 'ftp://exemple.com/fichier', 'rejcc.site/sans-protocole', '//evil.example'] as $cible) {
            $this->withToken($token)->postJson('/api/admin/liens-suivi', [
                'code' => 'essai', 'cible' => $cible, 'libelle' => 'Essai', 'canal' => 'autre',
            ])->assertStatus(422);
        }
        // Code avec des caractères interdits, canal inconnu.
        $this->withToken($token)->postJson('/api/admin/liens-suivi', ['code' => 'a/b', 'cible' => 'https://rejcc.site', 'libelle' => 'X', 'canal' => 'autre'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/admin/liens-suivi', ['code' => 'abc', 'cible' => 'https://rejcc.site', 'libelle' => 'X', 'canal' => 'pigeon'])->assertStatus(422);
        $this->assertSame(0, LienSuivi::count());
    }

    public function test_ouverture_publique_compte_les_clics_par_jour(): void
    {
        $lien = $this->lien();

        $this->getJson('/api/liens/gala-wa')->assertOk()->assertJsonPath('cible', 'https://rejcc.site/evenements/gala');
        $this->getJson('/api/liens/GALA-WA')->assertOk();
        // Robot d'aperçu : cible donnée, clic non compté.
        $this->getJson('/api/liens/gala-wa?compter=0')->assertOk();
        $this->assertSame(2, (int) LienSuiviClic::where('lien_id', $lien->id)->where('jour', now()->toDateString())->value('clics'));
        $this->assertSame(1, LienSuiviClic::count());

        $this->travel(1)->days();
        $this->getJson('/api/liens/gala-wa')->assertOk();
        $this->assertSame(2, LienSuiviClic::count());

        $r = $this->withToken($this->admin(['communication']))->getJson('/api/admin/liens-suivi')->assertOk();
        $this->assertSame(3, $r->json('liens.0.clics_total'));
        $this->assertSame(3, $r->json('liens.0.clics_30j'));
        $this->assertSame('whatsapp', $r->json('par_canal.0.canal'));
        $this->assertSame(3, $r->json('par_canal.0.clics_total'));

        $s = $this->withToken($this->admin(['communication']))->getJson('/api/admin/liens-suivi/statistiques?mois='.now()->format('Y-m'))->assertOk();
        $this->assertSame(1, $s->json('par_jour.'.now()->toDateString()));
        $this->assertSame('gala-wa', $s->json('par_lien.0.code'));
        // Période libre (30 derniers jours du tableau de bord).
        $p = $this->withToken($this->admin(['communication']))->getJson('/api/admin/liens-suivi/statistiques?du='.now()->subDays(29)->toDateString().'&au='.now()->toDateString())->assertOk();
        $this->assertSame(3, $p->json('total'));
        $this->assertCount(30, $p->json('par_jour'));
        $this->assertSame('WhatsApp', $p->json('par_canal.0.label'));
    }

    public function test_lien_inconnu_ou_inactif_introuvable(): void
    {
        $this->lien(['actif' => false]);
        $this->getJson('/api/liens/gala-wa')->assertNotFound();
        $this->getJson('/api/liens/inconnu')->assertNotFound();
        $this->assertSame(0, LienSuiviClic::count());
    }

    public function test_liens_des_testeurs_effaces_par_le_nettoyage(): void
    {
        $testeur = User::factory()->create(['role' => 'admin', 'departement' => 'communication']);
        $this->lien(['cree_par' => $testeur->id])->compterClic();
        $this->lien(['code' => 'test-lib', 'libelle' => '[TEST] Affiche']);
        $garde = $this->lien(['code' => 'vrai', 'libelle' => 'Vrai lien']);

        $this->artisan('test:nettoyer', ['--force' => true])->assertSuccessful();

        $this->assertSame([$garde->id], LienSuivi::pluck('id')->all());
        $this->assertSame(0, LienSuiviClic::count());
    }

    public function test_un_compte_de_test_ne_modifie_pas_un_vrai_lien(): void
    {
        $reel = $this->lien();
        $testeur = User::factory()->create(['role' => 'admin', 'departement' => 'Communication', 'permissions' => ['communication']]);
        $token = $this->tokenFor($testeur);

        $this->withToken($token)->putJson("/api/admin/liens-suivi/{$reel->id}", ['cible' => 'https://exemple.com'])->assertForbidden();
        $this->withToken($token)->deleteJson("/api/admin/liens-suivi/{$reel->id}")->assertForbidden();
        $this->assertSame('https://rejcc.site/evenements/gala', $reel->fresh()->cible);

        $id = $this->withToken($token)->postJson('/api/admin/liens-suivi', ['code' => 'test-essai', 'cible' => 'https://rejcc.site', 'libelle' => '[TEST] Essai', 'canal' => 'autre'])
            ->assertCreated()->json('lien.id');
        $this->withToken($token)->putJson("/api/admin/liens-suivi/{$id}", ['actif' => false])->assertOk();
    }
}

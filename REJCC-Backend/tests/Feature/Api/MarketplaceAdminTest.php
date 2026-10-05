<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MarketplaceListing;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceAdminTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function annonce(string $statut, string $titre = 'Jus de bissap'): MarketplaceListing
    {
        $v = User::factory()->create(['prenom' => 'Awa', 'email' => 'awa'.Str::random(4).'@exemple.ci', 'subscription_expires_at' => now()->addYear()]);

        return MarketplaceListing::create(['user_id' => $v->id, 'type' => 'produit', 'title' => $titre, 'category' => 'x', 'group_id' => 12,
            'description' => 'Jus naturels faits maison, livraison à Cocody.', 'statut' => $statut, 'publie_le' => now(), 'expire_le' => now()->addDays(90)]);
    }

    public function test_filtres_recherche_et_compteurs(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $this->annonce('en_attente');
        $enLigne = $this->annonce('approuve', 'Cours de couture');
        DB::table('listing_reports')->insert(['listing_id' => $enLigne->id, 'reporter_id' => User::factory()->create()->id, 'motif' => 'Arnaque', 'statut' => 'nouveau', 'created_at' => now(), 'updated_at' => now()]);

        $res = $this->withToken($admin)->getJson('/api/admin/marketplace')->assertOk()->json();
        $this->assertSame(['Jus de bissap'], array_column($res['listings'], 'title'));
        $this->assertSame(1, $res['compteurs']['signalees']);
        $this->assertSame(2, $res['compteurs']['tous']);

        $signalees = $this->withToken($admin)->getJson('/api/admin/marketplace?filtre=signalees')->json('listings');
        $this->assertSame('Arnaque', $signalees[0]['signalements'][0]['motif']);
        $this->assertSame(['Cours de couture'], array_column($this->withToken($admin)->getJson('/api/admin/marketplace?filtre=tous&q=couture')->json('listings'), 'title'));
        $this->withToken($admin)->getJson('/api/admin/a-traiter')->assertJsonFragment(['cle' => 'annonces_signalees', 'nombre' => 1]);
    }

    public function test_refus_avec_motif_obligatoire_correction_et_retrait_notifies(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $l = $this->annonce('en_attente');

        $this->withToken($admin)->putJson("/api/admin/marketplace/{$l->id}/reject")->assertStatus(422);
        $this->withToken($admin)->putJson("/api/admin/marketplace/{$l->id}/reject", ['motif' => 'Photo floue, merci d\'en ajouter une nette.'])->assertOk();
        $this->assertSame('refuse', $l->fresh()->statut);

        $this->withToken($admin)->putJson("/api/admin/marketplace/{$l->id}", [
            'type' => 'produit', 'title' => 'Jus de bissap artisanal', 'group_id' => 1,
            'description' => 'Jus naturels faits maison, livraison à Cocody.', 'price' => '1000', 'note' => 'Catégorie ajustée.',
        ])->assertOk();
        $this->assertSame('Agriculture & Pêche', $l->fresh()->category);
        $this->assertSame(1, MemberNotification::where('user_id', $l->user_id)->where('title', 'Votre annonce a été corrigée')->count());

        $l->update(['statut' => 'approuve']);
        DB::table('listing_reports')->insert(['listing_id' => $l->id, 'reporter_id' => User::factory()->create()->id, 'statut' => 'nouveau', 'created_at' => now(), 'updated_at' => now()]);
        $this->withToken($admin)->putJson("/api/admin/marketplace/{$l->id}/retirer", ['motif' => ''])->assertStatus(422);
        $this->withToken($admin)->putJson("/api/admin/marketplace/{$l->id}/retirer", ['motif' => 'Produit non conforme à la charte.'])->assertOk();
        $this->assertSame('retiree', $l->fresh()->statut);
        $this->assertSame(0, DB::table('listing_reports')->where('statut', 'nouveau')->count());
        $this->assertStringContainsString('non conforme', MemberNotification::where('user_id', $l->user_id)->latest('id')->value('body'));

        // Le vendeur corrige l'annonce retirée : elle repasse en validation.
        $this->withToken($this->tokenFor($l->user))->putJson("/api/marketplace/{$l->id}", [
            'type' => 'produit', 'title' => 'Jus de bissap artisanal', 'group_id' => 1, 'description' => 'Jus naturels faits maison, conformes.',
        ])->assertOk()->assertJsonPath('revalidation', true);
        $this->assertSame('en_attente', $l->fresh()->statut);

        $this->withToken($admin)->deleteJson("/api/admin/marketplace/{$l->id}")->assertOk();
        $this->assertSame(1, MemberNotification::where('user_id', $l->user_id)->where('title', 'Annonce supprimée')->count());
    }

    public function test_classer_les_signalements(): void
    {
        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin']));
        $l = $this->annonce('approuve');
        DB::table('listing_reports')->insert(['listing_id' => $l->id, 'reporter_id' => User::factory()->create()->id, 'statut' => 'nouveau', 'created_at' => now(), 'updated_at' => now()]);
        $this->withToken($admin)->putJson("/api/admin/marketplace/{$l->id}/signalements")->assertOk();
        $this->assertSame('approuve', $l->fresh()->statut);
        $this->assertSame(0, DB::table('listing_reports')->where('statut', 'nouveau')->count());
    }
}

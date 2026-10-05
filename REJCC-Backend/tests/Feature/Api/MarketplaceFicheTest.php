<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MarketplaceListing;
use App\Models\MemberNotification;
use App\Models\MemberReview;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceFicheTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function annonce(User $vendeur, string $statut = 'approuve'): MarketplaceListing
    {
        return MarketplaceListing::create(['user_id' => $vendeur->id, 'type' => 'produit', 'title' => 'Jus de bissap', 'category' => 'Autre',
            'description' => 'Jus naturels faits maison, livraison à Cocody.', 'contact' => '0708091011', 'statut' => $statut]);
    }

    public function test_fiche_complete_vues_et_telephone_reserve_aux_abonnes(): void
    {
        SubscriptionMode::set(true);
        $vendeur = User::factory()->create(['prenom' => 'Awa', 'titre' => 'Productrice', 'subscription_expires_at' => now()->addYear()]);
        $vendeur->groups()->attach(1, ['specialite' => 'Transformation de produits vivriers']);
        MemberReview::create(['reviewer_id' => User::factory()->create()->id, 'reviewed_id' => $vendeur->id, 'note' => 4]);
        $l = $this->annonce($vendeur);

        $abonne = $this->tokenFor(User::factory()->create(['subscription_expires_at' => now()->addYear()]));
        $nonAbonne = $this->tokenFor(User::factory()->create());

        $fiche = $this->withToken($abonne)->getJson("/api/marketplace/{$l->id}")->assertOk()->json('listing');
        $this->assertSame('0708091011', $fiche['contact']);
        $this->assertSame('Productrice', $fiche['seller']['titre']);
        $this->assertSame(4.0, (float) $fiche['seller']['avis']['moyenne']);
        $this->assertSame('Agriculture & Pêche', $fiche['seller']['groupes'][0]['nom']);
        $this->assertArrayNotHasKey('vues', $fiche);

        $this->withToken($nonAbonne)->getJson("/api/marketplace/{$l->id}")->assertOk()->assertJsonPath('listing.contact', null);

        // Une vue par membre et par jour ; le vendeur voit ses statistiques.
        $this->withToken($abonne)->getJson("/api/marketplace/{$l->id}");
        $stats = $this->withToken($this->tokenFor($vendeur))->getJson("/api/marketplace/{$l->id}")->json('listing');
        $this->assertSame(2, $stats['vues']);
        $this->assertTrue($stats['est_vendeur']);

        // Annonce en attente : invisible pour les autres.
        $attente = $this->annonce($vendeur, 'en_attente');
        $this->withToken($abonne)->getJson("/api/marketplace/{$attente->id}")->assertStatus(404);
    }

    public function test_contact_par_messagerie_avec_annonce_rattachee(): void
    {
        $vendeur = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $l = $this->annonce($vendeur);
        $acheteur = User::factory()->create(['prenom' => 'Koffi', 'nom' => 'Yao', 'subscription_expires_at' => now()->addYear()]);
        $t = $this->tokenFor($acheteur);

        $this->withToken($t)->postJson('/api/messages', ['recipient_id' => $vendeur->id, 'body' => 'Bonjour, il en reste ?', 'listing_id' => $l->id])->assertOk();
        $this->withToken($t)->postJson('/api/messages', ['recipient_id' => $vendeur->id, 'body' => 'Pour samedi.', 'listing_id' => $l->id])->assertOk();

        $this->assertSame(1, $l->fresh()->contacts); // un contact par membre intéressé
        $fil = $this->withToken($this->tokenFor($vendeur))->getJson("/api/messages/{$acheteur->id}")->json('messages');
        $this->assertSame('Jus de bissap', $fil[0]['annonce']['title']);
        $this->assertStringStartsWith('À propos de votre annonce « Jus de bissap »', MemberNotification::where('user_id', $vendeur->id)->latest('id')->value('body'));
    }

    public function test_signaler_une_annonce(): void
    {
        $vendeur = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $l = $this->annonce($vendeur);
        $this->withToken($this->tokenFor($vendeur))->postJson("/api/marketplace/{$l->id}/signaler")->assertStatus(422);

        $t = $this->tokenFor(User::factory()->create());
        $this->withToken($t)->postJson("/api/marketplace/{$l->id}/signaler", ['motif' => 'Arnaque'])->assertOk();
        $this->withToken($t)->postJson("/api/marketplace/{$l->id}/signaler", ['motif' => 'Arnaque (bis)'])->assertOk();
        $this->assertSame(1, DB::table('listing_reports')->count());
        $this->withToken($t)->getJson("/api/marketplace/{$l->id}")->assertJsonPath('listing.deja_signalee', true);
    }
}

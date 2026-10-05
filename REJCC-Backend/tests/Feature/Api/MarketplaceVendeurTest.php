<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MarketplaceListing;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceVendeurTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function donnees(array $extra = []): array
    {
        return $extra + ['type' => 'produit', 'title' => 'Jus de bissap', 'category' => 'Autre',
            'description' => 'Jus naturels faits maison, livraison à Cocody.', 'price' => '1000', 'group_id' => 12];
    }

    private function enLigne(User $vendeur): MarketplaceListing
    {
        $id = $this->withToken($this->tokenFor($vendeur))->postJson('/api/marketplace', $this->donnees())->assertOk()->json('listing.id');
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin'])))->putJson("/api/admin/marketplace/{$id}/approve")->assertOk();

        return MarketplaceListing::find($id);
    }

    public function test_publication_pour_90_jours(): void
    {
        $l = $this->enLigne($v = User::factory()->create(['subscription_expires_at' => now()->addYear()]));
        $this->assertSame('approuve', $l->statut);
        $this->assertTrue($l->expire_le->between(now()->addDays(89), now()->addDays(91)));
        $this->assertSame("/espace-membre/marketplace?annonce={$l->id}", MemberNotification::where('user_id', $v->id)->latest('id')->value('link'));
    }

    public function test_modifier_prix_immediat_contenu_en_revalidation_et_refus_resoumis(): void
    {
        $v = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $t = $this->tokenFor($v);
        $l = $this->enLigne($v);

        $this->withToken($t)->putJson("/api/marketplace/{$l->id}", $this->donnees(['price' => '1500']))->assertOk()->assertJsonPath('revalidation', false);
        $this->assertSame('approuve', $l->fresh()->statut);
        $this->assertSame('1500', $l->fresh()->price);

        $this->withToken($t)->putJson("/api/marketplace/{$l->id}", $this->donnees(['title' => 'Jus de bissap bio']))->assertOk()->assertJsonPath('revalidation', true);
        $this->assertSame('en_attente', $l->fresh()->statut);

        $l->update(['statut' => 'refuse', 'reject_reason' => 'Photo floue']);
        $this->withToken($t)->putJson("/api/marketplace/{$l->id}", $this->donnees(['photo' => 'https://exemple.ci/p.jpg']))->assertOk();
        $this->assertSame('en_attente', $l->fresh()->statut);
        $this->assertNull($l->fresh()->reject_reason);

        // L'annonce d'un autre membre reste intouchable.
        $this->withToken($this->tokenFor(User::factory()->create(['subscription_expires_at' => now()->addYear()])))
            ->putJson("/api/marketplace/{$l->id}", $this->donnees())->assertStatus(404);
    }

    public function test_vendu_indisponible_puis_remise_en_ligne(): void
    {
        $v = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $t = $this->tokenFor($v);
        $l = $this->enLigne($v);
        $lecteur = $this->tokenFor(User::factory()->create());

        $this->withToken($t)->postJson("/api/marketplace/{$l->id}/disponibilite", ['disponible' => false])->assertOk()->assertJsonPath('statut', 'indisponible');
        $this->withToken($lecteur)->getJson('/api/marketplace')->assertJsonCount(0, 'listings');
        $this->withToken($t)->postJson("/api/marketplace/{$l->id}/disponibilite", ['disponible' => true])->assertOk();
        $this->withToken($lecteur)->getJson('/api/marketplace')->assertJsonCount(1, 'listings');
    }

    public function test_rappel_expiration_et_renouvellement(): void
    {
        $v = User::factory()->create(['subscription_expires_at' => now()->addYears(2)]);
        $t = $this->tokenFor($v);
        $l = $this->enLigne($v);
        $lecteur = $this->tokenFor(User::factory()->create());

        $this->travel(84)->days();
        $this->artisan('marketplace:echeances')->assertSuccessful();
        $this->assertSame(1, MemberNotification::where('user_id', $v->id)->where('title', 'Votre annonce expire bientôt')->count());
        $this->artisan('marketplace:echeances');
        $this->assertSame(1, MemberNotification::where('user_id', $v->id)->where('title', 'Votre annonce expire bientôt')->count());

        $this->travel(7)->days();
        // Jetons recréés : ceux du début ont expiré pendant ces 91 jours.
        $t = $this->tokenFor($v);
        $lecteur = $this->tokenFor(User::factory()->create());
        // Même sans la tâche quotidienne, l'annonce échue n'est plus au catalogue.
        $this->withToken($lecteur)->getJson('/api/marketplace')->assertJsonCount(0, 'listings');
        $mes = $this->withToken($t)->getJson('/api/marketplace/mine')->json('listings.0');
        $this->assertSame('expiree', $mes['statut']);
        $this->assertSame(1, MemberNotification::where('user_id', $v->id)->where('title', 'Annonce expirée')->count());

        $this->withToken($t)->postJson("/api/marketplace/{$l->id}/renouveler")->assertOk();
        $this->assertSame('approuve', $l->fresh()->statut);
        $this->withToken($lecteur)->getJson('/api/marketplace')->assertJsonCount(1, 'listings');
    }

    public function test_catalogue_recherche_filtres_tri_et_favoris(): void
    {
        $abidjan = User::factory()->create(['prenom' => 'Awa', 'ville' => 'Abidjan', 'subscription_expires_at' => now()->addYear()]);
        $bouake = User::factory()->create(['prenom' => 'Yao', 'ville' => 'Bouaké', 'subscription_expires_at' => now()->addYear()]);
        $creer = fn (User $u, string $titre, ?string $prix, int $groupe, string $type = 'service') => MarketplaceListing::create([
            'user_id' => $u->id, 'type' => $type, 'title' => $titre, 'category' => 'x', 'group_id' => $groupe, 'price' => $prix,
            'description' => 'Description suffisamment longue pour le test.', 'statut' => 'approuve', 'publie_le' => now(), 'expire_le' => now()->addDays(90),
        ]);
        $traiteur = $creer($abidjan, 'Traiteur pour mariages', '150 000 F', 12);
        $creer($abidjan, 'Plomberie à domicile', 'Sur devis', 8);
        $creer($bouake, 'Service traiteur et buffet', '50 000', 12);
        $creer($bouake, 'Attiéké frais', '1 000 F le kilo', 1, 'produit');

        $t = $this->tokenFor($lecteur = User::factory()->create());
        $titres = fn (string $qs) => array_column($this->withToken($t)->getJson('/api/marketplace?'.$qs)->assertOk()->json('listings'), 'title');

        $this->assertEqualsCanonicalizing(['Traiteur pour mariages', 'Service traiteur et buffet'], $titres('q='.urlencode('Je cherche un traiteur')));
        $this->assertSame(['Traiteur pour mariages'], $titres('q='.urlencode('traiteurs Abidjan')));
        $this->assertSame(['Attiéké frais'], $titres('type=produit'));
        $this->assertCount(2, $titres('groupe=12'));
        $this->assertCount(2, $titres('ville='.urlencode('Bouaké')));
        $this->assertSame(['Attiéké frais', 'Service traiteur et buffet', 'Traiteur pour mariages', 'Plomberie à domicile'], $titres('tri=prix_asc'));

        $res = $this->withToken($t)->getJson('/api/marketplace')->json();
        $this->assertCount(16, $res['categories']);
        $this->assertSame(['Abidjan', 'Bouaké'], $res['villes']);
        $this->assertSame('Hôtellerie & Tourisme', collect($res['listings'])->firstWhere('title', 'Traiteur pour mariages')['groupe']['nom']);

        $this->withToken($t)->postJson("/api/marketplace/{$traiteur->id}/favori")->assertOk()->assertJsonPath('favori', true);
        $this->assertSame(['Traiteur pour mariages'], $titres('favoris=1'));
        $this->withToken($t)->postJson("/api/marketplace/{$traiteur->id}/favori")->assertOk()->assertJsonPath('favori', false);
        $this->assertSame([], $titres('favoris=1'));
    }

    public function test_annonces_suspendues_quand_l_abonnement_du_vendeur_expire(): void
    {
        \App\Support\SubscriptionMode::set(true);
        $v = User::factory()->create(['subscription_expires_at' => now()->addDays(10)]);
        $l = $this->enLigne($v);
        $mentor = User::factory()->create(['role' => 'mentor']);
        MarketplaceListing::create(['user_id' => $mentor->id, 'type' => 'service', 'title' => 'Coaching', 'category' => 'x', 'group_id' => 5,
            'description' => 'Accompagnement des jeunes entrepreneurs.', 'statut' => 'approuve', 'publie_le' => now(), 'expire_le' => now()->addDays(90)]);

        $this->travel(11)->days();
        $lecteur = $this->tokenFor(User::factory()->create());
        // L'annonce du vendeur non à jour disparaît ; celle du mentor (dispensé) reste.
        $this->assertSame(['Coaching'], array_column($this->withToken($lecteur)->getJson('/api/marketplace')->json('listings'), 'title'));
        $this->withToken($lecteur)->getJson("/api/marketplace/{$l->id}")->assertStatus(404);

        $this->artisan('marketplace:echeances');
        $this->artisan('marketplace:echeances');
        $this->assertSame(1, MemberNotification::where('user_id', $v->id)->where('title', 'Vos annonces sont suspendues')->count());

        $t = $this->tokenFor($v);
        $mes = $this->withToken($t)->getJson('/api/marketplace/mine')->assertOk()->json();
        $this->assertTrue($mes['listings'][0]['suspendue']);
        $this->assertFalse($mes['abonne']);
        $this->withToken($t)->putJson("/api/marketplace/{$l->id}", $this->donnees())->assertStatus(402);

        // Renouvellement de l'abonnement : l'annonce revient automatiquement.
        $v->update(['subscription_expires_at' => now()->addYear()]);
        $this->assertCount(2, $this->withToken($lecteur)->getJson('/api/marketplace')->json('listings'));
        $this->artisan('marketplace:echeances');
        $this->assertNull($l->fresh()->suspension_notifiee_at);

        // Retirer son annonce reste possible sans abonnement.
        $v->update(['subscription_expires_at' => now()->subDay()]);
        $this->withToken($t)->deleteJson("/api/marketplace/{$l->id}")->assertOk();
    }
}

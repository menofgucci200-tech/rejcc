<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketplaceConfidentialiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_catalogue_ne_divulgue_pas_le_telephone_personnel_du_vendeur(): void
    {
        $vendeur = User::factory()->create(['telephone' => '0700000000', 'photo' => 'https://exemple.ci/p.jpg', 'role' => 'mentor']); // mentor : dispensé d'abonnement
        MarketplaceListing::create(['user_id' => $vendeur->id, 'type' => 'produit', 'title' => 'Jus de bissap', 'category' => 'Autre',
            'description' => 'Jus naturels faits maison, livraison à Cocody.', 'statut' => 'approuve']);

        $lecteur = User::factory()->create();
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $lecteur->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        $res = $this->withToken($plain)->getJson('/api/marketplace')->assertOk();
        $vendeurJson = $res->json('listings.0.seller');
        $this->assertArrayNotHasKey('telephone', $vendeurJson);
        $this->assertSame('https://exemple.ci/p.jpg', $vendeurJson['photo']);
        $this->assertSame('mentor', $vendeurJson['role']);
        $this->assertStringNotContainsString('0700000000', $res->getContent());
    }
}

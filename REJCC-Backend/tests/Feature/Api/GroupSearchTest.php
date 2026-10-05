<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberReview;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupSearchTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function pro(string $prenom, int $groupe, array $fiche, array $notes = []): User
    {
        $u = User::factory()->create(['prenom' => $prenom, 'role' => 'member', 'ville' => 'Abidjan']);
        $u->groups()->attach($groupe, ['services' => json_encode($fiche['services'] ?? [])] + $fiche);
        foreach ($notes as $n) {
            MemberReview::create(['reviewer_id' => User::factory()->create()->id, 'reviewed_id' => $u->id, 'note' => $n]);
        }

        return $u;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Groupe 8 : BTP & Construction.
        $this->pro('Esther', 8, ['specialite' => 'Plombière, dépannage sanitaire.', 'zone' => 'Cocody, Bingerville', 'services' => ['Pose de chauffe-eau', 'Détection de fuites']], [5, 5]);
        $this->pro('Yao', 8, ['specialite' => 'Plombier chauffagiste.', 'zone' => 'Yopougon', 'services' => ['Dépannage urgent']], [3]);
        $this->pro('Ines', 8, ['specialite' => 'Électricienne bâtiment.', 'zone' => 'Cocody', 'services' => ['Mise aux normes']]);
    }

    private function abonne(): string
    {
        return $this->tokenFor(User::factory()->create(['role' => 'member', 'subscription_expires_at' => now()->addYear()]));
    }

    public function test_recherche_en_langage_courant_dans_un_groupe(): void
    {
        $token = $this->abonne();

        // Mots vides ignorés, pluriel retiré, zone et spécialité combinées.
        $noms = fn ($q) => collect($this->withToken($token)->getJson('/api/groups/8/members?q='.urlencode($q))->assertOk()->json('members'))->pluck('prenom')->all();
        $this->assertSame(['Esther'], $noms('Je cherche des plombiers à Cocody'));
        // Services (JSON, accents échappés).
        $this->assertSame(['Esther'], $noms('détection'));
        $this->assertSame(['Esther'], $noms('chauffe-eau'));
        // Majuscule accentuée en base, minuscule dans la recherche.
        $this->assertSame(['Ines'], $noms('électricienne'));
    }

    public function test_tri_par_note_puis_sans_avis_en_dernier(): void
    {
        $membres = $this->withToken($this->abonne())->getJson('/api/groups/8/members?tri=note')->assertOk()->json('members');
        $this->assertSame(['Esther', 'Yao', 'Ines'], array_column($membres, 'prenom'));
        $this->assertSame(5.0, (float) $membres[0]['note_moyenne']);
        $this->assertNull($membres[2]['note_moyenne']);

        $parNom = $this->withToken($this->abonne())->getJson('/api/groups/8/members')->json('members');
        $this->assertSame(['Esther', 'Ines', 'Yao'], array_column($parNom, 'prenom'));
    }

    public function test_je_cherche_dans_tous_les_groupes(): void
    {
        $res = $this->withToken($this->abonne())->getJson('/api/groups/recherche?q='.urlencode('plombier'))->assertOk()->json();

        $this->assertFalse($res['verrouille']);
        $this->assertSame(2, $res['total']);
        $this->assertSame([['id' => 8, 'nom' => 'BTP & Construction', 'nombre' => 2]], $res['par_groupe']);
        // Les mieux notés d'abord, avec leur groupe.
        $this->assertSame(['Esther', 'Yao'], array_column($res['members'], 'prenom'));
        $this->assertSame('BTP & Construction', $res['members'][0]['groupe']['nom']);

        // Requête vide ou faite seulement de mots vides.
        $this->withToken($this->abonne())->getJson('/api/groups/recherche?q='.urlencode('je cherche un'))
            ->assertOk()->assertJsonPath('total', 0);
    }

    public function test_je_cherche_sans_abonnement_donne_seulement_les_nombres(): void
    {
        SubscriptionMode::set(true);
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $res = $this->withToken($token)->getJson('/api/groups/recherche?q=Cocody')->assertOk()->json();
        $this->assertTrue($res['verrouille']);
        $this->assertSame(2, $res['total']);
        $this->assertSame([], $res['members']);
    }

    public function test_apercu_d_un_groupe_sans_donnees_personnelles(): void
    {
        SubscriptionMode::set(true);
        $token = $this->tokenFor(User::factory()->create(['role' => 'member']));

        $this->withToken($token)->getJson('/api/groups/8/members')->assertStatus(402);

        $apercu = $this->withToken($token)->getJson('/api/groups/8/apercu')->assertOk()->json('apercu');
        $this->assertSame('BTP & Construction', $apercu['group']['name']);
        $this->assertSame(3, $apercu['membres']);
        $this->assertSame(1, $apercu['villes']);
        $this->assertSame(3, $apercu['avis']);
        $this->assertEqualsWithDelta(4.3, $apercu['note_moyenne'], 0.01);
        $this->assertContains('Pose de chauffe-eau', $apercu['services']);
        $this->assertNull($apercu['group']['whatsapp']);
        $this->assertStringNotContainsString('Esther', json_encode($apercu));
    }
}

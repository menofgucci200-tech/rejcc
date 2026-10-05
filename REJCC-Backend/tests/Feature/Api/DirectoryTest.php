<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function abonne(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['subscription_expires_at' => now()->addYear()]);
    }

    public function test_la_carte_annuaire_donne_titre_competences_et_nouveaute(): void
    {
        $this->abonne(['prenom' => 'Ancien', 'created_at' => now()->subMonths(3), 'titre' => 'Comptable', 'competences' => ['Excel', 'Fiscalité', 'Audit', 'Paie']]);
        $this->abonne(['prenom' => 'Nouveau', 'created_at' => now()->subDays(3)]);
        $token = $this->tokenFor($this->abonne());

        $membres = collect($this->withToken($token)->getJson('/api/members')->assertOk()->json('members'))->keyBy('prenom');
        $this->assertSame('Comptable', $membres['Ancien']['titre']);
        $this->assertSame(['Excel', 'Fiscalité', 'Audit'], $membres['Ancien']['competences']);
        $this->assertFalse($membres['Ancien']['nouveau']);
        $this->assertTrue($membres['Nouveau']['nouveau']);
    }

    public function test_recherche_elargie_filtres_et_tri(): void
    {
        $awa = $this->abonne(['prenom' => 'Awa', 'secteur' => 'Agro', 'ville' => 'Abidjan', 'competences' => ['Fiscalité', 'Export'], 'paroisse' => 'Saint-Jacques', 'created_at' => now()->subMonths(2)]);
        $this->abonne(['prenom' => 'Jean', 'secteur' => 'BTP', 'ville' => 'Bouaké', 'titre' => 'Architecte', 'bio' => 'Passionné de construction durable.', 'created_at' => now()->subDay()]);
        $groupe = \App\Models\Group::create(['name' => 'Agriculture', 'slug' => 'agriculture-test', 'ordre' => 99]);
        $awa->groups()->attach($groupe->id);
        $token = $this->tokenFor($this->abonne(['prenom' => 'Moi', 'created_at' => now()->subYear()]));

        $prenoms = fn (string $qs) => array_column($this->withToken($token)->getJson('/api/members'.$qs)->json('members'), 'prenom');

        $this->assertSame(['Awa'], $prenoms('?q=fiscalité'));       // compétence avec accent
        $this->assertSame(['Jean'], $prenoms('?q=architecte'));      // titre
        $this->assertSame(['Jean'], $prenoms('?q=durable'));         // biographie
        $this->assertSame(['Awa'], $prenoms('?q=jacques'));          // paroisse
        $this->assertSame(['Jean'], $prenoms('?secteur=BTP'));
        $this->assertSame(['Awa'], $prenoms('?ville=Abidjan'));
        $this->assertSame(['Awa'], $prenoms('?groupe='.$groupe->id));
        $this->assertSame(['Jean', 'Awa'], $prenoms('?tri=recents'));

        $filtres = $this->withToken($token)->getJson('/api/members')->json('filtres');
        $this->assertContains('BTP', $filtres['secteurs']);
        $this->assertContains('Bouaké', $filtres['villes']);
        $this->assertContains('Agriculture', array_column($filtres['groupes'], 'nom'));
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    private function memberToken(?User &$user = null): string
    {
        $user = User::factory()->create(['role' => 'member']);
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_les_16_groupes_sectoriels_sont_disponibles(): void
    {
        $groups = $this->withToken($this->memberToken())->getJson('/api/groups')
            ->assertOk()->json('groups');

        $this->assertCount(16, $groups);
        $this->assertSame('Agriculture & Pêche', $groups[0]['name']);
        $this->assertSame('Action Sociale & Solidarité', $groups[15]['name']);
        $this->assertSame(0, $groups[0]['members']);
        $this->assertFalse($groups[0]['joined']);
    }

    public function test_un_membre_rejoint_et_quitte_plusieurs_groupes(): void
    {
        $token = $this->memberToken($user);

        $specialite = 'Plombier spécialisé en dépannage sanitaire et chauffage central.';

        // Adhésion multiple (ex. suivre plusieurs formations en parallèle).
        $this->withToken($token)->postJson('/api/groups/1/join', ['specialite' => $specialite])->assertOk();
        $this->withToken($token)->postJson('/api/groups/2/join', ['specialite' => $specialite])->assertOk();
        // Rejoindre deux fois le même groupe reste idempotent (met à jour la spécialité).
        $this->withToken($token)->postJson('/api/groups/1/join', ['specialite' => $specialite])->assertOk()
            ->assertJsonPath('members', 1);

        $groups = collect($this->withToken($token)->getJson('/api/groups')->json('groups'));
        $this->assertTrue($groups->firstWhere('id', 1)['joined']);
        $this->assertTrue($groups->firstWhere('id', 2)['joined']);
        $this->assertSame(2, $user->groups()->count());

        // Quitter un groupe.
        $this->withToken($token)->postJson('/api/groups/1/leave')->assertOk()
            ->assertJsonPath('members', 0);
        $this->assertSame(1, $user->fresh()->groups()->count());

        // Groupe inexistant.
        $this->withToken($token)->postJson('/api/groups/999/join')->assertStatus(404);
    }

    public function test_les_groupes_exigent_une_authentification(): void
    {
        $this->getJson('/api/groups')->assertStatus(401);
    }

    public function test_la_specialite_est_obligatoire_pour_rejoindre_un_groupe(): void
    {
        $token = $this->memberToken();

        $this->withToken($token)->postJson('/api/groups/1/join')
            ->assertStatus(422);

        $this->withToken($token)->postJson('/api/groups/1/join', ['specialite' => 'court'])
            ->assertStatus(422);
    }

    public function test_le_trombinoscope_est_reserve_aux_abonnes_a_jour(): void
    {
        $token = $this->memberToken(); // pas d'abonnement

        $this->withToken($token)->getJson('/api/groups/1/members')
            ->assertStatus(402)
            ->assertJsonPath('code', 'subscription_required');
    }

    public function test_le_trombinoscope_affiche_les_membres_et_leur_specialite(): void
    {
        $viewerToken = $this->memberToken($viewer);
        $viewer->subscription_expires_at = now()->addYear();
        $viewer->save();

        $plombier = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Koffi', 'ville' => 'Abidjan']);
        $plombierToken = $this->tokenFor($plombier);
        $this->withToken($plombierToken)->postJson('/api/groups/8/join', [
            'specialite' => 'Plombier spécialisé en dépannage sanitaire et chauffage central.',
        ])->assertOk();

        $roster = $this->withToken($viewerToken)->getJson('/api/groups/8/members')
            ->assertOk()->json();

        $this->assertSame('BTP & Construction', $roster['group']['name']);
        $this->assertCount(1, $roster['members']);
        $this->assertSame('Awa', $roster['members'][0]['prenom']);
        $this->assertStringContainsString('dépannage sanitaire', $roster['members'][0]['specialite']);

        // Recherche par spécialité.
        $recherche = $this->withToken($viewerToken)->getJson('/api/groups/8/members?q=chauffage')
            ->assertOk()->json('members');
        $this->assertCount(1, $recherche);
    }

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    public function test_le_trombinoscope_exclut_les_comptes_suspendus_et_les_membres_masques(): void
    {
        $groupe = \App\Models\Group::first();
        $visible = User::factory()->create(['prenom' => 'Visible', 'subscription_expires_at' => now()->addYear()]);
        $suspendu = User::factory()->create(['prenom' => 'Suspendu', 'is_active' => false]);
        $masque = User::factory()->create(['prenom' => 'Masque', 'preferences' => ['apparaitre_annuaire' => false]]);
        foreach ([$visible, $suspendu, $masque] as $u) {
            $u->groups()->attach($groupe->id, ['specialite' => 'Spécialité de test assez longue']);
        }
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $visible->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        $r = $this->withToken($plain)->getJson("/api/groups/{$groupe->id}/members")->assertOk()->json();
        $this->assertSame(['Visible'], array_column($r['members'], 'prenom'));

        // Le compteur ignore les comptes suspendus (le membre masqué reste compté).
        $g = collect($this->withToken($plain)->getJson('/api/groups')->json('groups'))->firstWhere('id', $groupe->id);
        $this->assertSame(2, $g['members']);
    }

    public function test_la_fiche_professionnelle_du_membre_dans_le_groupe(): void
    {
        $groupe = \App\Models\Group::where('slug', 'btp-construction')->first();
        $pro = User::factory()->create(['prenom' => 'Yao', 'telephone' => '0707070707', 'subscription_expires_at' => now()->addYear()]);
        $plainPro = Str::random(60);
        ApiToken::create(['user_id' => $pro->id, 'token' => hash('sha256', $plainPro), 'name' => 'test']);

        $this->withToken($plainPro)->postJson("/api/groups/{$groupe->id}/join", [
            'specialite' => 'Plombier : dépannage sanitaire, chauffe-eau, fuites.',
            'services' => ['Dépannage urgent', ' Pose de chauffe-eau ', 'Dépannage urgent'],
            'zone' => 'Cocody, Bingerville',
            'disponibilites' => '7j/7, 7h-20h',
            'telephone_visible' => true,
        ])->assertOk();

        $mine = collect($this->withToken($plainPro)->getJson('/api/groups')->json('groups'))->firstWhere('id', $groupe->id);
        $this->assertSame(['Dépannage urgent', 'Pose de chauffe-eau'], $mine['ma_fiche']['services']);

        $client = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $client->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        $liste = $this->withToken($plain)->getJson("/api/groups/{$groupe->id}/members?q=bingerville")->json('members');
        $this->assertSame('Cocody, Bingerville', $liste[0]['zone']);

        $fiche = $this->withToken($plain)->getJson("/api/groups/{$groupe->id}/members/{$pro->id}")->assertOk()->json('fiche');
        $this->assertSame('7j/7, 7h-20h', $fiche['pro']['disponibilites']);
        // Téléphone affiché car autorisé pour ce groupe (même si masqué dans l'annuaire).
        $this->assertSame('0707070707', $fiche['membre']['telephone']);

        // Hors du groupe : pas de fiche.
        $this->withToken($plain)->getJson("/api/groups/{$groupe->id}/members/{$client->id}")->assertStatus(404);
    }

    public function test_identite_suggestions_et_derniers_membres(): void
    {
        $token = $this->memberToken($moi);
        $moi->update(['secteur' => 'Plomberie', 'titre' => 'Plombier indépendant']);
        $masque = User::factory()->create(['preferences' => ['apparaitre_annuaire' => false]]);
        $masque->groups()->attach(8, ['specialite' => 'Spécialité de test assez longue']);
        $visible = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Traoré']);
        $visible->groups()->attach(8, ['specialite' => 'Spécialité de test assez longue']);

        $groups = collect($this->withToken($token)->getJson('/api/groups')->assertOk()->json('groups'))->keyBy('id');

        $btp = $groups[8];
        $this->assertSame('building-2', $btp['icone']);
        $this->assertSame('#E07B24', $btp['couleur']);
        $this->assertTrue($btp['suggere']);
        $this->assertFalse($groups[2]['suggere']);
        // Les membres masqués de l'annuaire n'apparaissent pas parmi les derniers arrivés.
        $this->assertSame([['initiales' => 'AT', 'photo' => $visible->photo, 'mentor' => false]], $btp['derniers']);
    }

    public function test_lien_whatsapp_reserve_aux_membres_abonnes_du_groupe(): void
    {
        \App\Support\SubscriptionMode::set(true);
        $referent = User::factory()->create(['prenom' => 'Paul']);
        \App\Models\Group::whereKey(8)->update([
            'whatsapp_url' => 'https://chat.whatsapp.com/abc123',
            'referent_id' => $referent->id,
            'annonce' => 'Réunion du groupe samedi à 10h.',
            'annonce_at' => now(),
        ]);

        $token = $this->memberToken($moi);
        $voir = fn () => collect($this->withToken($token)->getJson('/api/groups')->json('groups'))->firstWhere('id', 8);

        // Ni membre du groupe ni abonné : le lien existe mais reste verrouillé.
        $this->assertSame('verrouille', $voir()['whatsapp']);
        $this->assertSame('Paul', $voir()['referent']['prenom']);
        $this->assertSame('Réunion du groupe samedi à 10h.', $voir()['annonce']);

        // Membre du groupe mais pas abonné : toujours verrouillé.
        $moi->groups()->attach(8, ['specialite' => 'Spécialité de test assez longue']);
        $this->assertSame('verrouille', $voir()['whatsapp']);

        // Membre du groupe et abonné : le lien est donné.
        $moi->update(['subscription_expires_at' => now()->addYear()]);
        $this->assertSame('https://chat.whatsapp.com/abc123', $voir()['whatsapp']);
        $this->withToken($token)->getJson('/api/groups/8/members')->assertOk()
            ->assertJsonPath('group.whatsapp', 'https://chat.whatsapp.com/abc123')
            ->assertJsonPath('group.referent.prenom', 'Paul');
    }
}

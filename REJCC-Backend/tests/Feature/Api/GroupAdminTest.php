<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\MemberReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupAdminTest extends TestCase
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

    private function membreDuBtp(string $prenom = 'Esther'): User
    {
        $u = User::factory()->create(['prenom' => $prenom, 'role' => 'member']);
        $u->groups()->attach(8, ['specialite' => 'Plombière, dépannage sanitaire.', 'services' => json_encode(['Devis gratuit'])]);

        return $u;
    }

    public function test_section_reservee_aux_admins_autorises(): void
    {
        $this->withToken($this->admin(['formations']))->getJson('/api/admin/groups')->assertStatus(403);
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'member'])))->getJson('/api/admin/groups')->assertStatus(403);
        $this->withToken($this->admin(['groupes']))->getJson('/api/admin/groups')->assertOk()->assertJsonCount(16, 'groups');
    }

    public function test_creer_modifier_reordonner_et_supprimer_un_groupe(): void
    {
        $token = $this->admin();

        $id = $this->withToken($token)->postJson('/api/admin/groups', [
            'name' => 'Mines & Énergie', 'description' => 'Pour les métiers des mines.', 'icone' => 'factory', 'couleur' => '#123456',
        ])->assertCreated()->json('id');
        $g = Group::find($id);
        $this->assertSame('mines-energie', $g->slug);
        $this->assertSame(Group::where('slug', 'action-sociale-solidarite')->value('ordre') + 1, $g->ordre);

        // Nom en double, couleur ou lien WhatsApp invalides.
        $this->withToken($token)->postJson('/api/admin/groups', ['name' => 'BTP & Construction'])->assertStatus(422);
        $this->withToken($token)->putJson("/api/admin/groups/{$id}", ['name' => 'Mines & Énergie', 'couleur' => 'rouge'])->assertStatus(422);
        $this->withToken($token)->putJson("/api/admin/groups/{$id}", ['name' => 'Mines & Énergie', 'whatsapp_url' => 'https://exemple.com/x'])
            ->assertStatus(422)->assertJsonPath('message', "Collez le lien d'invitation du groupe WhatsApp (https://chat.whatsapp.com/…).");

        $this->withToken($token)->putJson("/api/admin/groups/{$id}", ['name' => 'Mines & Énergie', 'whatsapp_url' => 'https://chat.whatsapp.com/Abc'])->assertOk();
        $this->assertSame('https://chat.whatsapp.com/Abc', $g->fresh()->whatsapp_url);

        // Monter d'un cran : il passe devant « Action Sociale & Solidarité ».
        $this->withToken($token)->postJson("/api/admin/groups/{$id}/move", ['direction' => 'up'])->assertOk();
        $this->assertSame(16, $g->fresh()->ordre);
        $this->assertSame(17, Group::where('slug', 'action-sociale-solidarite')->value('ordre'));

        $this->withToken($token)->deleteJson("/api/admin/groups/{$id}")->assertOk();
        $this->assertNull(Group::find($id));
    }

    public function test_referent_parmi_les_membres_et_annonce_notifiee(): void
    {
        $token = $this->admin();
        $esther = $this->membreDuBtp();
        $autre = $this->membreDuBtp('Yao');
        $externe = User::factory()->create();

        $this->withToken($token)->putJson('/api/admin/groups/8', ['name' => 'BTP & Construction', 'referent_id' => $externe->id])
            ->assertStatus(422)->assertJsonPath('message', 'Le référent doit être membre du groupe.');

        $this->withToken($token)->putJson('/api/admin/groups/8', [
            'name' => 'BTP & Construction', 'referent_id' => $esther->id,
            'annonce' => 'Réunion samedi à 10h.', 'notifier' => true,
        ])->assertOk()->assertJsonPath('notifies', 2);

        $g = Group::find(8);
        $this->assertSame($esther->id, $g->referent_id);
        $this->assertNotNull($g->annonce_at);
        $this->assertSame(1, MemberNotification::where('user_id', $autre->id)->where('title', 'Annonce du groupe BTP & Construction')->count());

        // Même annonce renvoyée : pas de nouvelle notification.
        $this->withToken($token)->putJson('/api/admin/groups/8', [
            'name' => 'BTP & Construction', 'referent_id' => $esther->id, 'annonce' => 'Réunion samedi à 10h.', 'notifier' => true,
        ])->assertOk()->assertJsonPath('notifies', 0);
    }

    public function test_retirer_un_membre_le_previent_et_libere_le_role_de_referent(): void
    {
        $token = $this->admin();
        $esther = $this->membreDuBtp();
        Group::whereKey(8)->update(['referent_id' => $esther->id]);

        $liste = $this->withToken($token)->getJson('/api/admin/groups/8/members')->assertOk()->json('members');
        $this->assertSame(['Devis gratuit'], $liste[0]['services']);

        $this->withToken($token)->deleteJson("/api/admin/groups/8/members/{$esther->id}", ['motif' => 'Fiche incomplète'])->assertOk();
        $this->assertFalse($esther->groups()->where('groups.id', 8)->exists());
        $this->assertNull(Group::find(8)->referent_id);
        $this->assertStringContainsString('Fiche incomplète', MemberNotification::where('user_id', $esther->id)->latest('id')->value('body'));
    }

    public function test_signaler_puis_moderer_un_avis(): void
    {
        $pro = $this->membreDuBtp();
        $auteur = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $temoin = User::factory()->create(['subscription_expires_at' => now()->addYear()]);
        $avis = MemberReview::create(['reviewer_id' => $auteur->id, 'reviewed_id' => $pro->id, 'group_id' => 8, 'note' => 1, 'commentaire' => 'Arnaqueur !']);

        // L'auteur ne peut pas signaler son propre avis ; un autre membre, si.
        $this->withToken($this->tokenFor($auteur))->postJson("/api/avis/{$avis->id}/signaler")->assertStatus(422);
        $this->withToken($this->tokenFor($temoin))->postJson("/api/avis/{$avis->id}/signaler", ['motif' => 'Injurieux'])->assertOk();

        $token = $this->admin();
        $this->withToken($token)->getJson('/api/admin/a-traiter')->assertOk()
            ->assertJsonFragment(['cle' => 'avis', 'nombre' => 1]);
        $this->withToken($token)->getJson('/api/admin/avis')->assertOk()
            ->assertJsonPath('avis.0.motif', 'Injurieux')->assertJsonPath('avis.0.professionnel', trim($pro->prenom.' '.$pro->nom));

        // Masquage : l'avis disparaît de la moyenne, l'auteur est prévenu.
        $this->withToken($token)->putJson("/api/admin/avis/{$avis->id}", ['masque' => true])->assertOk();
        $this->assertSame(0, MemberReview::resume($pro->id)['nombre']);
        $this->assertSame(1, MemberNotification::where('user_id', $auteur->id)->where('title', 'Votre avis a été masqué')->count());
        $this->withToken($token)->getJson('/api/admin/avis')->assertJsonPath('meta.total', 0);

        // L'auteur corrige son avis : il redevient visible et repasse en modération.
        $this->withToken($this->tokenFor($auteur))->postJson("/api/members/{$pro->id}/avis", ['note' => 2, 'commentaire' => 'Retard important.'])->assertOk();
        $avis->refresh();
        $this->assertFalse($avis->masque);
        $this->assertNotNull($avis->signale_at);

        $this->withToken($token)->deleteJson("/api/admin/avis/{$avis->id}")->assertOk();
        $this->assertSame(0, MemberReview::count());
    }

    public function test_export_des_membres_d_un_groupe(): void
    {
        $this->membreDuBtp();
        $res = $this->withToken($this->admin())->getJson('/api/admin/export/groupes?group=8')->assertOk()->json();
        $this->assertSame('Groupe', $res['columns'][0]);
        $this->assertCount(1, $res['rows']);
        $this->assertSame('BTP & Construction', $res['rows'][0][0]);
        $this->assertSame('Devis gratuit', $res['rows'][0][8]);
    }
}

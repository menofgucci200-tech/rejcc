<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\MemberReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberReviewTest extends TestCase
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
        return User::factory()->create(['role' => 'member', 'subscription_expires_at' => now()->addYear()] + $attrs);
    }

    private function plombier(): User
    {
        $pro = $this->abonne(['prenom' => 'Esther']);
        $pro->groups()->attach(8, ['specialite' => 'Plombière, dépannage sanitaire et chauffage.']);

        return $pro;
    }

    public function test_un_membre_donne_puis_modifie_son_avis_et_le_pro_est_notifie_une_fois(): void
    {
        $pro = $this->plombier();
        $client = $this->abonne(['prenom' => 'Awa']);
        $token = $this->tokenFor($client);

        $avis = $this->withToken($token)->postJson("/api/members/{$pro->id}/avis", [
            'note' => 4, 'commentaire' => 'Travail propre et rapide.', 'group_id' => 8,
        ])->assertOk()->json('avis');

        $this->assertSame(4.0, (float) $avis['moyenne']);
        $this->assertSame(1, $avis['nombre']);
        $this->assertSame('Awa', explode(' ', $avis['liste'][0]['auteur'])[0]);
        $this->assertSame('BTP & Construction', $avis['liste'][0]['groupe']);
        $this->assertSame(4, $avis['mon_avis']['note']);

        // Modification : toujours un seul avis, pas de seconde notification.
        $avis = $this->withToken($token)->postJson("/api/members/{$pro->id}/avis", ['note' => 5])
            ->assertOk()->json('avis');
        $this->assertSame(1, $avis['nombre']);
        $this->assertSame(5.0, (float) $avis['moyenne']);
        $this->assertSame(1, MemberReview::count());
        $this->assertSame(1, MemberNotification::where('user_id', $pro->id)->where('title', 'Nouvel avis sur votre fiche')->count());

        // Suppression.
        $this->withToken($token)->deleteJson("/api/members/{$pro->id}/avis")
            ->assertOk()->assertJsonPath('avis.nombre', 0)->assertJsonPath('avis.mon_avis', null);
    }

    public function test_pas_d_avis_sur_soi_meme_ni_sans_note_ni_sans_abonnement(): void
    {
        $pro = $this->plombier();
        $this->withToken($this->tokenFor($pro))->postJson("/api/members/{$pro->id}/avis", ['note' => 5])->assertStatus(422);

        $client = $this->abonne();
        $this->withToken($this->tokenFor($client))->postJson("/api/members/{$pro->id}/avis", ['note' => 9])->assertStatus(422);
        $this->withToken($this->tokenFor($client))->postJson("/api/members/{$pro->id}/avis", [])->assertStatus(422);

        $nonAbonne = User::factory()->create(['role' => 'member']);
        $this->withToken($this->tokenFor($nonAbonne))->postJson("/api/members/{$pro->id}/avis", ['note' => 5])
            ->assertStatus(402);

        $this->assertSame(0, MemberReview::count());
    }

    public function test_la_note_apparait_sur_le_trombinoscope_et_la_fiche_hors_avis_masques(): void
    {
        $pro = $this->plombier();
        foreach ([5, 3] as $note) {
            MemberReview::create(['reviewer_id' => $this->abonne()->id, 'reviewed_id' => $pro->id, 'note' => $note]);
        }
        MemberReview::create(['reviewer_id' => $this->abonne()->id, 'reviewed_id' => $pro->id, 'note' => 1, 'masque' => true]);

        $token = $this->tokenFor($this->abonne());
        $membre = $this->withToken($token)->getJson('/api/groups/8/members')->assertOk()->json('members.0');
        $this->assertSame('Esther', $membre['prenom']);
        $this->assertStringContainsString('Plombière', $membre['specialite']);
        $this->assertSame(4.0, (float) $membre['note_moyenne']);
        $this->assertSame(2, $membre['nb_avis']);

        $fiche = $this->withToken($token)->getJson("/api/groups/8/members/{$pro->id}")->assertOk()->json('fiche.avis');
        $this->assertSame(2, $fiche['nombre']);
        $this->assertCount(2, $fiche['liste']);
        $this->assertSame(1, $fiche['repartition'][5]);
        $this->assertSame(0, $fiche['repartition'][1]);
        $this->assertNull($fiche['mon_avis']);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\Mentorship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MentoratTest extends TestCase
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

    public function test_un_mentor_est_dispense_d_abonnement(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);

        $this->assertTrue($mentor->hasPaidSubscription());
        $me = $this->withToken($this->tokenFor($mentor))->getJson('/api/auth/me')->json('user');
        $this->assertTrue($me['subscription_exempt']);
        $this->assertSame(3, $me['mentor']['capacite']);

        // L'annuaire (réservé aux abonnés) lui est ouvert.
        $this->withToken($this->tokenFor($mentor))->getJson('/api/members')->assertOk();
    }

    public function test_le_mentor_renseigne_son_profil_de_mentor(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $token = $this->tokenFor($mentor);

        $this->withToken($token)->putJson('/api/mentorat/profil', [
            'expertises' => ['Finance', ' Marketing ', 'Finance'],
            'bio' => '20 ans dans la banque.',
            'disponibilites' => 'Mardi et jeudi soir',
            'format' => 'visio',
            'capacite' => 4,
            'accepte' => true,
        ])->assertOk()->assertJsonPath('mentor.expertises', ['Finance', 'Marketing'])
            ->assertJsonPath('mentor.format_label', 'En visio');

        // Un membre ne peut pas se créer un profil de mentor.
        $this->withToken($this->tokenFor($this->abonne()))->putJson('/api/mentorat/profil', ['capacite' => 2])->assertStatus(403);
    }

    public function test_les_mentors_apparaissent_dans_l_annuaire_et_leur_fiche_s_ouvre(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor', 'prenom' => 'Paul', 'mentor_expertises' => ['Agro-business']]);
        User::factory()->create(['role' => 'member', 'prenom' => 'Awa']);
        $token = $this->tokenFor($this->abonne());

        $membres = $this->withToken($token)->getJson('/api/members')->json('members');
        $this->assertContains('mentor', array_column($membres, 'role'));

        $seulsMentors = $this->withToken($token)->getJson('/api/members?mentors=1')->json('members');
        $this->assertSame(['Paul'], array_column($seulsMentors, 'prenom'));

        $this->withToken($token)->getJson("/api/members/{$mentor->id}")->assertOk()
            ->assertJsonPath('member.mentor.expertises', ['Agro-business']);
    }

    public function test_un_membre_trouve_un_mentor_et_lui_envoie_une_demande(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor', 'prenom' => 'Paul', 'mentor_expertises' => ['Levée de fonds'], 'mentor_capacite' => 1]);
        User::factory()->create(['role' => 'mentor', 'prenom' => 'Jean', 'mentor_expertises' => ['Marketing']]);
        $membre = $this->abonne(['prenom' => 'Awa']);
        $token = $this->tokenFor($membre);

        $liste = $this->withToken($token)->getJson('/api/mentors?q=levée')->assertOk()->json();
        $this->assertSame(['Paul'], array_column($liste['mentors'], 'prenom'));
        $this->assertContains('Marketing', $liste['expertises']);
        $this->assertTrue($liste['mentors'][0]['disponible']);

        $this->withToken($token)->postJson("/api/mentors/{$mentor->id}/demande", ['objectif' => 'court'])->assertStatus(422);
        $this->withToken($token)->postJson("/api/mentors/{$mentor->id}/demande", [
            'objectif' => 'Structurer les finances de mon atelier de couture',
            'besoin' => 'Je ne sais pas fixer mes prix.',
        ])->assertOk()->assertJsonPath('mentorship.statut', 'en_attente');

        $this->assertSame(1, MemberNotification::where('user_id', $mentor->id)->count());
        // Pas de doublon avec le même mentor.
        $this->withToken($token)->postJson("/api/mentors/{$mentor->id}/demande", ['objectif' => 'Encore une demande identique'])->assertStatus(422);

        $mes = $this->withToken($token)->getJson('/api/mentorat')->json('mentorats');
        $this->assertSame('Paul', $mes[0]['autre']['prenom']);
        $this->assertSame('mentore', $mes[0]['je_suis']);

        // Le membre peut retirer sa demande.
        $this->withToken($token)->postJson("/api/mentorat/{$mes[0]['id']}/annuler")->assertOk();
        $this->assertSame('annule', Mentorship::first()->statut);
        $this->assertSame([], $this->withToken($token)->getJson('/api/mentorat')->json('mentorats'));
    }

    public function test_la_demande_est_reservee_aux_abonnes_quand_les_abonnements_sont_actives(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor']);
        $token = $this->tokenFor(User::factory()->create(['subscription_expires_at' => null]));

        // Parcourir les mentors reste libre.
        $this->withToken($token)->getJson('/api/mentors')->assertOk();
        $this->withToken($token)->postJson("/api/mentors/{$mentor->id}/demande", ['objectif' => 'Lancer mon activité de traiteur'])
            ->assertStatus(402)->assertJsonPath('code', 'subscription_required');
    }

    public function test_un_mentor_complet_ou_indisponible_ne_recoit_plus_de_demande(): void
    {
        $mentor = User::factory()->create(['role' => 'mentor', 'mentor_capacite' => 1]);
        Mentorship::create(['mentor_id' => $mentor->id, 'mentore_id' => $this->abonne()->id, 'statut' => 'accepte', 'objectif' => 'Déjà suivi']);
        $token = $this->tokenFor($this->abonne());

        $this->assertFalse($this->withToken($token)->getJson('/api/mentors')->json('mentors.0.disponible'));
        $this->withToken($token)->postJson("/api/mentors/{$mentor->id}/demande", ['objectif' => 'Développer ma boutique en ligne'])->assertStatus(422);
    }
}

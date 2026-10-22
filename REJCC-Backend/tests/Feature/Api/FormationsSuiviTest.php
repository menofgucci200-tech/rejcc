<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\Departements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Suivi du département Formation : émargement et attestation de présence,
 * replay après la session, cockpit et suivi des apprenants.
 */
class FormationsSuiviTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function formation(): string
    {
        return $this->tokenFor(User::factory()->create(['role' => 'admin', 'bureau_departement' => 'formation', 'permissions' => Departements::sections('formation')]));
    }

    private function live(array $extra = []): Event
    {
        return Event::create([
            'title' => 'Atelier', 'slug' => 'atelier-'.Str::random(5), 'category' => Event::CATEGORIE_LIVE, 'statut' => 'publie',
            'en_ligne' => true, 'lien_visio' => 'https://meet.google.com/abc-defg-hij', 'inscriptions_ouvertes' => true,
            'starts_at' => now()->subHours(2), 'ends_at' => now()->subHour(), ...$extra,
        ]);
    }

    public function test_emargement_et_attestation_de_presence(): void
    {
        $token = $this->formation();
        $e = $this->live(['attestation' => true]);
        $venu = User::factory()->create(['prenom' => 'Ama', 'nom' => 'Kouassi']);
        $absent = User::factory()->create();
        $r1 = EventRegistration::create(['event_id' => $e->id, 'user_id' => $venu->id, 'rejoint_at' => now()->subMinutes(100)]);
        EventRegistration::create(['event_id' => $e->id, 'user_id' => $absent->id]);

        $this->withToken($token)->getJson("/api/admin/formations-live/{$e->id}/presences")->assertOk()->assertJsonCount(2, 'inscrits');
        $this->withToken($token)->postJson("/api/admin/formations-live/{$e->id}/presences", ['tous_les_rejoints' => true])
            ->assertOk()->assertJsonPath('presents', 1)->assertJsonPath('attestations', 1);
        $this->assertNotNull($r1->fresh()->present_at);
        $this->assertTrue(Certificate::where('type', 'evenement')->where('source_id', $r1->id)->exists());

        $avenir = $this->live(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        $this->withToken($token)->postJson("/api/admin/formations-live/{$avenir->id}/presences", ['tous_les_rejoints' => true])->assertStatus(422);
    }

    public function test_replay_prevenu_et_visible_sur_la_fiche_avec_la_prochaine_session(): void
    {
        $token = $this->formation();
        $f = Formation::create(['title' => 'Compta', 'category' => 'Gestion', 'is_published' => true, 'is_free' => true]);
        $e = $this->live(['formation_id' => $f->id]);
        $suivante = $this->live(['formation_id' => $f->id, 'title' => 'Séance 2', 'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(7)->addHour()]);
        $membre = User::factory()->create(['role' => 'member', 'is_active' => true]);
        EventRegistration::create(['event_id' => $e->id, 'user_id' => $membre->id]);

        $this->withToken($token)->putJson("/api/admin/formations-live/{$e->id}/apres", ['replay_url' => 'https://youtu.be/xyz', 'prevenir' => true])->assertOk();
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'Le replay : Atelier')->exists());

        $this->withToken($this->tokenFor($membre))->getJson("/api/events/{$e->id}")->assertOk()
            ->assertJsonPath('event.apres.replay_url', 'https://youtu.be/xyz')
            ->assertJsonPath('event.apres.prochaine.id', $suivante->id);
    }

    public function test_cockpit_signale_ce_qui_manque_et_les_chevauchements(): void
    {
        $token = $this->formation();
        $debut = now()->addDay()->setTime(18, 0);
        $this->live(['title' => 'A', 'starts_at' => $debut, 'ends_at' => $debut->copy()->addHour()]);
        $this->live(['title' => 'B', 'starts_at' => $debut->copy()->addMinutes(30), 'ends_at' => $debut->copy()->addMinutes(90), 'lien_visio' => 'https://meet.google.com/']);

        $r = $this->withToken($token)->getJson('/api/admin/formations-live/cockpit')->assertOk();
        $this->assertSame(2, $r->json('chiffres.sessions_a_venir'));
        $b = collect($r->json('a_faire'))->firstWhere('titre', 'B');
        $this->assertSame('urgent', $b['niveau']);
        $this->assertContains('lien Meet manquant', $b['problemes']);
        $this->assertContains('hôte Meet à désigner', $b['problemes']);
        $a = collect($r->json('a_faire'))->firstWhere('titre', 'A');
        $this->assertStringContainsString('même créneau que « B', implode(' ', $a['problemes']));
    }

    public function test_suivi_des_apprenants_et_relance(): void
    {
        $token = $this->formation();
        $f = Formation::create(['title' => 'Compta', 'category' => 'Gestion', 'is_published' => true, 'is_free' => true]);
        $actif = User::factory()->create(['prenom' => 'Awa']);
        $endormi = User::factory()->create(['prenom' => 'Koffi']);
        FormationEnrollment::create(['formation_id' => $f->id, 'user_id' => $actif->id, 'progress' => 40]);
        $e = FormationEnrollment::create(['formation_id' => $f->id, 'user_id' => $endormi->id, 'progress' => 10]);
        $e->forceFill(['updated_at' => now()->subDays(20)])->saveQuietly();

        $r = $this->withToken($token)->getJson("/api/admin/formations/{$f->id}/apprenants")->assertOk();
        $this->assertSame('inactif', collect($r->json('apprenants'))->firstWhere('user_id', $endormi->id)['statut']);

        $this->withToken($token)->postJson("/api/admin/formations/{$f->id}/relance", ['user_ids' => [$endormi->id], 'message' => 'On vous attend pour le module 2 !'])
            ->assertOk()->assertJsonPath('envoyes', 1);
        $this->assertTrue(MemberNotification::where('user_id', $endormi->id)->where('body', 'On vous attend pour le module 2 !')->exists());
    }
}

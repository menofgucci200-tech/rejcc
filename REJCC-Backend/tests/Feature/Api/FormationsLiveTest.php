<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
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
 * Sessions live du département Formation : programmées dans le dashboard,
 * visibles dans le calendrier des membres, annoncées et rattachées à leur
 * formation.
 */
class FormationsLiveTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function formateur(): string
    {
        return $this->tokenFor(User::factory()->create([
            'role' => 'admin', 'bureau_departement' => 'formation', 'permissions' => Departements::sections('formation'),
        ]));
    }

    private function formation(): Formation
    {
        return Formation::create(['title' => 'Créer son entreprise', 'category' => 'Entrepreneuriat', 'description' => 'Les bases.', 'is_published' => true, 'is_free' => true]);
    }

    public function test_le_departement_formation_programme_une_session_avec_salle_jitsi_et_l_annonce(): void
    {
        $membre = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $f = $this->formation();

        $r = $this->withToken($this->formateur())->postJson('/api/admin/formations-live', [
            'title' => 'Atelier business plan', 'formation_id' => $f->id, 'animateur' => 'Bokoua Djoko',
            'starts_at' => now()->addDays(3)->setTime(18, 0)->toIso8601String(), 'duree' => 90,
            'visio' => 'auto', 'annoncer' => true,
        ])->assertOk();

        $this->assertStringStartsWith('https://meet.jit.si/REJCC-atelier-business-plan-', $r->json('session.lien_visio'));
        $this->assertSame(90, $r->json('session.duree'));
        $this->assertSame(1, $r->json('annonces'));

        $session = Event::find($r->json('session.id'));
        $this->assertTrue($session->estLive());
        $this->assertTrue($session->en_ligne);
        $this->assertSame('publie', $session->statut);
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'Nouvelle session live : Atelier business plan')->exists());

        // Dans le calendrier du membre, comme tout événement.
        $events = $this->withToken($this->tokenFor($membre))->getJson('/api/events')->assertOk()->json('events');
        $this->assertSame(['Atelier business plan'], collect($events)->pluck('title')->all());
    }

    public function test_un_lien_meet_ou_zoom_peut_remplacer_la_salle_automatique(): void
    {
        $r = $this->withToken($this->formateur())->postJson('/api/admin/formations-live', [
            'title' => 'Leadership', 'starts_at' => now()->addDay()->toIso8601String(), 'duree' => 60,
            'visio' => 'lien', 'lien_visio' => 'https://meet.google.com/abc-defg-hij',
        ])->assertOk();
        $this->assertSame('https://meet.google.com/abc-defg-hij', $r->json('session.lien_visio'));

        $this->withToken($this->formateur())->postJson('/api/admin/formations-live', [
            'title' => 'Sans lien', 'starts_at' => now()->addDay()->toIso8601String(), 'duree' => 60, 'visio' => 'lien',
        ])->assertStatus(422);
    }

    public function test_la_formation_montre_ses_sessions_et_le_report_previent_les_inscrits(): void
    {
        $token = $this->formateur();
        $f = $this->formation();
        $membre = User::factory()->create(['role' => 'member', 'is_active' => true]);
        FormationEnrollment::create(['formation_id' => $f->id, 'user_id' => $membre->id]);

        $id = $this->withToken($token)->postJson('/api/admin/formations-live', [
            'title' => 'Séance questions-réponses', 'formation_id' => $f->id,
            'starts_at' => now()->addDays(2)->toIso8601String(), 'duree' => 60, 'visio' => 'auto',
        ])->json('session.id');
        EventRegistration::create(['event_id' => $id, 'user_id' => $membre->id]);

        $fiche = $this->withToken($this->tokenFor($membre))->getJson("/api/formations/{$f->id}/fiche")->assertOk();
        $this->assertSame('Séance questions-réponses', $fiche->json('formation.sessions_live.0.title'));
        $this->assertTrue($fiche->json('formation.sessions_live.0.inscrit'));

        $lien = Event::find($id)->lien_visio;
        $this->withToken($token)->putJson("/api/admin/formations-live/{$id}", [
            'title' => 'Séance questions-réponses', 'formation_id' => $f->id,
            'starts_at' => now()->addDays(4)->toIso8601String(), 'duree' => 60, 'visio' => 'auto',
        ])->assertOk();
        $this->assertSame($lien, Event::find($id)->lien_visio, 'la salle Jitsi ne change pas');
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'like', 'Date modifiée%')->exists());

        $this->withToken($token)->getJson("/api/admin/formations-live/{$id}/inscrits")->assertOk()->assertJsonCount(1, 'inscrits');
        $this->withToken($token)->postJson("/api/admin/formations-live/{$id}/annuler", ['motif' => 'Formateur indisponible'])->assertOk();
        $this->assertSame('annule', Event::find($id)->statut);
    }

    public function test_seuls_les_comptes_ayant_la_rubrique_formations_y_accedent_et_seulement_aux_sessions_live(): void
    {
        $communication = $this->tokenFor(User::factory()->create([
            'role' => 'admin', 'bureau_departement' => 'communication', 'permissions' => Departements::sections('communication'),
        ]));
        $this->withToken($communication)->getJson('/api/admin/formations-live')->assertStatus(403);

        $conference = Event::create(['title' => 'Conférence', 'slug' => 'conference', 'category' => 'Conférence', 'statut' => 'publie', 'starts_at' => now()->addDay()]);
        $this->withToken($this->formateur())->postJson("/api/admin/formations-live/{$conference->id}/annuler")->assertNotFound();
        $this->assertSame('publie', $conference->fresh()->statut);
    }
}

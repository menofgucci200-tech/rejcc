<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\Formateur;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Group;
use App\Models\MemberNotification;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Departements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sessions live : public visé, formateurs (lien personnel) et réunions
 * Google Meet créées par le compte Google du REJCC.
 */
class FormationsLivePublicTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function formateurDuDepartement(): string
    {
        return $this->tokenFor(User::factory()->create(['role' => 'admin', 'bureau_departement' => 'formation', 'permissions' => Departements::sections('formation')]));
    }

    private function programmer(string $token, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->withToken($token)->postJson('/api/admin/formations-live', [
            'title' => 'Atelier', 'starts_at' => now()->addDays(2)->toIso8601String(), 'duree' => 60,
            'visio' => 'lien', 'lien_visio' => 'https://meet.google.com/abc-defg-hij', ...$extra,
        ]);
    }

    public function test_seul_le_public_vise_est_prevenu_voit_la_session_et_peut_s_inscrire(): void
    {
        $agri = Group::create(['name' => 'Agriculture', 'slug' => 'agriculture']);
        $entrepreneurAgri = User::factory()->create(['role' => 'member', 'is_active' => true, 'profil' => 'entrepreneur']);
        $etudiantAgri = User::factory()->create(['role' => 'member', 'is_active' => true, 'profil' => 'etudiant']);
        $entrepreneurAilleurs = User::factory()->create(['role' => 'member', 'is_active' => true, 'profil' => 'entrepreneur']);
        $agri->users()->attach([$entrepreneurAgri->id, $etudiantAgri->id]);
        $token = $this->formateurDuDepartement();

        $this->withToken($token)->postJson('/api/admin/formations-live/public', ['cible_groupes' => [$agri->id], 'cible_profils' => ['entrepreneur']])
            ->assertOk()->assertJsonPath('total', 1);

        $id = $this->programmer($token, ['cible_groupes' => [$agri->id], 'cible_profils' => ['entrepreneur'], 'annoncer' => true])
            ->assertOk()->assertJsonPath('annonces', 1)->assertJsonPath('session.public', 'Agriculture · entrepreneurs')->json('session.id');

        $this->assertTrue(MemberNotification::where('user_id', $entrepreneurAgri->id)->exists());
        $this->assertFalse(MemberNotification::where('user_id', $etudiantAgri->id)->exists());

        $vise = $this->tokenFor($entrepreneurAgri);
        $this->withToken($vise)->getJson('/api/events')->assertJsonCount(1, 'events');
        $this->withToken($vise)->postJson("/api/events/{$id}/inscription")->assertOk();

        $autre = $this->tokenFor($entrepreneurAilleurs);
        $this->withToken($autre)->getJson('/api/events')->assertJsonCount(0, 'events');
        $this->withToken($autre)->getJson("/api/events/{$id}")->assertNotFound();
        $this->withToken($autre)->postJson("/api/events/{$id}/inscription")->assertNotFound();

        // Pas sur le site public.
        $this->getJson('/api/public-events')->assertJsonCount(0, 'events');
    }

    public function test_les_inscrits_d_une_formation_peuvent_etre_vises(): void
    {
        $f = Formation::create(['title' => 'Comptabilité', 'category' => 'Gestion', 'is_published' => true, 'is_free' => true]);
        $inscrit = User::factory()->create(['role' => 'member', 'is_active' => true]);
        User::factory()->create(['role' => 'member', 'is_active' => true]);
        FormationEnrollment::create(['formation_id' => $f->id, 'user_id' => $inscrit->id]);
        $token = $this->formateurDuDepartement();

        $this->programmer($token, ['cible_formation' => true])->assertStatus(422);
        $this->programmer($token, ['cible_formation' => true, 'formation_id' => $f->id, 'annoncer' => true])->assertJsonPath('annonces', 1);
        $this->withToken($this->tokenFor($inscrit))->getJson('/api/events')->assertJsonCount(1, 'events');
    }

    public function test_le_formateur_recoit_un_lien_personnel_qui_ouvre_la_visio_le_jour_venu(): void
    {
        $token = $this->formateurDuDepartement();
        $membre = User::factory()->create(['role' => 'member', 'prenom' => 'Ama', 'nom' => 'Kouassi']);
        $interne = $this->withToken($token)->postJson('/api/admin/formateurs', ['user_id' => $membre->id, 'specialite' => 'Leadership'])->assertOk()->json('formateur.id');
        $externe = $this->withToken($token)->postJson('/api/admin/formateurs', ['prenom' => 'Paul', 'nom' => 'Expert', 'email' => 'paul@exemple.ci'])->assertOk()->json('formateur.id');
        $this->withToken($token)->postJson('/api/admin/formateurs', ['user_id' => $membre->id])->assertStatus(422);

        $r = $this->programmer($token, ['formateurs' => [$interne, $externe]])->assertOk();
        $this->assertSame('Ama Kouassi, Paul Expert', $r->json('session.animateur'));
        $lien = collect($r->json('session.formateurs'))->firstWhere('id', $externe)['lien'];
        $this->assertStringContainsString('/animer/', $lien);
        $jeton = Str::afterLast($lien, '/');

        // Deux jours avant : l'espace s'ouvre, la visio pas encore.
        $this->getJson("/api/animer/{$jeton}")->assertOk()->assertJsonPath('session.formateur', 'Paul Expert')->assertJsonPath('session.lien_visio', null);
        $this->travelTo(now()->addDays(2)->subMinutes(30));
        $this->getJson("/api/animer/{$jeton}")->assertJsonPath('session.lien_visio', 'https://meet.google.com/abc-defg-hij');
        $this->travelBack();

        // Le formateur membre retrouve la session dans son espace, et a été prévenu.
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'Vous animez : Atelier')->exists());
        $this->withToken($this->tokenFor($membre))->getJson('/api/mes-sessions-animees')->assertJsonPath('formateur', true)->assertJsonCount(1, 'sessions');
        $this->getJson('/api/animer/'.str_repeat('x', 48))->assertNotFound();
    }

    public function test_la_reunion_meet_est_creee_par_le_compte_google_du_rejcc(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $token = $this->formateurDuDepartement();

        // Compte non connecté : refus clair.
        $this->programmer($token, ['visio' => 'meet', 'lien_visio' => null])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, "n'est pas connecté"));

        SiteSetting::create(['key' => 'google_meet', 'value' => ['email' => 'contact.rejcc@gmail.com', 'refresh_token' => Crypt::encryptString('rt')]]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at', 'expires_in' => 3600]),
            'www.googleapis.com/calendar/v3/*' => Http::response(['id' => 'evt123', 'hangoutLink' => 'https://meet.google.com/rej-ccli-ve1']),
        ]);

        $r = $this->programmer($token, ['visio' => 'meet', 'lien_visio' => null])->assertOk();
        $this->assertSame('https://meet.google.com/rej-ccli-ve1', $r->json('session.lien_visio'));
        $this->assertSame('meet', $r->json('session.visio'));
        $this->assertSame('evt123', Event::find($r->json('session.id'))->google_event_id);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'conferenceDataVersion=1') && $req['conferenceData']['createRequest']['conferenceSolutionKey']['type'] === 'hangoutsMeet');

        // Annulation : la réunion est retirée de l'agenda Google.
        $this->withToken($token)->postJson('/api/admin/formations-live/'.$r->json('session.id').'/annuler')->assertOk();
        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_contains($req->url(), 'events/evt123'));
        $this->assertNull(Event::find($r->json('session.id'))->google_event_id);
    }

    public function test_connexion_du_compte_google(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret', 'app.dashboard_url' => 'https://dashboard.rejcc.site']);
        $token = $this->formateurDuDepartement();

        $url = $this->withToken($token)->postJson('/api/admin/google-meet/connexion')->assertOk()->json('url');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertStringContainsString('calendar.events', $q['scope']);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at', 'refresh_token' => 'rt-secret']),
            'openidconnect.googleapis.com/*' => Http::response(['email' => 'contact.rejcc@gmail.com']),
        ]);
        $this->get('/api/google/callback?code=abc&state='.urlencode($q['state']))
            ->assertRedirect('https://dashboard.rejcc.site/admin/formations-live?google=connecte');

        $this->withToken($token)->getJson('/api/admin/google-meet')->assertJsonPath('compte.email', 'contact.rejcc@gmail.com');
        $this->assertStringNotContainsString('rt-secret', json_encode(SiteSetting::where('key', 'google_meet')->value('value')));

        $this->get('/api/google/callback?code=abc&state=falsifie')->assertRedirect('https://dashboard.rejcc.site/admin/formations-live?google=erreur');
    }
}

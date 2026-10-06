<?php

namespace Tests\Feature\Api;

use App\Mail\InfoEvenement;
use App\Mail\InscriptionEvenementConfirmee;
use App\Models\ApiToken;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Inscription publique (QR code) fusionnée dans les événements, et
 * administration des événements : une seule liste membres + invités.
 */
class EventSignupTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function adminToken(): string
    {
        return $this->tokenFor(User::factory()->create(['role' => 'admin']));
    }

    private function evenement(array $attrs = []): Event
    {
        return Event::create($attrs + [
            'title' => 'Lancement REJCC', 'slug' => 'lancement-rejcc', 'category' => 'Lancement',
            'starts_at' => now()->addDays(10), 'location' => 'Abidjan', 'inscription_publique' => true,
        ]);
    }

    private function payload(array $o = []): array
    {
        return $o + ['prenom' => 'Marie', 'nom' => 'Aka', 'telephone' => '0102030405'];
    }

    // ── Inscription publique ─────────────────────────────────────────────

    public function test_un_invite_s_inscrit_et_recoit_un_billet(): void
    {
        Mail::fake();
        $event = $this->evenement();

        $res = $this->postJson('/api/event-signup/lancement-rejcc', $this->payload(['email' => 'marie@example.com']))
            ->assertOk()->assertJsonPath('event.count', 1)->json();

        $this->assertMatchesRegularExpression('/^B-[A-Z0-9]{8}$/', $res['billet']);
        $this->assertDatabaseHas('event_registrations', ['event_id' => $event->id, 'user_id' => null, 'telephone' => '0102030405']);
        Mail::assertSent(InscriptionEvenementConfirmee::class, fn ($m) => $m->hasTo('marie@example.com'));

        // Le billet est consultable (QR présenté à l'entrée).
        $this->getJson('/api/billet/'.strtolower($res['billet']))->assertOk()
            ->assertJsonPath('billet.nom', 'Marie Aka')->assertJsonPath('billet.event.title', 'Lancement REJCC');
    }

    public function test_aucun_email_si_aucune_adresse_et_pas_de_doublon(): void
    {
        Mail::fake();
        $this->evenement();

        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload())->assertOk();
        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload(['prenom' => 'Autre', 'telephone' => '01 02 03 04 05']))
            ->assertStatus(422)->assertJsonPath('message', 'Ce numéro est déjà inscrit à cet événement.');

        Mail::assertNothingSent();
        $this->assertSame(1, EventRegistration::count());
    }

    public function test_les_invites_et_les_membres_partagent_la_capacite(): void
    {
        $event = $this->evenement(['capacity' => 1]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => User::factory()->create()->id]);

        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload())
            ->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('event.is_full', true);
    }

    public function test_refus_si_ferme_date_limite_brouillon_ou_non_public(): void
    {
        $this->evenement(['inscriptions_ouvertes' => false]);
        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload())->assertStatus(422)
            ->assertJsonPath('message', 'Les inscriptions sont fermées.');

        $this->evenement(['slug' => 'forum', 'date_limite' => now()->subDay()]);
        $this->getJson('/api/event-signup/forum')->assertOk()
            ->assertJsonPath('event.is_past_deadline', true)->assertJsonPath('event.accepts', false);

        $this->evenement(['slug' => 'prive', 'inscription_publique' => false]);
        $this->postJson('/api/event-signup/prive', $this->payload())->assertStatus(422)
            ->assertJsonPath('message', 'Cet événement est réservé aux membres du réseau.');

        // Brouillon sans inscription publique : introuvable.
        $this->evenement(['slug' => 'brouillon', 'statut' => 'brouillon', 'inscription_publique' => false]);
        $this->getJson('/api/event-signup/brouillon')->assertStatus(404);
        $this->getJson('/api/event-signup/inconnu')->assertStatus(404);
        $this->assertSame(0, EventRegistration::count());
    }

    public function test_l_ancien_lien_du_qr_reste_valable(): void
    {
        $this->evenement(['slug' => 'lancement-2', 'ancien_slug' => 'lancement-officiel']);

        $this->getJson('/api/event-signup/lancement-officiel')->assertOk()->assertJsonPath('event.slug', 'lancement-2');
        $this->postJson('/api/event-signup/lancement-officiel', $this->payload())->assertOk();
    }

    public function test_les_reponses_sont_validees_et_enregistrees(): void
    {
        $this->evenement(['champs' => [
            ['key' => 'domaine', 'label' => 'Domaine de formation', 'type' => 'text', 'required' => true],
            ['key' => 'statut', 'label' => 'Statut social', 'type' => 'select', 'required' => true, 'options' => ['Étudiant', 'Salarié']],
        ]]);

        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload())->assertStatus(422);
        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload(['answers' => ['domaine' => 'Informatique', 'statut' => 'Autre']]))
            ->assertStatus(422);
        $this->postJson('/api/event-signup/lancement-rejcc', $this->payload(['answers' => ['domaine' => 'Informatique', 'statut' => 'Étudiant']]))
            ->assertOk();

        $this->assertSame(['domaine' => 'Informatique', 'statut' => 'Étudiant'], EventRegistration::first()->reponses);
    }

    // ── Administration ───────────────────────────────────────────────────

    public function test_l_admin_cree_un_brouillon_puis_publie_avec_annonce(): void
    {
        $token = $this->adminToken();
        $membre = User::factory()->create();
        User::factory()->create(['is_active' => false]);

        $event = $this->withToken($token)->postJson('/api/admin/events', [
            'title' => 'Lancement officiel', 'category' => 'Lancement', 'starts_at' => now()->addDays(20)->format('Y-m-d H:i'),
            'statut' => 'brouillon', 'capacity' => 500, 'inscription_publique' => true, 'annoncer' => true,
            'champs' => [
                ['label' => 'Domaine de formation', 'type' => 'text', 'required' => true],
                ['label' => 'Statut social', 'type' => 'select', 'options' => ['Étudiant', ' Salarié ', '']],
            ],
        ])->assertOk()->assertJsonPath('annonces', 0)->json('event');

        $this->assertSame('brouillon', $event['statut']);
        $this->assertSame('domaine-de-formation', $event['champs'][0]['key']);
        $this->assertSame(['Étudiant', 'Salarié'], $event['champs'][1]['options']);

        // Publication avec annonce : seuls les membres actifs sont notifiés, une seule fois.
        $data = ['title' => 'Lancement officiel', 'category' => 'Lancement', 'starts_at' => $event['starts_at'], 'statut' => 'publie', 'annoncer' => true];
        $this->withToken($token)->putJson("/api/admin/events/{$event['id']}", $data)->assertOk()->assertJsonPath('annonces', 1);
        $this->withToken($token)->putJson("/api/admin/events/{$event['id']}", $data)->assertOk()->assertJsonPath('annonces', 0);
        $this->assertSame(1, MemberNotification::where('user_id', $membre->id)->where('title', 'Nouvel événement : Lancement officiel')->count());
    }

    public function test_regles_du_formulaire_admin(): void
    {
        $token = $this->adminToken();
        $base = ['title' => 'Atelier', 'category' => 'Atelier', 'starts_at' => now()->addDays(5)->format('Y-m-d H:i')];

        $this->withToken($token)->postJson('/api/admin/events', $base + ['en_ligne' => true])->assertStatus(422);
        $this->withToken($token)->postJson('/api/admin/events', $base + ['date_limite' => now()->addDays(6)->format('Y-m-d H:i')])
            ->assertStatus(422)->assertJsonPath('message', "La date limite d'inscription doit précéder le début de l'événement.");
        $this->withToken($token)->postJson('/api/admin/events', $base + ['reserve_abonnes' => true, 'inscription_publique' => true])
            ->assertStatus(422);
        $this->withToken($token)->postJson('/api/admin/events', $base + ['en_ligne' => true, 'lien_visio' => 'https://meet.example.com/abc'])
            ->assertOk()->assertJsonPath('event.statut', 'publie');

        // Le lien de visio n'est jamais exposé sur la vitrine.
        $this->assertArrayNotHasKey('lien_visio', $this->getJson('/api/public-events')->json('events.0'));
    }

    public function test_report_annulation_et_message_previennent_les_inscrits(): void
    {
        Mail::fake();
        $token = $this->adminToken();
        $e = $this->evenement();
        $membre = User::factory()->create();
        EventRegistration::create(['event_id' => $e->id, 'user_id' => $membre->id]);
        EventRegistration::create(['event_id' => $e->id, 'prenom' => 'Marie', 'nom' => 'Aka', 'telephone' => '0102030405', 'email' => 'marie@example.com']);
        EventRegistration::create(['event_id' => $e->id, 'prenom' => 'Jean', 'nom' => 'Yao', 'telephone' => '0708091011']);

        // Report : nouvelle date → membres notifiés, invités avec e-mail prévenus.
        $this->withToken($token)->putJson("/api/admin/events/{$e->id}", [
            'title' => $e->title, 'category' => $e->category, 'location' => 'Abidjan', 'starts_at' => now()->addDays(15)->format('Y-m-d H:i'),
        ])->assertOk()->assertJsonPath('prevenus.membres', 1)->assertJsonPath('prevenus.invites', 1);
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'like', 'Date modifiée%')->exists());

        // Message aux inscrits.
        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/message", ['message' => 'Apportez votre carte membre.'])
            ->assertOk()->assertJsonPath('prevenus.membres', 1);

        // Annulation : motif obligatoire, puis tout le monde est prévenu.
        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/annuler", ['motif' => ''])->assertStatus(422);
        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/annuler", ['motif' => 'Salle indisponible'])->assertOk();
        $this->assertSame('annule', $e->fresh()->statut);
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'like', 'Événement annulé%')->exists());
        // Seule Marie (invitée avec e-mail) est prévenue par e-mail, pour chaque information.
        foreach (['Date modifiée', 'Message', 'Événement annulé'] as $titre) {
            Mail::assertSent(InfoEvenement::class, fn ($m) => $m->hasTo('marie@example.com') && str_starts_with($m->titre, $titre));
        }
        Mail::assertNotSent(InfoEvenement::class, fn ($m) => $m->inscription->email !== 'marie@example.com');

        // Rétablir.
        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/retablir")->assertOk();
        $this->assertSame('publie', $e->fresh()->statut);
    }

    public function test_liste_unique_pointage_invite_et_export(): void
    {
        $token = $this->adminToken();
        $e = $this->evenement(['champs' => [['key' => 'domaine', 'label' => 'Domaine de formation', 'type' => 'text', 'required' => false]]]);
        $membre = User::factory()->create(['prenom' => 'Awa', 'nom' => 'Koné']);
        EventRegistration::create(['event_id' => $e->id, 'user_id' => $membre->id]);
        $invite = EventRegistration::create(['event_id' => $e->id, 'prenom' => 'Jean', 'nom' => 'Kouassi', 'telephone' => '0102030405', 'reponses' => ['domaine' => 'Agriculture']]);

        $liste = $this->withToken($token)->getJson("/api/admin/events/{$e->id}/inscrits")->assertOk()->json();
        $this->assertSame([2, 1, 1], [$liste['total'], $liste['membres'], $liste['invites']]);
        $this->withToken($token)->getJson("/api/admin/events/{$e->id}/inscrits?q=kouassi")->assertJsonCount(1, 'inscrits')
            ->assertJsonPath('inscrits.0.type', 'invite');

        // Pointage d'un invité par son billet.
        $this->withToken($token)->postJson("/api/admin/events/{$e->id}/pointage", ['code' => $invite->billet])
            ->assertOk()->assertJsonPath('membre.nom', 'Jean Kouassi')->assertJsonPath('presents', 1);

        $export = $this->withToken($token)->getJson('/api/admin/export/participants?event='.$e->id)->assertOk()->json();
        $this->assertContains('Domaine de formation', $export['columns']);
        $this->assertCount(2, $export['rows']);
        $ligneInvite = collect($export['rows'])->first(fn ($r) => $r[3] === 'Jean');
        $this->assertSame('Invité', $ligneInvite[1]);
        $this->assertContains('Agriculture', $ligneInvite);

        // Retrait d'un inscrit.
        $this->withToken($token)->deleteJson("/api/admin/events/{$e->id}/inscrits/{$invite->id}")->assertOk();
        $this->assertSame(1, $e->nbInscrits());
    }

    public function test_un_membre_ne_peut_pas_gerer_les_evenements(): void
    {
        $t = $this->tokenFor(User::factory()->create());
        $e = $this->evenement();

        $this->withToken($t)->getJson('/api/admin/events')->assertStatus(403);
        $this->withToken($t)->postJson("/api/admin/events/{$e->id}/annuler", ['motif' => 'Essai de motif'])->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\Departements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Jour J d'une session live : rejoindre en cours de session, clic noté,
 * deux rappels (veille et une heure avant), session commencée modifiable.
 */
class SessionsLiveJourJTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $user->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function live(array $extra = []): Event
    {
        return Event::create([
            'title' => 'Atelier', 'slug' => 'atelier-'.Str::random(5), 'category' => Event::CATEGORIE_LIVE,
            'statut' => 'publie', 'en_ligne' => true, 'lien_visio' => 'https://meet.google.com/abc-defg-hij',
            'inscriptions_ouvertes' => true, 'starts_at' => now()->subMinutes(20), 'ends_at' => now()->addMinutes(40), ...$extra,
        ]);
    }

    public function test_un_membre_rejoint_une_session_en_cours_meme_sans_inscription_prealable(): void
    {
        $e = $this->live();
        $membre = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $token = $this->tokenFor($membre);

        $this->withToken($token)->getJson("/api/events/{$e->id}")->assertOk()
            ->assertJsonPath('event.refus', null)->assertJsonPath('event.visio_ouverte', true)->assertJsonPath('event.en_cours', true);

        $this->withToken($token)->postJson("/api/events/{$e->id}/rejoindre")->assertOk()
            ->assertJsonPath('lien_visio', 'https://meet.google.com/abc-defg-hij');
        $r = EventRegistration::where('event_id', $e->id)->where('user_id', $membre->id)->first();
        $this->assertNotNull($r->rejoint_at);

        // Trop tôt : la visio s'ouvre 15 minutes avant.
        $plusTard = $this->live(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $this->withToken($token)->postJson("/api/events/{$plusTard->id}/rejoindre")->assertStatus(422);
        // Terminée : plus d'inscription.
        $finie = $this->live(['starts_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $this->withToken($token)->postJson("/api/events/{$finie->id}/rejoindre")->assertStatus(422);
    }

    public function test_deux_rappels_la_veille_puis_une_heure_avant_avec_e_mail(): void
    {
        Mail::fake();
        $e = $this->live(['starts_at' => now()->addHours(20), 'ends_at' => now()->addHours(21)]);
        $membre = User::factory()->create(['role' => 'member', 'is_active' => true]);
        EventRegistration::create(['event_id' => $e->id, 'user_id' => $membre->id]);

        $this->artisan('evenements:rappels')->assertSuccessful();
        $this->artisan('evenements:rappels')->assertSuccessful();
        $this->assertSame(1, MemberNotification::where('user_id', $membre->id)->where('title', 'like', 'Rappel :%')->count());

        $this->travelTo(now()->addHours(19)->addMinutes(10));
        $this->artisan('evenements:rappels')->assertSuccessful();
        $this->assertSame(1, MemberNotification::where('user_id', $membre->id)->where('title', 'Dans 1 h : Atelier')->count());
        $this->assertStringNotContainsString('billet', MemberNotification::where('user_id', $membre->id)->latest('id')->value('body'));
    }

    public function test_une_session_commencee_reste_modifiable_et_un_report_relance_les_rappels(): void
    {
        $token = $this->tokenFor(User::factory()->create(['role' => 'admin', 'bureau_departement' => 'formation', 'permissions' => Departements::sections('formation')]));
        $e = $this->live();
        $donnees = ['title' => 'Atelier', 'starts_at' => $e->starts_at->format('Y-m-d H:i'), 'duree' => 60, 'visio' => 'lien', 'lien_visio' => 'https://meet.google.com/xyz-corr-ige'];

        $this->withToken($token)->putJson("/api/admin/formations-live/{$e->id}", $donnees)->assertOk();
        $this->assertSame('https://meet.google.com/xyz-corr-ige', $e->fresh()->lien_visio);
        $this->assertNull($e->fresh()->excerpt);

        // Nouvelle date passée : refusée.
        $this->withToken($token)->putJson("/api/admin/formations-live/{$e->id}", ['starts_at' => now()->subDay()->format('Y-m-d H:i')] + $donnees)->assertStatus(422);

        $r = EventRegistration::create(['event_id' => $e->id, 'user_id' => User::factory()->create()->id, 'rappel_at' => now(), 'rappel_h1_at' => now()]);
        $this->withToken($token)->putJson("/api/admin/formations-live/{$e->id}", ['starts_at' => now()->addDays(3)->format('Y-m-d H:i')] + $donnees)->assertOk();
        $this->assertNull($r->fresh()->rappel_at);
        $this->assertNull($r->fresh()->rappel_h1_at);
    }
}

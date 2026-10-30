<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\Salon;
use App\Models\SalonMembre;
use App\Models\SalonMessage;
use App\Models\SalonProgrammation;
use App\Models\SalonVocal;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Communauté, lot 3 : sujets dans les grands salons, annonces programmées, discussions vocales. */
class CommunauteLot3Test extends TestCase
{
    use RefreshDatabase;

    private const SALON = 'echanges-et-networking';

    private function token(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    private function membre(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => 'member', 'is_active' => true]);
    }

    private function entrer(string $token): void
    {
        $this->withToken($token)->getJson('/api/communaute/salons/'.self::SALON)->assertOk();
        $this->withToken($token)->postJson('/api/communaute/salons/'.self::SALON.'/regles')->assertOk();
    }

    private function poster(string $token, array $donnees)
    {
        return $this->withToken($token)->postJson('/api/communaute/salons/'.self::SALON.'/messages', $donnees);
    }

    public function test_sujets_dans_un_grand_salon(): void
    {
        SubscriptionMode::set(true);
        $salon = Salon::where('slug', self::SALON)->first();
        $abonne = $this->membre(['prenom' => 'Esther', 'subscription_expires_at' => now()->addYear()]);
        $libre = $this->membre(['prenom' => 'Awa']);
        $referent = $this->membre(['prenom' => 'Paul']);
        [$tE, $tA, $tP] = [$this->token($abonne), $this->token($libre), $this->token($referent)];
        foreach ([$tE, $tA, $tP] as $t) {
            $this->entrer($t);
        }
        SalonMembre::where('salon_id', $salon->id)->where('user_id', $referent->id)->update(['role' => 'moderateur']);

        // Sujets non activés : refus.
        $this->withToken($tE)->postJson('/api/communaute/salons/'.self::SALON.'/sujets', ['titre' => 'Fiscalité'])->assertStatus(422);
        $salon->forceFill(['sujets_actives' => true])->save();

        // Ouverture réservée aux abonnés à jour (et référents) ; titres uniques.
        $this->withToken($tA)->postJson('/api/communaute/salons/'.self::SALON.'/sujets', ['titre' => 'Fiscalité'])->assertStatus(402);
        $fisc = $this->withToken($tE)->postJson('/api/communaute/salons/'.self::SALON.'/sujets', ['titre' => 'Fiscalité des PME', 'emoji' => '🧾'])->assertOk()->json('sujet.id');
        $this->withToken($tP)->postJson('/api/communaute/salons/'.self::SALON.'/sujets', ['titre' => 'fiscalité des pme'])->assertStatus(422);
        $emploi = $this->withToken($tP)->postJson('/api/communaute/salons/'.self::SALON.'/sujets', ['titre' => 'Recrutements'])->assertOk()->json('sujet.id');

        // Tout membre écrit dans un sujet ouvert ; la réponse hérite du sujet cité.
        $m1 = $this->poster($tA, ['type' => 'texte', 'body' => 'Qui connaît la TVA sur les services ?', 'sujet_id' => $fisc])->assertOk()->json('message');
        $this->assertSame('Fiscalité des PME', $m1['sujet']['titre']);
        $m2 = $this->poster($tE, ['type' => 'texte', 'body' => 'Oui, 18 %.', 'reponse_a_id' => $m1['id']])->assertOk()->json('message');
        $this->assertSame($fisc, $m2['sujet']['id']);
        $this->poster($tE, ['type' => 'texte', 'body' => 'Bonjour à tous'])->assertOk();
        $this->poster($tP, ['type' => 'texte', 'body' => 'Poste de comptable ouvert', 'sujet_id' => $emploi])->assertOk();

        // Le fil se filtre par sujet ; la liste des sujets compte les messages.
        $fil = $this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON.'?sujet='.$fisc)->assertOk()->json();
        $this->assertSame(['Qui connaît la TVA sur les services ?', 'Oui, 18 %.'], array_column($fil['messages'], 'body'));
        $this->assertSame(2, collect($fil['sujets'])->firstWhere('id', $fisc)['messages']);
        $this->assertTrue($fil['salon']['sujets_actives']);
        $general = $this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON.'?sujet=general')->json('messages');
        $this->assertSame(['Bonjour à tous'], array_column($general, 'body'));
        $this->assertCount(4, $this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON)->json('messages'));

        // Fermeture par un référent : plus de messages des membres, le référent peut encore écrire.
        $sujetAwa = \App\Models\SalonSujet::find($fisc);
        $this->withToken($tA)->putJson('/api/communaute/sujets/'.$fisc, ['ferme' => true])->assertStatus(403);
        $this->withToken($tP)->putJson('/api/communaute/sujets/'.$fisc, ['ferme' => true])->assertOk();
        $this->assertNotNull($sujetAwa->fresh()->ferme_at);
        $this->poster($tA, ['type' => 'texte', 'body' => 'Encore une question', 'sujet_id' => $fisc])->assertStatus(403)->assertJsonPath('code', 'sujet_ferme');
        $this->poster($tP, ['type' => 'texte', 'body' => 'Sujet clos, merci à tous.', 'sujet_id' => $fisc])->assertOk();
        $this->assertTrue(collect($this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON)->json('sujets'))->firstWhere('id', $fisc)['ferme']);
        $this->assertDatabaseHas('salon_moderations', ['action' => 'sujet_ferme', 'detail' => 'Fiscalité des PME']);

        // Un sujet d'un autre salon est refusé.
        $this->withToken($tA)->postJson('/api/communaute/salons/entrepreneurs-et-projets/regles')->assertOk();
        $this->withToken($tA)->postJson('/api/communaute/salons/entrepreneurs-et-projets/messages', ['type' => 'texte', 'body' => 'Test', 'sujet_id' => $emploi])->assertOk()
            ->assertJsonPath('message.sujet', null);
    }

    public function test_activation_des_sujets_depuis_le_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => ['salons']]);
        $t = $this->token($admin);
        $salon = Salon::where('slug', self::SALON)->first();
        $annonces = Salon::where('type', 'annonces')->first();
        $donnees = ['nom' => $salon->nom, 'type' => 'discussion', 'acces' => 'tous', 'sujets_actives' => true];
        $this->withToken($t)->putJson('/api/admin/communaute/salons/'.$salon->id, $donnees)->assertOk();
        $this->assertTrue($salon->fresh()->sujets_actives);
        // Le canal Annonces n'est jamais organisé en sujets.
        $this->withToken($t)->putJson('/api/admin/communaute/salons/'.$annonces->id, ['nom' => $annonces->nom, 'type' => 'annonces', 'acces' => 'tous', 'sujets_actives' => true])->assertOk();
        $this->assertFalse($annonces->fresh()->sujets_actives);
        $this->assertTrue(collect($this->withToken($t)->getJson('/api/admin/communaute')->json('salons'))->firstWhere('id', $salon->id)['sujets_actives']);
    }

    public function test_annonce_programmee_publiee_a_l_heure(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => ['salons'], 'prenom' => 'Bureau']);
        $membre = $this->membre(['prenom' => 'Awa']);
        $t = $this->token($admin);
        $annonces = Salon::where('type', 'annonces')->first();

        $this->withToken($t)->postJson('/api/admin/communaute/programmations', ['salon_id' => $annonces->id, 'body' => 'Assemblée générale samedi', 'publier_a' => now()->subHour()->format('Y-m-d\TH:i')])
            ->assertStatus(422);
        $quand = now()->addHours(2)->startOfMinute();
        $id = $this->withToken($t)->postJson('/api/admin/communaute/programmations', ['salon_id' => $annonces->id, 'body' => 'Assemblée générale samedi', 'publier_a' => $quand->format('Y-m-d\TH:i')])
            ->assertOk()->json('id');
        $autre = $this->withToken($t)->postJson('/api/admin/communaute/programmations', ['salon_id' => $annonces->id, 'body' => 'Annonce annulée', 'publier_a' => $quand->format('Y-m-d\TH:i')])->json('id');
        $this->withToken($t)->deleteJson('/api/admin/communaute/programmations/'.$autre)->assertOk();
        $this->assertSame([$id], array_column($this->withToken($t)->getJson('/api/admin/communaute/programmations')->json('a_venir'), 'id'));
        $this->withToken($this->token($membre))->getJson('/api/admin/communaute/programmations')->assertStatus(403);

        // Avant l'heure : rien ; à l'heure : publiée une seule fois, membres prévenus.
        $this->artisan('communaute:programmes')->assertSuccessful();
        $this->assertSame(0, SalonMessage::where('salon_id', $annonces->id)->count());
        $this->travel(2)->hours();
        $this->travel(1)->minutes();
        $this->artisan('communaute:programmes')->assertSuccessful();
        $this->artisan('communaute:programmes')->assertSuccessful();
        $publie = SalonMessage::where('salon_id', $annonces->id)->get();
        $this->assertCount(1, $publie);
        $this->assertSame('Assemblée générale samedi', $publie[0]->body);
        $this->assertSame($admin->id, $publie[0]->user_id);
        $this->assertSame($publie[0]->id, SalonProgrammation::find($id)->salon_message_id);
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', 'Nouvelle annonce du REJCC')->exists());
        $this->assertSame(0, SalonMessage::where('body', 'Annonce annulée')->count());
    }

    public function test_discussion_vocale_par_lien_et_par_meet(): void
    {
        $salon = Salon::where('slug', self::SALON)->first();
        $membre = $this->membre(['prenom' => 'Awa']);
        $referent = $this->membre(['prenom' => 'Paul']);
        [$tA, $tP] = [$this->token($membre), $this->token($referent)];
        $this->entrer($tA);
        $this->entrer($tP);
        SalonMembre::where('salon_id', $salon->id)->where('user_id', $referent->id)->update(['role' => 'moderateur']);
        $url = '/api/communaute/salons/'.self::SALON.'/vocal';

        // Réservé aux référents ; sans compte Meet, un lien https est demandé.
        $this->withToken($tA)->postJson($url, ['titre' => 'Café réseau', 'lien' => 'https://zoom.us/j/123'])->assertStatus(403);
        $this->withToken($tP)->postJson($url, ['titre' => 'Café réseau'])->assertStatus(422)->assertJsonPath('code', 'meet_indisponible');
        $this->withToken($tP)->postJson($url, ['titre' => 'Café réseau', 'lien' => 'http://zoom.us/j/123'])->assertStatus(422);

        Cache::flush(); // Awa n'a plus le salon sous les yeux : elle sera prévenue.
        $m = $this->withToken($tP)->postJson($url, ['titre' => 'Café réseau', 'lien' => 'https://zoom.us/j/123'])->assertOk()->json('message');
        $this->assertSame('direct', $m['type']);
        $this->assertTrue($m['direct']['en_cours']);
        $this->assertSame('https://zoom.us/j/123', $m['direct']['lien']);
        $this->withToken($tP)->postJson($url, ['titre' => 'Deuxième', 'lien' => 'https://zoom.us/j/456'])->assertStatus(422);

        // Tous les membres sont prévenus ; la liste des salons signale le direct.
        $this->assertTrue(MemberNotification::where('user_id', $membre->id)->where('title', '🎙️ Discussion vocale en direct dans « '.$salon->nom.' »')->exists());
        $liste = collect($this->withToken($tA)->getJson('/api/communaute')->json('salons'))->firstWhere('slug', self::SALON);
        $this->assertSame('Café réseau', $liste['direct']);
        $vu = collect($this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON)->json('messages'))->firstWhere('id', $m['id']);
        $this->assertFalse($vu['direct']['peut_terminer']);

        // Fin par le référent : le lien disparaît.
        $vocal = $m['direct']['id'];
        $this->withToken($tA)->postJson('/api/communaute/vocaux/'.$vocal.'/terminer')->assertStatus(403);
        $this->withToken($tP)->postJson('/api/communaute/vocaux/'.$vocal.'/terminer')->assertOk();
        $vu = collect($this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON)->json('messages'))->firstWhere('id', $m['id']);
        $this->assertFalse($vu['direct']['en_cours']);
        $this->assertNull($vu['direct']['lien']);

        // Avec le compte Google du REJCC : réunion Meet créée puis retirée de l'agenda à la fin automatique (3 h).
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        SiteSetting::create(['key' => 'google_meet', 'value' => ['email' => 'contact.rejcc@gmail.com', 'refresh_token' => Crypt::encryptString('rt')]]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at', 'expires_in' => 3600]),
            'www.googleapis.com/calendar/v3/*' => Http::response(['id' => 'evt9', 'hangoutLink' => 'https://meet.google.com/abc-defg-hij']),
        ]);
        $m = $this->withToken($tP)->postJson($url, ['titre' => 'Prière du soir'])->assertOk()->json('message');
        $this->assertSame('https://meet.google.com/abc-defg-hij', $m['direct']['lien']);
        $this->assertSame('meet', $m['direct']['fournisseur']);
        $this->travel(3)->hours();
        $this->travel(1)->minutes();
        $this->artisan('communaute:programmes')->assertSuccessful();
        $this->assertNotNull(SalonVocal::find($m['direct']['id'])->fin_at);
        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_contains($req->url(), 'events/evt9'));
    }
}

<?php

namespace Tests\Feature\Api;

use App\Mail\MessageCompte;
use App\Models\AccountEvent;
use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\PersonalDocument;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Cloture;
use App\Support\Notifications;
use App\Support\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParametresCompteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    private function tokenFor(User $u, string $agent = 'Mozilla/5.0 (Linux; Android 14) Chrome/128.0 Mobile'): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'web', 'agent' => $agent]);

        return $plain;
    }

    private function notif(User $u, string $link, int $minutes = 20, array $plus = []): MemberNotification
    {
        $n = MemberNotification::create(['user_id' => $u->id, 'type' => 'info', 'title' => 'Titre '.$link, 'body' => 'Texte', 'link' => $link] + $plus);
        $n->forceFill(['created_at' => now()->subMinutes($minutes)])->save();

        return $n;
    }

    private function terminer(): void
    {
        $this->app->terminate();
    }

    public function test_categories_et_emails_immediats_ou_resume(): void
    {
        $this->assertSame('messagerie', Notifications::categorie('/espace-membre/messagerie?to=3', 'message'));
        $this->assertSame('mentorat', Notifications::categorie('/espace-membre/mentorat/4'));
        $this->assertSame('formations', Notifications::categorie('/espace-membre/certificats?certificat=1'));
        $this->assertSame('reseau', Notifications::categorie('/espace-membre/emplois?offre=2'));
        $this->assertSame('compte', Notifications::categorie(null));

        $u = User::factory()->create();
        $msg = $this->notif($u, '/espace-membre/messagerie?to=9', 20, ['type' => 'message']);
        $recent = $this->notif($u, '/espace-membre/mentorat', 3);            // trop récente : le membre est peut-être en ligne
        $lue = $this->notif($u, '/espace-membre/mentorat/2', 20, ['read_at' => now()]);
        $evt = $this->notif($u, '/espace-membre/evenements?evenement=1', 60); // catégorie « résumé quotidien »

        $this->assertSame(1, Notifications::envoyerImmediats());
        $this->terminer();
        Mail::assertSent(MessageCompte::class, fn ($m) => $m->hasTo($u->email) && str_contains($m->sujet, $msg->title));
        $this->assertNotNull($msg->fresh()->email_at);
        $this->assertNull($recent->fresh()->email_at);
        $this->assertNull($lue->fresh()->email_at);
        $this->assertNull($evt->fresh()->email_at);
        // Pas de second e-mail pour la même notification.
        $this->assertSame(0, Notifications::envoyerImmediats());

        // Résumé quotidien : les nouveautés « quotidien » restées non lues.
        $this->assertSame(1, Notifications::envoyerResumes());
        $this->assertNotNull($evt->fresh()->email_at);
    }

    public function test_reglages_pause_et_emails_deja_dedies(): void
    {
        $u = User::factory()->create();
        $t = $this->tokenFor($u);
        $r = $this->withToken($t)->getJson('/api/auth/notifications')->assertOk();
        $this->assertSame('immediat', $r->json('reglages.email.messagerie'));
        $this->assertSame('quotidien', $r->json('reglages.email.evenements'));
        $this->assertCount(6, $r->json('categories'));

        $this->withToken($t)->putJson('/api/auth/notifications', ['email' => ['messagerie' => 'jamais', 'inconnue' => 'immediat'], 'push' => ['reseau' => true]])
            ->assertOk()->assertJsonPath('reglages.email.messagerie', 'jamais')->assertJsonPath('reglages.push.reseau', true);
        $this->assertArrayNotHasKey('inconnue', $u->fresh()->preferences['email']);
        $this->withToken($t)->putJson('/api/auth/notifications', ['email' => ['messagerie' => 'souvent']])->assertStatus(422);

        $this->notif($u, '/espace-membre/messagerie?to=2', 20, ['type' => 'message']);
        $this->assertSame(0, Notifications::envoyerImmediats());

        // Pause des e-mails jusqu'à une date.
        $this->withToken($t)->putJson('/api/auth/notifications', ['email' => ['mentorat' => 'immediat'], 'pause_emails' => now()->addDays(5)->toDateString()])->assertOk();
        $this->notif($u, '/espace-membre/mentorat', 20);
        $this->assertSame(0, Notifications::envoyerImmediats());
        $this->withToken($t)->putJson('/api/auth/notifications', ['pause_emails' => now()->addYear()->toDateString()])->assertStatus(422);
        $this->withToken($t)->putJson('/api/auth/notifications', ['pause_emails' => null])->assertOk();
        $this->assertSame(1, Notifications::envoyerImmediats());

        // Une notification accompagnée d'un e-mail dédié n'est pas renvoyée.
        $this->notif($u, '/espace-membre/certificats', 20, ['email_at' => now()->subMinutes(20)]);
        $this->assertSame(0, Notifications::envoyerResumes());
    }

    public function test_preferences_connues_et_newsletter_synchronisee(): void
    {
        $u = User::factory()->create();
        $t = $this->tokenFor($u);
        $this->withToken($t)->putJson('/api/auth/preferences', ['preferences' => ['newsletter' => false, 'telechargement_hors_ligne' => true, 'texte_grand' => true]])
            ->assertOk()->assertJsonPath('preferences.texte_grand', true)->assertJsonMissingPath('preferences.telechargement_hors_ligne');
        $this->assertFalse(NewsletterSubscriber::where('email', $u->email)->exists());
        $this->withToken($t)->putJson('/api/auth/preferences', ['preferences' => ['newsletter' => true, 'apparaitre_annuaire' => false]])->assertOk();
        $this->assertTrue(NewsletterSubscriber::where('email', $u->email)->exists());
        $this->assertTrue(AccountEvent::where('user_id', $u->id)->where('type', 'confidentialite')->exists());
    }

    public function test_appareils_et_mot_de_passe(): void
    {
        $u = User::factory()->create(['password' => 'Ancien123']);
        $ici = $this->tokenFor($u);
        $this->tokenFor($u, 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/604.1');
        $autre = ApiToken::where('user_id', $u->id)->latest('id')->first();

        $r = $this->withToken($ici)->withHeaders(['X-Client-Agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/128.0', 'X-Client-Ip' => '41.207.1.2'])
            ->getJson('/api/auth/appareils')->assertOk();
        $this->assertCount(2, $r->json('appareils'));
        $courant = collect($r->json('appareils'))->firstWhere('courant', true);
        $this->assertSame('Chrome sur Windows', $courant['appareil']);
        $this->assertSame('41.207.1.2', $courant['ip']);
        $this->assertSame('Safari sur iPhone', collect($r->json('appareils'))->firstWhere('courant', false)['appareil']);

        $this->withToken($ici)->deleteJson('/api/auth/appareils/'.$courant['id'])->assertStatus(404);
        $this->withToken($ici)->deleteJson('/api/auth/appareils/'.$autre->id)->assertOk();
        $this->assertSame(1, ApiToken::where('user_id', $u->id)->count());

        // Mot de passe : règles, ancien identique refusé, autres appareils déconnectés, alerte e-mail.
        $this->tokenFor($u);
        $this->withToken($ici)->putJson('/api/auth/password', ['current_password' => 'Ancien123', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'])
            ->assertStatus(422)->assertJsonPath('message', 'Le mot de passe doit contenir au moins une lettre et un chiffre.');
        $this->withToken($ici)->putJson('/api/auth/password', ['current_password' => 'faux', 'password' => 'Nouveau123', 'password_confirmation' => 'Nouveau123'])
            ->assertStatus(422)->assertJsonPath('champ', 'current_password');
        $this->withToken($ici)->putJson('/api/auth/password', ['current_password' => 'Ancien123', 'password' => 'Nouveau123', 'password_confirmation' => 'Nouveau123'])
            ->assertOk()->assertJsonPath('deconnectes', 1);
        $this->assertSame(1, ApiToken::where('user_id', $u->id)->count());
        Mail::assertSent(MessageCompte::class, fn ($m) => str_contains($m->sujet, 'mot de passe a été modifié'));
        $this->assertTrue(AccountEvent::where('user_id', $u->id)->where('type', 'mot_de_passe')->exists());

        $journal = $this->withToken($ici)->getJson('/api/auth/journal')->assertOk()->json('evenements');
        $this->assertSame('Mot de passe modifié', $journal[0]['libelle']);
    }

    public function test_connexion_journalisee_et_limite_par_email(): void
    {
        $u = User::factory()->create(['password' => 'Secret123']);
        $this->withHeaders(['X-Client-Agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/128.0 Mobile', 'X-Client-Ip' => '102.67.1.1'])
            ->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'Secret123'])->assertOk();
        $e = AccountEvent::where('user_id', $u->id)->where('type', 'connexion')->first();
        $this->assertSame('102.67.1.1', $e->ip);
        $this->assertSame('102.67.1.1', ApiToken::where('user_id', $u->id)->first()->ip);

        // 5 essais par minute pour une même adresse e-mail, quelle que soit l'adresse IP du visiteur.
        foreach (range(1, 4) as $i) {
            $this->withHeaders(['X-Client-Ip' => "10.0.0.{$i}"])->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'faux'])->assertStatus(401);
        }
        $this->withHeaders(['X-Client-Ip' => '10.0.0.9'])->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'faux'])->assertStatus(429);
        // Un autre membre, depuis le même serveur, n'est pas bloqué.
        $v = User::factory()->create(['password' => 'Secret123']);
        $this->withHeaders(['X-Client-Ip' => '10.0.0.20'])->postJson('/api/auth/login', ['email' => $v->email, 'password' => 'Secret123'])->assertOk();
    }

    public function test_changement_d_adresse_email(): void
    {
        $u = User::factory()->create(['password' => 'Secret123', 'email' => 'ancien@example.com']);
        NewsletterSubscriber::create(['email' => 'ancien@example.com']);
        User::factory()->create(['email' => 'pris@example.com']);
        $t = $this->tokenFor($u);

        $this->withToken($t)->postJson('/api/auth/email', ['email' => 'pris@example.com', 'password' => 'Secret123'])->assertStatus(422);
        $this->withToken($t)->postJson('/api/auth/email', ['email' => 'nouveau@example.com', 'password' => 'faux'])->assertStatus(422);
        $this->withToken($t)->postJson('/api/auth/email', ['email' => 'Nouveau@Example.com', 'password' => 'Secret123'])->assertOk();
        $this->assertSame('nouveau@example.com', $u->fresh()->email_nouveau);
        $this->assertSame('ancien@example.com', $u->fresh()->email);

        $jeton = null;
        Mail::assertSent(MessageCompte::class, function ($m) use (&$jeton) {
            if ($m->hasTo('nouveau@example.com') && preg_match('#/confirmer-email/([A-Za-z0-9]+)#', $m->contenu, $x)) {
                $jeton = $x[1];
            }

            return $m->hasTo('nouveau@example.com');
        });
        Mail::assertSent(MessageCompte::class, fn ($m) => $m->hasTo('ancien@example.com'));

        $this->postJson('/api/auth/email/confirmer', ['jeton' => 'mauvais-jeton-de-quarante-huit-caracteres-xxxxxx'])->assertStatus(422);
        $this->postJson('/api/auth/email/confirmer', ['jeton' => $jeton])->assertOk();
        $this->assertSame('nouveau@example.com', $u->fresh()->email);
        $this->assertTrue(NewsletterSubscriber::where('email', 'nouveau@example.com')->exists());
        $this->postJson('/api/auth/email/confirmer', ['jeton' => $jeton])->assertStatus(422);
    }

    public function test_export_cloture_annulation_et_anonymisation(): void
    {
        $u = User::factory()->create(['password' => 'Secret123', 'telephone' => '0701020304', 'bio' => 'Ma vie']);
        $t = $this->tokenFor($u);
        Payment::create(['user_id' => $u->id, 'type' => 'abonnement', 'reference' => 'ABO-X', 'provider' => 'cinetpay', 'amount' => 10000, 'currency' => 'XOF', 'status' => 'success']);
        PersonalDocument::create(['user_id' => $u->id, 'type' => 'cni', 'fichier' => "personnels/{$u->id}/a.enc", 'fichier_nom' => 'cni.pdf', 'mime' => 'application/pdf', 'octets' => 10]);

        $export = $this->withToken($t)->getJson('/api/auth/export')->assertOk()->json('donnees');
        $this->assertSame($u->email, $export['profil']['email']);
        $this->assertArrayNotHasKey('password', $export['profil']);
        $this->assertCount(1, $export['abonnement_paiements']);
        $this->assertArrayNotHasKey('fichier', $export['documents_personnels'][0]);

        $this->withToken($t)->postJson('/api/auth/cloture', ['password' => 'faux'])->assertStatus(422);
        $r = $this->withToken($t)->postJson('/api/auth/cloture', ['password' => 'Secret123', 'motif' => 'Je déménage'])->assertOk();
        $this->assertSame(["personnels/{$u->id}/a.enc"], $r->json('fichiers'));
        $this->assertFalse($u->fresh()->is_active);
        $this->assertSame(0, ApiToken::where('user_id', $u->id)->count());
        $this->assertSame(0, PersonalDocument::where('user_id', $u->id)->count());

        // Se reconnecter avant l'échéance annule la clôture.
        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'Secret123'])->assertOk()->assertJsonPath('cloture_annulee', true);
        $this->assertTrue($u->fresh()->is_active);
        $this->assertNull($u->fresh()->suppression_prevue_at);

        // Nouvelle clôture, puis échéance : anonymisation.
        $t = $this->tokenFor($u);
        $this->withToken($t)->postJson('/api/auth/cloture', ['password' => 'Secret123'])->assertOk();
        $this->travel(31)->days();
        $this->assertSame(1, Cloture::anonymiserEcheances());
        $f = $u->fresh();
        $this->assertSame('Membre supprimé', $f->name);
        $this->assertNull($f->telephone);
        $this->assertNull($f->bio);
        $this->assertStringEndsWith('@rejcc.invalid', $f->email);
        $this->assertSame(1, Payment::where('user_id', $u->id)->count()); // conservé (obligation légale)
        $this->postJson('/api/auth/login', ['email' => $f->email, 'password' => 'Secret123'])->assertStatus(401);
        $this->assertSame(0, Cloture::anonymiserEcheances());
    }

    public function test_un_administrateur_ne_peut_pas_cloturer_son_compte(): void
    {
        $a = User::factory()->create(['role' => 'admin', 'password' => 'Secret123']);
        $this->withToken($this->tokenFor($a))->postJson('/api/auth/cloture', ['password' => 'Secret123'])->assertStatus(422);
    }

    public function test_notifications_sur_les_appareils(): void
    {
        $this->getJson('/api/push/cle')->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(65, strlen(WebPush::deb64(WebPush::clePublique())));

        $u = User::factory()->create();
        $t = $this->tokenFor($u);
        // Clé d'appareil de test (paire EC P-256 réelle).
        $k = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $d = openssl_pkey_get_details($k)['ec'];
        $p256dh = WebPush::b64("\x04".str_pad($d['x'], 32, "\0", STR_PAD_LEFT).str_pad($d['y'], 32, "\0", STR_PAD_LEFT));

        $this->withToken($t)->postJson('/api/auth/push', ['endpoint' => 'https://push.example.com/a', 'keys' => ['p256dh' => 'court', 'auth' => 'x']])->assertStatus(422);
        $this->withToken($t)->postJson('/api/auth/push', ['endpoint' => 'https://push.example.com/a', 'keys' => ['p256dh' => $p256dh, 'auth' => WebPush::b64(random_bytes(16))]])->assertOk();
        $this->withToken($t)->postJson('/api/auth/push', ['endpoint' => 'https://push.example.com/b', 'keys' => ['p256dh' => $p256dh, 'auth' => WebPush::b64(random_bytes(16))]])->assertOk();
        $this->assertSame(2, PushSubscription::where('user_id', $u->id)->count());

        Http::fake(['push.example.com/a' => Http::response('', 201), 'push.example.com/b' => Http::response('', 410)]);
        $this->notif($u, '/espace-membre/messagerie?to=2', 1, ['type' => 'message']);
        $this->notif($u, '/espace-membre/emplois', 1); // « réseau » : pas sur le téléphone par défaut
        $this->assertSame(1, Notifications::envoyerPush());
        Http::assertSent(fn ($r) => $r->url() === 'https://push.example.com/a' && $r->hasHeader('Content-Encoding', 'aes128gcm') && str_starts_with($r->header('Authorization')[0], 'vapid t='));
        // Abonnement expiré (410) supprimé ; rien n'est renvoyé deux fois.
        $this->assertSame(1, PushSubscription::where('user_id', $u->id)->count());
        $this->assertSame(0, Notifications::envoyerPush());

        $this->withToken($t)->deleteJson('/api/auth/push')->assertOk();
        $this->assertSame(0, PushSubscription::where('user_id', $u->id)->count());
    }
}

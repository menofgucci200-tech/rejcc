<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function tokenFor(User $u): string
    {
        $plain = Str::random(60);
        ApiToken::create(['user_id' => $u->id, 'token' => hash('sha256', $plain), 'name' => 'test']);

        return $plain;
    }

    protected function abonne(array $attrs = []): User
    {
        return User::factory()->create(['role' => 'member', 'subscription_expires_at' => now()->addYear()] + $attrs);
    }

    public function test_destinataires_invalides_et_messages_vides_sont_refuses_avec_un_message_clair(): void
    {
        $moi = $this->abonne();
        $token = $this->tokenFor($moi);
        $suspendu = $this->abonne(['is_active' => false]);

        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $moi->id, 'body' => 'Bonjour'])
            ->assertStatus(422)->assertJsonPath('message', 'Ce destinataire est introuvable.');
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => 99999, 'body' => 'Bonjour'])
            ->assertStatus(422)->assertJsonPath('message', 'Ce destinataire est introuvable.');
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $suspendu->id, 'body' => 'Bonjour'])
            ->assertStatus(422)->assertJsonPath('message', 'Ce compte est suspendu : il ne peut pas recevoir de messages.');
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $suspendu->id, 'body' => '   '])
            ->assertStatus(422)->assertJsonPath('message', "Écrivez votre message avant de l'envoyer.");
        $this->withToken($token)->getJson('/api/messages/99999')->assertStatus(404);
        $this->assertSame(0, Message::count());
    }

    public function test_fil_incremental_et_lecture(): void
    {
        $awa = $this->abonne(['prenom' => 'Awa']);
        $esther = $this->abonne(['prenom' => 'Esther']);
        $tA = $this->tokenFor($awa);
        $tE = $this->tokenFor($esther);

        $premier = $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => "Bonjour Esther,\nêtes-vous disponible ?"])
            ->assertOk()->json('message.id');
        $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => 'Samedi ?'])->assertOk();

        // Seuls les messages après « premier » sont renvoyés ; rien n'est encore lu.
        $fil = $this->withToken($tA)->getJson("/api/messages/{$esther->id}?after={$premier}")->assertOk()->json();
        $this->assertCount(1, $fil['messages']);
        $this->assertSame('Samedi ?', $fil['messages'][0]['body']);
        $this->assertSame(0, $fil['vu_jusqua']);
        $this->assertSame('Esther', $fil['partner']['prenom']);

        // Esther ouvre le fil : les deux messages sont lus, Awa voit « Vu ».
        $this->withToken($tE)->getJson("/api/messages/{$awa->id}")->assertOk()->assertJsonCount(2, 'messages');
        $fil = $this->withToken($tA)->getJson("/api/messages/{$esther->id}")->json();
        $this->assertSame($fil['messages'][1]['id'], $fil['vu_jusqua']);
        $this->assertSame("Bonjour Esther,\nêtes-vous disponible ?", $fil['messages'][0]['body']);

        $conv = $this->withToken($tA)->getJson('/api/messages')->json('conversations.0');
        $this->assertTrue($conv['last_moi']);
        $this->assertTrue($conv['last_vu']);
        $this->assertSame(0, $conv['unread']);
    }

    public function test_une_seule_notification_par_conversation_avec_extrait(): void
    {
        $awa = $this->abonne(['prenom' => 'Awa', 'nom' => 'Traoré']);
        $esther = $this->abonne();
        $tA = $this->tokenFor($awa);

        foreach (['Bonjour Esther', "Êtes-vous\ndisponible samedi ?", 'Merci !'] as $texte) {
            $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => $texte])->assertOk();
        }

        $notifs = \App\Models\MemberNotification::where('user_id', $esther->id)->where('type', 'message')->get();
        $this->assertCount(1, $notifs);
        $this->assertSame('Message de Awa Traoré', $notifs[0]->title);
        $this->assertSame('« Merci ! » · 3 messages non lus', $notifs[0]->body);
        $this->assertSame("/espace-membre/messagerie?to={$awa->id}", $notifs[0]->link);

        // Esther ouvre le fil : la notification est lue, et tant que le fil est
        // ouvert, les nouveaux messages d'Awa ne créent pas de notification.
        $this->withToken($this->tokenFor($esther))->getJson("/api/messages/{$awa->id}")->assertOk();
        $this->assertNotNull($notifs[0]->fresh()->read_at);
        $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => 'Encore là ?'])->assertOk();
        $this->assertSame(0, \App\Models\MemberNotification::where('user_id', $esther->id)->whereNull('read_at')->count());

        // Fil refermé (cache expiré) : nouvelle notification.
        \Illuminate\Support\Facades\Cache::flush();
        $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $esther->id, 'body' => 'Bonne soirée'])->assertOk();
        $this->assertSame('« Bonne soirée » · 2 messages non lus', \App\Models\MemberNotification::where('user_id', $esther->id)->whereNull('read_at')->value('body'));
    }

    public function test_bloquer_un_membre(): void
    {
        $awa = $this->abonne();
        $intrus = $this->abonne();
        $tA = $this->tokenFor($awa);
        $tI = $this->tokenFor($intrus);

        $this->withToken($tI)->postJson('/api/messages', ['recipient_id' => $awa->id, 'body' => 'Achetez mes produits !'])->assertOk();
        $this->withToken($tA)->postJson("/api/messages/{$intrus->id}/bloquer")->assertOk();

        $this->withToken($tI)->postJson('/api/messages', ['recipient_id' => $awa->id, 'body' => 'Encore moi'])
            ->assertStatus(403)->assertJsonPath('message', 'Ce membre ne reçoit plus vos messages.');
        $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $intrus->id, 'body' => 'Stop'])
            ->assertStatus(422)->assertJsonPath('message', 'Vous avez bloqué ce membre. Débloquez-le pour lui écrire.');
        $this->withToken($tA)->getJson("/api/messages/{$intrus->id}")->assertJsonPath('bloque', true)->assertJsonPath('peut_ecrire', false);
        $this->withToken($tA)->getJson('/api/messages')->assertJsonPath('conversations.0.bloque', true);

        $this->withToken($tA)->deleteJson("/api/messages/{$intrus->id}/bloquer")->assertOk();
        $this->withToken($tI)->postJson('/api/messages', ['recipient_id' => $awa->id, 'body' => 'Merci'])->assertOk();
    }

    public function test_archiver_jusqu_au_prochain_message(): void
    {
        $awa = $this->abonne();
        $paul = $this->abonne();
        $tA = $this->tokenFor($awa);
        $this->withToken($tA)->postJson('/api/messages', ['recipient_id' => $paul->id, 'body' => 'Bonjour'])->assertOk();

        $this->travel(1)->seconds();
        $this->withToken($tA)->postJson("/api/messages/{$paul->id}/archiver")->assertOk();
        $this->withToken($tA)->getJson('/api/messages')->assertJsonCount(0, 'conversations')->assertJsonPath('archives', 1);
        $this->withToken($tA)->getJson('/api/messages?archives=1')->assertJsonCount(1, 'conversations');

        // Un nouveau message de Paul fait réapparaître la conversation.
        $this->travel(1)->seconds();
        $this->withToken($this->tokenFor($paul))->postJson('/api/messages', ['recipient_id' => $awa->id, 'body' => 'Re !'])->assertOk();
        $this->withToken($tA)->getJson('/api/messages')->assertJsonCount(1, 'conversations')->assertJsonPath('archives', 0);
    }

    public function test_anti_spam(): void
    {
        $moi = $this->abonne();
        $token = $this->tokenFor($moi);
        $autres = User::factory()->count(21)->create(['role' => 'member']);

        // 20 nouvelles conversations par 24 h (espacées pour ne pas buter sur la limite par minute).
        foreach ($autres->take(20) as $i => $u) {
            $this->travel(5)->seconds();
            $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $u->id, 'body' => "Bonjour {$i}"])->assertOk();
        }
        $this->travel(2)->minutes();
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $autres[20]->id, 'body' => 'Une de trop'])
            ->assertStatus(429)->assertJsonPath('message', 'Vous avez démarré 20 nouvelles conversations en 24 h : réessayez demain.');
        // Répondre dans une conversation existante reste possible.
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $autres[0]->id, 'body' => 'Suite'])->assertOk();

        // Plus de 20 messages en une minute.
        for ($i = 0; $i < 19; $i++) {
            $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $autres[0]->id, 'body' => "m{$i}"])->assertOk();
        }
        $this->withToken($token)->postJson('/api/messages', ['recipient_id' => $autres[0]->id, 'body' => 'trop'])->assertStatus(429);
    }

    public function test_signaler_une_conversation_et_traitement_admin(): void
    {
        $awa = $this->abonne();
        $intrus = $this->abonne(['prenom' => 'Intrus']);
        $tA = $this->tokenFor($awa);

        // Rien à signaler tant que l'autre n'a pas écrit.
        $this->withToken($tA)->postJson("/api/messages/{$intrus->id}/signaler")->assertStatus(422);

        $this->withToken($this->tokenFor($intrus))->postJson('/api/messages', ['recipient_id' => $awa->id, 'body' => 'Propos injurieux'])->assertOk();
        $this->withToken($tA)->postJson("/api/messages/{$intrus->id}/signaler", ['motif' => 'Insultes', 'bloquer' => true])->assertOk();
        $this->withToken($tA)->getJson("/api/messages/{$intrus->id}")->assertJsonPath('signalee', true)->assertJsonPath('bloque', true);

        $admin = $this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['messagerie']]));
        $this->withToken($this->tokenFor(User::factory()->create(['role' => 'admin', 'permissions' => ['formations']])))
            ->getJson('/api/admin/signalements-messages')->assertStatus(403);
        $liste = $this->withToken($admin)->getJson('/api/admin/signalements-messages')->assertOk()->json('signalements');
        $this->assertSame('Insultes', $liste[0]['motif']);
        $this->assertSame(1, $liste[0]['messages']);
        $this->withToken($admin)->getJson('/api/admin/a-traiter')->assertJsonFragment(['cle' => 'signalements', 'nombre' => 1]);

        $this->withToken($admin)->getJson("/api/admin/signalements-messages/{$liste[0]['id']}")->assertOk()
            ->assertJsonPath('messages.0.body', 'Propos injurieux')->assertJsonPath('messages.0.de_signale', true);

        $this->withToken($admin)->putJson("/api/admin/signalements-messages/{$liste[0]['id']}", ['decision' => 'averti'])->assertOk();
        $this->assertSame(1, \App\Models\MemberNotification::where('user_id', $intrus->id)->where('title', 'Avertissement de la modération')->count());
        $this->assertSame(1, \App\Models\MemberNotification::where('user_id', $awa->id)->where('title', 'Votre signalement a été traité')->count());
        $this->withToken($admin)->getJson('/api/admin/signalements-messages')->assertJsonCount(0, 'signalements');
    }

    public function test_non_abonne_lit_et_repond_mais_ne_demarre_pas(): void
    {
        \App\Support\SubscriptionMode::set(true);
        $awa = $this->abonne(['prenom' => 'Awa']);
        $koffi = User::factory()->create(['role' => 'member', 'prenom' => 'Koffi']); // non abonné
        $paul = User::factory()->create(['role' => 'member']);
        $tK = $this->tokenFor($koffi);

        // Koffi ne peut pas démarrer de conversation.
        $this->withToken($tK)->postJson('/api/messages', ['recipient_id' => $paul->id, 'body' => 'Bonjour'])
            ->assertStatus(402)->assertJsonPath('code', 'subscription_required');
        $this->withToken($tK)->getJson("/api/messages/{$paul->id}")->assertStatus(402);

        // Awa (abonnée) lui écrit : Koffi voit la conversation, la lit et répond.
        $this->withToken($this->tokenFor($awa))->postJson('/api/messages', ['recipient_id' => $koffi->id, 'body' => 'Bonjour Koffi'])->assertOk();
        // Une conversation que Koffi aurait commencée avant de ne plus être abonné reste masquée.
        Message::create(['sender_id' => $koffi->id, 'recipient_id' => $paul->id, 'body' => 'Ancien message']);

        $this->withToken($tK)->getJson('/api/messages')->assertOk()
            ->assertJsonPath('restreint', true)->assertJsonCount(1, 'conversations')->assertJsonPath('conversations.0.prenom', 'Awa');
        $this->withToken($tK)->getJson("/api/messages/{$awa->id}")->assertOk()->assertJsonPath('messages.0.body', 'Bonjour Koffi');
        $this->withToken($tK)->postJson('/api/messages', ['recipient_id' => $awa->id, 'body' => 'Merci Awa !'])->assertOk();

        // Les mentors sont dispensés d'abonnement : ils démarrent librement.
        $mentor = User::factory()->create(['role' => 'mentor']);
        $this->withToken($this->tokenFor($mentor))->postJson('/api/messages', ['recipient_id' => $paul->id, 'body' => 'Bienvenue'])->assertOk();
    }
}

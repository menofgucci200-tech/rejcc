<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\MemberNotification;
use App\Models\Salon;
use App\Models\SalonMembre;
use App\Models\SalonMessage;
use App\Models\SalonModeration;
use App\Models\SalonSignalement;
use App\Models\User;
use App\Support\Communaute;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Communauté REJCC : salons, messages, notifications et modération. */
class CommunauteTest extends TestCase
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

    private function abonne(array $attrs = []): User
    {
        return $this->membre(['subscription_expires_at' => now()->addYear()] + $attrs);
    }

    private function admin(?array $permissions = null): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => $permissions]);
    }

    /** Ouvre le salon et accepte ses règles (comme le fait un membre avant d'écrire). */
    private function entrer(string $token, string $slug = self::SALON): void
    {
        $this->withToken($token)->getJson("/api/communaute/salons/{$slug}")->assertOk();
        $this->withToken($token)->postJson("/api/communaute/salons/{$slug}/regles")->assertOk();
    }

    private function ecrire(string $token, string $body, array $extra = [], string $slug = self::SALON)
    {
        return $this->withToken($token)->postJson("/api/communaute/salons/{$slug}/messages", ['type' => 'texte', 'body' => $body] + $extra);
    }

    public function test_les_salons_des_communautes_whatsapp_sont_crees_et_listes(): void
    {
        $t = $this->token($this->membre());
        $r = $this->withToken($t)->getJson('/api/communaute')->assertOk()->json();

        $this->assertSame(
            ['Annonces', 'Spiritualité et Leadership Chrétien', 'Échanges & Networking', 'Entrepreneurs & Projets', 'Opportunités & Formations'],
            collect($r['salons'])->pluck('nom')->all()
        );
        $this->assertSame('annonces', $r['salons'][0]['type']);
        $this->assertSame('tout', $r['salons'][0]['notifications']);
        $this->assertSame('mentions', $r['salons'][1]['notifications']);
        $this->assertSame(['👍', '🙏', '❤️', '😂', '😮', '👏'], $r['reactions']);
    }

    public function test_un_membre_accepte_les_regles_puis_ecrit_et_les_autres_voient_ses_messages_non_lus(): void
    {
        $awa = $this->membre(['prenom' => 'Awa']);
        $esther = $this->membre(['prenom' => 'Esther']);
        $tA = $this->token($awa);
        $tE = $this->token($esther);

        $this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON)->assertOk()
            ->assertJsonPath('moi.refus_ecriture', 'Acceptez les règles du salon pour y écrire.');
        $this->ecrire($tA, 'Bonjour à tous')->assertStatus(403)->assertJsonPath('code', 'ecriture_refusee');

        // Esther passe dans le salon avant le message d'Awa.
        $this->withToken($tE)->getJson('/api/communaute/salons/'.self::SALON)->assertOk();

        $this->withToken($tA)->postJson('/api/communaute/salons/'.self::SALON.'/regles')->assertOk();
        $m = $this->ecrire($tA, "Bonjour à tous,\nqui sera au forum samedi ?")->assertOk()->json('message');
        $this->assertSame('texte', $m['type']);
        $this->assertTrue($m['moi']);

        $liste = $this->withToken($tE)->getJson('/api/communaute')->json('salons');
        $salon = collect($liste)->firstWhere('slug', self::SALON);
        $this->assertSame(1, $salon['non_lus']);
        $this->assertSame('Awa', $salon['dernier']['auteur']);
        $this->assertSame(1, $this->withToken($tE)->getJson('/api/nav-compteurs')->json('compteurs.communaute'));

        // Ouvrir le salon le marque comme lu ; le rafraîchissement ne renvoie que les nouveaux messages.
        $fil = $this->withToken($tE)->getJson('/api/communaute/salons/'.self::SALON)->json();
        $this->assertCount(1, $fil['messages']);
        $this->assertSame(0, $this->withToken($tE)->getJson('/api/nav-compteurs')->json('compteurs.communaute'));
        $this->assertCount(0, $this->withToken($tE)->getJson('/api/communaute/salons/'.self::SALON.'?after='.$m['id'])->json('messages'));
    }

    public function test_seule_l_equipe_publie_dans_les_annonces_et_tout_le_monde_est_prevenu(): void
    {
        $membre = $this->membre();
        $autre = $this->membre();
        $sourdine = $this->membre();
        $adminProjets = $this->admin(['projets']);
        $admin = $this->admin();

        $this->ecrire($this->token($membre), 'Info', [], 'annonces')->assertStatus(403)
            ->assertJsonPath('message', "Seule l'équipe du REJCC publie dans les Annonces. Vous pouvez réagir aux messages.");
        $this->ecrire($this->token($adminProjets), 'Info', [], 'annonces')->assertStatus(403);

        $tS = $this->token($sourdine);
        $this->withToken($tS)->getJson('/api/communaute/salons/annonces')->assertOk();
        $this->withToken($tS)->putJson('/api/communaute/salons/annonces/notifications', ['niveau' => 'rien'])->assertOk();

        $this->ecrire($this->token($admin), 'Assemblée générale le 15 novembre à 9h00.', [], 'annonces')->assertOk();

        $this->assertDatabaseHas('member_notifications', ['user_id' => $membre->id, 'type' => 'communaute', 'title' => 'Nouvelle annonce du REJCC']);
        $this->assertDatabaseHas('member_notifications', ['user_id' => $autre->id, 'type' => 'communaute']);
        $this->assertDatabaseMissing('member_notifications', ['user_id' => $sourdine->id, 'type' => 'communaute']);
        $this->assertDatabaseMissing('member_notifications', ['user_id' => $admin->id, 'type' => 'communaute']);
    }

    public function test_mentions_et_reponses_notifient_les_bonnes_personnes_une_seule_fois(): void
    {
        $awa = $this->membre(['prenom' => 'Awa', 'nom' => 'Koné']);
        $esther = $this->membre(['prenom' => 'Esther', 'nom' => 'Yao']);
        $marc = $this->membre(['prenom' => 'Marc']);
        $tA = $this->token($awa);
        $tE = $this->token($esther);
        $this->entrer($tA);
        $this->entrer($tE);
        $this->withToken($this->token($marc))->getJson('/api/communaute/salons/'.self::SALON)->assertOk();
        Cache::flush(); // personne n'a plus le salon sous les yeux

        $this->ecrire($tA, '@Esther Yao tu as le programme ?', ['mentions' => [$esther->id, 999999]])->assertOk();
        $notif = MemberNotification::where('user_id', $esther->id)->where('type', 'communaute')->sole();
        $this->assertSame('Awa Koné vous a mentionné dans « Échanges & Networking »', $notif->title);
        $this->assertSame('/espace-membre/communaute?salon='.self::SALON, $notif->link);
        // Marc ne suit que ses mentions : rien pour lui.
        $this->assertDatabaseMissing('member_notifications', ['user_id' => $marc->id]);

        // Un second message ordinaire met à jour la même notification sans effacer la mention.
        $this->ecrire($tA, 'Merci !')->assertOk();
        $this->assertSame(1, MemberNotification::where('user_id', $esther->id)->count());
        $this->assertStringContainsString('vous a mentionné', MemberNotification::where('user_id', $esther->id)->value('title'));

        // Réponse : l'auteur du message cité est prévenu, sauf s'il a le salon sous les yeux.
        $cite = SalonMessage::where('user_id', $awa->id)->first();
        $this->ecrire($tE, 'Oui, je te l’envoie', ['reponse_a_id' => $cite->id])->assertOk()->assertJsonPath('message.reponse_a.auteur', 'Awa Koné');
        $this->assertSame('Esther Yao vous a répondu dans « Échanges & Networking »', MemberNotification::where('user_id', $awa->id)->value('title'));

        MemberNotification::query()->delete();
        Communaute::marquerPresent($awa->id, Salon::where('slug', self::SALON)->value('id'));
        $this->ecrire($tE, 'Encore une chose', ['reponse_a_id' => $cite->id])->assertOk();
        $this->assertDatabaseMissing('member_notifications', ['user_id' => $awa->id]);
        Cache::flush();

        // Suivre « tous les messages » d'un salon.
        $this->withToken($this->token($marc))->putJson('/api/communaute/salons/'.self::SALON.'/notifications', ['niveau' => 'tout'])->assertOk();
        $this->ecrire($tE, 'Rendez-vous à 10h')->assertOk();
        $this->assertSame('Nouveau message dans « Échanges & Networking »', MemberNotification::where('user_id', $marc->id)->value('title'));
    }

    public function test_photos_et_documents_reserves_aux_abonnes_notes_vocales_pour_tous(): void
    {
        SubscriptionMode::set(true);
        $libre = $this->membre();
        $abonne = $this->abonne();
        $tL = $this->token($libre);
        $tA = $this->token($abonne);
        $this->entrer($tL);
        $this->entrer($tA);

        $photo = ['type' => 'image', 'fichier' => 'communaute/2026/10/photo.jpg', 'fichier_nom' => 'forum.jpg', 'fichier_mime' => 'image/jpeg', 'fichier_taille' => 120000];
        $this->withToken($tL)->postJson('/api/communaute/salons/'.self::SALON.'/messages', $photo)->assertStatus(402)->assertJsonPath('code', 'subscription_required');
        $this->withToken($tA)->postJson('/api/communaute/salons/'.self::SALON.'/messages', $photo + ['body' => 'Souvenir du forum'])->assertOk()
            ->assertJsonPath('message.type', 'image')->assertJsonPath('message.fichier.nom', 'forum.jpg');

        $vocal = ['type' => 'vocal', 'fichier' => 'communaute/2026/10/note.webm', 'fichier_nom' => 'note.webm', 'fichier_mime' => 'audio/webm;codecs=opus', 'fichier_taille' => 40000, 'duree' => 42];
        $this->withToken($tL)->postJson('/api/communaute/salons/'.self::SALON.'/messages', $vocal)->assertOk()->assertJsonPath('message.fichier.duree', 42);
        $this->withToken($tL)->postJson('/api/communaute/salons/'.self::SALON.'/messages', ['duree' => 400] + $vocal)->assertStatus(422);

        // Chemins et formats contrôlés.
        $this->withToken($tA)->postJson('/api/communaute/salons/'.self::SALON.'/messages', ['fichier' => '../.env'] + $photo)->assertStatus(422);
        $this->withToken($tA)->postJson('/api/communaute/salons/'.self::SALON.'/messages', ['type' => 'fichier', 'fichier_mime' => 'application/x-msdownload'] + $photo)
            ->assertStatus(422)->assertJsonPath('message', 'Seuls les documents PDF sont acceptés.');
        $this->withToken($tA)->postJson('/api/communaute/salons/'.self::SALON.'/messages', ['fichier_taille' => 20 * 1024 * 1024] + $photo)->assertStatus(422);

        // Accès au fichier : membres du salon seulement.
        $id = SalonMessage::where('type', 'image')->value('id');
        $this->withToken($tL)->getJson("/api/communaute/messages/{$id}/fichier")->assertOk()->assertJsonPath('fichier.chemin', 'communaute/2026/10/photo.jpg');
    }

    public function test_modifier_dans_les_15_minutes_supprimer_reagir(): void
    {
        $awa = $this->membre();
        $esther = $this->membre();
        $tA = $this->token($awa);
        $tE = $this->token($esther);
        $this->entrer($tA);
        $this->entrer($tE);

        $id = $this->ecrire($tA, 'Bonjur')->json('message.id');
        $this->withToken($tA)->putJson("/api/communaute/messages/{$id}", ['body' => 'Bonjour'])->assertOk();
        $this->withToken($tE)->putJson("/api/communaute/messages/{$id}", ['body' => 'Piraté'])->assertStatus(404);
        $this->assertSame('Bonjour', SalonMessage::find($id)->body);

        $this->travel(16)->minutes();
        $this->withToken($tA)->putJson("/api/communaute/messages/{$id}", ['body' => 'Trop tard'])->assertStatus(422);
        $this->travelBack();

        $this->withToken($tE)->postJson("/api/communaute/messages/{$id}/reactions", ['emoji' => '🙏'])->assertOk()
            ->assertJsonPath('reactions.0', ['emoji' => '🙏', 'nombre' => 1, 'moi' => true]);
        $this->withToken($tE)->postJson("/api/communaute/messages/{$id}/reactions", ['emoji' => '🙏'])->assertOk()->assertJsonPath('reactions', []);
        $this->withToken($tE)->postJson("/api/communaute/messages/{$id}/reactions", ['emoji' => '💩'])->assertStatus(422);

        $this->withToken($tE)->deleteJson("/api/communaute/messages/{$id}")->assertStatus(403);
        $this->withToken($tA)->deleteJson("/api/communaute/messages/{$id}")->assertOk();
        $fil = $this->withToken($tE)->getJson('/api/communaute/salons/'.self::SALON)->json('messages');
        $this->assertSame('supprime', $fil[0]['type']);
        $this->assertNull($fil[0]['body']);
        $this->assertFalse($fil[0]['supprime_par_equipe']);
    }

    public function test_signalement_traite_par_l_administration(): void
    {
        $auteur = $this->membre(['prenom' => 'Paul']);
        $temoin = $this->membre();
        $admin = $this->admin(['salons']);
        $tP = $this->token($auteur);
        $tT = $this->token($temoin);
        $tAd = $this->token($admin);
        $this->entrer($tP);
        $this->entrer($tT);

        $id = $this->ecrire($tP, 'Achetez mes produits miracles !')->json('message.id');
        $this->withToken($tP)->postJson("/api/communaute/messages/{$id}/signaler")->assertStatus(404); // pas son propre message
        $this->withToken($tT)->postJson("/api/communaute/messages/{$id}/signaler", ['motif' => 'Publicité'])->assertOk();

        $this->assertSame(1, collect($this->withToken($tAd)->getJson('/api/admin/a-traiter')->json('elements'))->firstWhere('cle', 'communaute')['nombre']);
        $sig = $this->withToken($tAd)->getJson('/api/admin/communaute')->assertOk()->json('signalements.0');
        $this->assertSame('nouveau', $sig['statut']);
        $this->assertSame(['Publicité'], $sig['motifs']);

        $this->withToken($tAd)->deleteJson("/api/admin/communaute/messages/{$id}")->assertOk();
        $this->assertSame('traite', SalonSignalement::first()->statut);
        $this->assertSame('supprime', SalonSignalement::first()->decision);
        $this->assertTrue(SalonModeration::where('action', 'message_supprime')->exists());
        $fil = $this->withToken($tT)->getJson('/api/communaute/salons/'.self::SALON)->json('messages');
        $this->assertTrue($fil[0]['supprime_par_equipe']);

        // Rubrique réservée.
        $this->withToken($this->token($this->admin(['projets'])))->getJson('/api/admin/communaute')->assertStatus(403);
        $this->withToken($tT)->getJson('/api/admin/communaute')->assertStatus(403);
    }

    public function test_un_referent_modere_son_salon_sans_pouvoir_sanctionner_l_equipe(): void
    {
        $admin = $this->admin();
        $referent = $this->membre(['prenom' => 'Rita']);
        $bavard = $this->membre(['prenom' => 'Bob']);
        $autreReferent = $this->membre();
        $tAd = $this->token($admin);
        $tR = $this->token($referent);
        $tB = $this->token($bavard);
        $salon = Salon::where('slug', self::SALON)->first();

        $this->withToken($tAd)->postJson("/api/admin/communaute/salons/{$salon->id}/referents", ['user_id' => $referent->id])->assertOk();
        $this->withToken($tAd)->postJson("/api/admin/communaute/salons/{$salon->id}/referents", ['user_id' => $autreReferent->id])->assertOk();
        $this->assertDatabaseHas('member_notifications', ['user_id' => $referent->id, 'title' => 'Vous êtes référent du salon « Échanges & Networking »']);

        // Un référent écrit sans accepter les règles et voit ses outils.
        $this->withToken($tR)->getJson('/api/communaute/salons/'.self::SALON)->assertJsonPath('moi.moderateur', true);
        $this->entrer($tB);
        $id = $this->ecrire($tB, 'Message déplacé')->json('message.id');

        $this->withToken($tR)->deleteJson("/api/communaute/messages/{$id}")->assertOk();
        $this->withToken($tR)->postJson('/api/communaute/salons/'.self::SALON."/membres/{$bavard->id}/silence", ['duree' => '24h'])->assertOk();
        $this->ecrire($tB, 'Je peux parler ?')->assertStatus(403);

        $this->withToken($tR)->postJson('/api/communaute/salons/'.self::SALON."/membres/{$admin->id}/silence")->assertStatus(403);
        $this->withToken($tR)->postJson('/api/communaute/salons/'.self::SALON."/membres/{$autreReferent->id}/exclure")->assertStatus(403);
        $this->withToken($tB)->postJson('/api/communaute/salons/'.self::SALON."/membres/{$referent->id}/silence")->assertStatus(403);

        // Exclusion : le salon disparaît pour Bob ; réintégration par l'administration.
        $this->withToken($tR)->postJson('/api/communaute/salons/'.self::SALON."/membres/{$bavard->id}/exclure")->assertOk();
        $this->withToken($tB)->getJson('/api/communaute/salons/'.self::SALON)->assertStatus(403);
        $this->assertNull(collect($this->withToken($tB)->getJson('/api/communaute')->json('salons'))->firstWhere('slug', self::SALON));
        $this->withToken($tAd)->postJson("/api/admin/communaute/salons/{$salon->id}/membres/{$bavard->id}/reintegrer")->assertOk();
        $this->withToken($tB)->getJson('/api/communaute/salons/'.self::SALON)->assertOk();
        $this->assertSame(4, SalonModeration::whereIn('action', ['message_supprime', 'silence', 'exclusion', 'reintegration'])->count());

        // Lecture seule et mode lent.
        $this->withToken($tR)->putJson('/api/communaute/salons/'.self::SALON.'/reglages', ['lecture_seule' => true])->assertOk();
        $this->ecrire($tB, 'Coucou')->assertStatus(403)->assertJsonPath('message', 'Ce salon est en lecture seule pour le moment.');
        $this->withToken($tR)->putJson('/api/communaute/salons/'.self::SALON.'/reglages', ['lecture_seule' => false, 'mode_lent_secondes' => 60])->assertOk();
        $this->travel(2)->minutes();
        $this->ecrire($tB, 'Premier')->assertOk();
        $this->ecrire($tB, 'Second')->assertStatus(429)->assertJsonPath('code', 'mode_lent');
        $this->ecrire($tR, 'Le référent n\'est pas limité')->assertOk();
        $this->withToken($tB)->putJson('/api/communaute/salons/'.self::SALON.'/reglages', ['lecture_seule' => true])->assertStatus(403);
    }

    public function test_mots_interdits_anti_spam_et_doublons(): void
    {
        $admin = $this->admin();
        $m = $this->membre();
        $t = $this->token($m);
        $this->entrer($t);

        $this->withToken($this->token($admin))->putJson('/api/admin/communaute/mots-interdits', ['texte' => "arnaque\nCrypto miracle"])->assertOk();
        $this->ecrire($t, 'Attention, ARNAQUE en vue')->assertStatus(422)->assertJsonPath('code', 'mot_interdit');
        $this->ecrire($t, 'Investissez dans la crypto miracle')->assertStatus(422);
        $this->ecrire($t, 'Les arnaqueurs existent')->assertOk(); // mot entier seulement

        $this->ecrire($t, 'Bonjour')->assertOk();
        $this->ecrire($t, 'Bonjour')->assertStatus(422)->assertJsonPath('message', 'Vous venez d\'envoyer ce même message.');
        for ($i = 0; $i < 8; $i++) {
            $this->ecrire($t, "Message {$i}");
        }
        $this->ecrire($t, 'Encore un')->assertStatus(429);
    }

    public function test_l_administration_cree_regle_et_archive_les_salons(): void
    {
        SubscriptionMode::set(true);
        $tAd = $this->token($this->admin(['salons']));
        $libre = $this->membre();
        $abonne = $this->abonne();

        $this->withToken($tAd)->postJson('/api/admin/communaute/salons', ['nom' => 'Échanges & Networking', 'type' => 'discussion', 'acces' => 'abonnes', 'emoji' => '⭐'])->assertOk();
        $nouveau = Salon::latest('id')->first();
        $this->assertSame('echanges-networking', $nouveau->slug);

        $this->withToken($this->token($libre))->getJson("/api/communaute/salons/{$nouveau->slug}")->assertStatus(402)->assertJsonPath('code', 'subscription_required');
        $this->withToken($this->token($abonne))->getJson("/api/communaute/salons/{$nouveau->slug}")->assertOk();
        $verrou = collect($this->withToken($this->token($libre))->getJson('/api/communaute')->json('salons'))->firstWhere('id', $nouveau->id);
        $this->assertSame('Ce salon est réservé aux membres à jour de leur abonnement annuel.', $verrou['verrou']);

        $this->withToken($tAd)->postJson("/api/admin/communaute/salons/{$nouveau->id}/archiver")->assertOk();
        $this->withToken($this->token($abonne))->getJson("/api/communaute/salons/{$nouveau->slug}")->assertStatus(404);

        $annonces = Salon::where('type', 'annonces')->first();
        $this->withToken($tAd)->postJson("/api/admin/communaute/salons/{$annonces->id}/archiver")->assertStatus(422);
        $this->withToken($tAd)->postJson('/api/admin/communaute/salons', ['nom' => '', 'type' => 'discussion', 'acces' => 'tous'])->assertStatus(422);
    }

    public function test_compte_suspendu_et_categorie_de_notifications(): void
    {
        $suspendu = $this->membre(['is_active' => false]);
        $this->withToken($this->token($suspendu))->getJson('/api/communaute')->assertStatus(403);

        $this->assertSame('communaute', \App\Support\Notifications::categorie('/espace-membre/communaute?salon=annonces', 'communaute'));
        $this->assertArrayHasKey('communaute', \App\Support\Notifications::reglages($this->membre())['push']);
        $this->assertTrue(\App\Support\Notifications::reglages($this->membre())['push']['communaute']);
        $this->assertSame(0, SalonMembre::count());
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\Formation;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Salon;
use App\Models\SalonMessage;
use App\Models\User;
use App\Support\SubscriptionMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Communauté, lot 2 : sondages, cartes de partage, épinglés, recherche, statistiques. */
class CommunauteLot2Test extends TestCase
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

    public function test_sondage_cree_par_un_abonne_vote_et_cloture(): void
    {
        SubscriptionMode::set(true);
        $abonne = $this->membre(['prenom' => 'Esther', 'subscription_expires_at' => now()->addYear()]);
        $libre = $this->membre(['prenom' => 'Awa']);
        $tE = $this->token($abonne);
        $tA = $this->token($libre);
        $this->entrer($tE);
        $this->entrer($tA);

        $sondage = ['type' => 'sondage', 'sondage' => ['question' => 'Quelle date pour la sortie ?', 'options' => ['Samedi 8', 'Samedi 15', 'samedi 15', ' '], 'duree' => '3j']];
        $this->poster($tA, $sondage)->assertStatus(402)->assertJsonPath('code', 'subscription_required');
        $this->poster($tE, ['type' => 'sondage', 'sondage' => ['question' => 'Date ?', 'options' => ['Seul choix']]])
            ->assertStatus(422)->assertJsonPath('message', 'Proposez au moins deux choix différents.');

        $m = $this->poster($tE, $sondage)->assertOk()->json('message');
        $this->assertSame('sondage', $m['type']);
        $this->assertSame(['Samedi 8', 'Samedi 15'], array_column($m['sondage']['options'], 'libelle')); // doublons et vides retirés
        $this->assertTrue($m['sondage']['peut_clore']);
        $id = $m['sondage']['id'];
        [$huit, $quinze] = array_column($m['sondage']['options'], 'id');

        // Tout membre peut voter ; un seul choix ; on peut changer d'avis.
        $this->withToken($tA)->postJson("/api/communaute/sondages/{$id}/votes", ['options' => [$huit, $quinze]])->assertStatus(422);
        $this->withToken($tA)->postJson("/api/communaute/sondages/{$id}/votes", ['options' => [$huit]])->assertOk();
        $r = $this->withToken($tE)->postJson("/api/communaute/sondages/{$id}/votes", ['options' => [$quinze]])->assertOk()->json('sondage');
        $this->assertSame(2, $r['votants']);
        $this->assertSame([50, 50], array_column($r['options'], 'pourcentage'));
        $this->assertSame(['Awa'], $r['options'][0]['votants']);
        $r = $this->withToken($tA)->postJson("/api/communaute/sondages/{$id}/votes", ['options' => [$quinze]])->assertOk()->json('sondage');
        $this->assertSame([0, 100], array_column($r['options'], 'pourcentage'));

        // Le fil renvoie les résultats ; seul l'auteur (ou un référent) clôt le sondage.
        $fil = $this->withToken($tA)->getJson('/api/communaute/salons/'.self::SALON)->json('messages');
        $this->assertTrue($fil[0]['sondage']['options'][1]['moi']);
        $this->assertFalse($fil[0]['sondage']['peut_clore']);
        $this->withToken($tA)->postJson("/api/communaute/sondages/{$id}/clore")->assertStatus(403);
        $this->withToken($tE)->postJson("/api/communaute/sondages/{$id}/clore")->assertOk();
        $this->withToken($tA)->postJson("/api/communaute/sondages/{$id}/votes", ['options' => [$huit]])->assertStatus(422)->assertJsonPath('message', 'Ce sondage est clos.');

        // Un sondage arrivé à échéance est clos lui aussi.
        $autre = $this->poster($tE, ['type' => 'sondage', 'sondage' => ['question' => 'Thème du prochain atelier ?', 'options' => ['Finance', 'Marketing'], 'duree' => '1j']])->json('message.sondage');
        $this->travel(2)->days();
        $this->withToken($tA)->postJson("/api/communaute/sondages/{$autre['id']}/votes", ['options' => [$autre['options'][0]['id']]])->assertStatus(422);
    }

    public function test_cartes_de_partage_des_elements_de_la_plateforme(): void
    {
        $t = $this->token($this->membre());
        $this->entrer($t);
        $ev = Event::create(['title' => 'Forum Agro-business', 'slug' => 'forum', 'category' => 'Forum', 'statut' => 'publie', 'starts_at' => now()->addDays(10), 'location' => 'Abidjan, Plateau']);
        Event::create(['title' => 'Brouillon', 'slug' => 'brouillon', 'category' => 'Forum', 'statut' => 'brouillon', 'starts_at' => now()->addDays(3)]);
        $formation = Formation::create(['title' => 'Prise de parole', 'category' => 'Communication', 'modules_count' => 4, 'is_published' => true]);
        $projet = Project::create(['user_id' => $this->membre()->id, 'title' => 'BâtiJeunes', 'accroche' => 'Chantiers-écoles pour les jeunes', 'description' => 'Projet', 'statut' => 'valide']);

        $liste = $this->withToken($t)->getJson('/api/communaute/cartes?type=evenement')->assertOk()->json('cartes');
        $this->assertSame(['Forum Agro-business'], array_column($liste, 'titre'));
        $this->assertSame('/espace-membre/evenements?evenement='.$ev->id, $liste[0]['lien']);
        $this->assertSame('S\'inscrire', $liste[0]['action']);
        $this->assertSame('Prise de parole', $this->withToken($t)->getJson('/api/communaute/cartes?type=formation&q=parole')->json('cartes.0.titre'));

        $m = $this->poster($t, ['type' => 'carte', 'carte_type' => 'evenement', 'carte_id' => $ev->id, 'body' => 'Qui vient ?'])->assertOk()->json('message');
        $this->assertSame('Forum Agro-business', $m['carte']['titre']);
        $this->assertStringContainsString('Abidjan, Plateau', $m['carte']['detail']);
        $this->poster($t, ['type' => 'carte', 'carte_type' => 'projet', 'carte_id' => $projet->id])->assertOk()->assertJsonPath('message.carte.lien', '/espace-membre/projets?projet='.$projet->id);
        $this->poster($t, ['type' => 'carte', 'carte_type' => 'formation', 'carte_id' => $formation->id])->assertOk();
        $this->poster($t, ['type' => 'carte', 'carte_type' => 'evenement', 'carte_id' => Event::where('slug', 'brouillon')->value('id')])->assertStatus(422);

        // La carte reste à jour : un événement annulé l'indique.
        $ev->update(['statut' => 'annule']);
        $fil = $this->withToken($t)->getJson('/api/communaute/salons/'.self::SALON)->json('messages');
        $this->assertSame('Annulé', $fil[0]['carte']['etat']);
        $this->assertSame('🔗 Événement · Qui vient ?', \App\Support\Communaute::extrait(SalonMessage::first()));
    }

    public function test_epingles_par_les_referents_et_recherche_dans_le_salon(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => null]);
        $membre = $this->membre(['prenom' => 'Paul']);
        $tAd = $this->token($admin);
        $tM = $this->token($membre);
        $this->entrer($tM);

        $ids = [];
        foreach (['Bienvenue dans le salon', 'Le programme du forum est en ligne', 'Pensez à la cotisation', 'Atelier pitch jeudi', 'Visite de chantier', 'Sortie détente'] as $texte) {
            $ids[] = $this->poster($tM, ['type' => 'texte', 'body' => $texte])->json('message.id');
            $this->travel(7)->seconds();
        }

        $this->withToken($tM)->postJson("/api/communaute/messages/{$ids[0]}/epingle")->assertStatus(403);
        foreach (array_slice($ids, 0, 5) as $id) {
            $this->withToken($tAd)->postJson("/api/communaute/messages/{$id}/epingle")->assertOk();
            $this->travel(1)->seconds();
        }
        $this->withToken($tAd)->postJson("/api/communaute/messages/{$ids[5]}/epingle")->assertStatus(422);

        $salon = $this->withToken($tM)->getJson('/api/communaute/salons/'.self::SALON)->json();
        $this->assertCount(5, $salon['epingles']);
        $this->assertSame('Visite de chantier', $salon['epingles'][0]['extrait']); // le plus récent en premier
        $this->withToken($tAd)->deleteJson("/api/communaute/messages/{$ids[0]}/epingle")->assertOk();
        $this->assertCount(4, $this->withToken($tM)->getJson('/api/communaute/salons/'.self::SALON)->json('epingles'));

        $res = $this->withToken($tM)->getJson('/api/communaute/salons/'.self::SALON.'/recherche?q=forum')->assertOk()->json('resultats');
        $this->assertSame([$ids[1]], array_column($res, 'id'));
        $this->assertSame([], $this->withToken($tM)->getJson('/api/communaute/salons/'.self::SALON.'/recherche?q=a')->json('resultats'));

        // Saut vers un message : les messages autour, jusqu'au plus récent.
        $autour = $this->withToken($tM)->getJson('/api/communaute/salons/'.self::SALON.'?autour='.$ids[1])->json('messages');
        $this->assertSame($ids, array_column($autour, 'id'));
    }

    public function test_statistiques_d_animation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'permissions' => ['salons']]);
        $awa = $this->membre(['prenom' => 'Awa']);
        $salon = Salon::where('slug', self::SALON)->first();
        $spi = Salon::where('slug', 'spiritualite-et-leadership-chretien')->first();

        foreach (range(1, 6) as $i) {
            SalonMessage::forceCreate(['salon_id' => $spi->id, 'user_id' => $awa->id, 'type' => 'texte', 'body' => "ancien {$i}", 'created_at' => now()->subDays(10)]);
        }
        SalonMessage::forceCreate(['salon_id' => $spi->id, 'user_id' => $awa->id, 'type' => 'texte', 'body' => 'récent', 'created_at' => now()->subDays(2)]);
        SalonMessage::create(['salon_id' => $salon->id, 'user_id' => $awa->id, 'type' => 'texte', 'body' => 'bonjour']);

        $r = $this->withToken($this->token($admin))->getJson('/api/admin/communaute/statistiques')->assertOk()->json();
        $this->assertCount(30, $r['par_jour']);
        $this->assertSame(1, end($r['par_jour'])['messages']);
        $this->assertSame(8, $r['totaux']['messages']);
        $stats = collect($r['salons'])->keyBy('id');
        $this->assertSame('Activité en forte baisse', $stats[$spi->id]['alerte']);
        $this->assertSame(-83, $stats[$spi->id]['tendance']);
        $this->assertSame('Aucun message pour l\'instant', collect($r['salons'])->firstWhere('nom', 'Entrepreneurs & Projets')['alerte']);
        $this->assertSame(8, $r['contributeurs'][0]['messages']);

        $this->withToken($this->token($awa))->getJson('/api/admin/communaute/statistiques')->assertStatus(403);
    }
}

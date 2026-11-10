<?php

namespace Tests\Feature;

use App\Livewire\Member\Dashboard;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Accueil du membre : « À faire maintenant » rassemble au plus 3 actions,
 * de la plus urgente à la moins urgente.
 */
class AccueilMembreTest extends TestCase
{
    private function aFaire(Collection $evenements, ?array $seance, int $nonLus, ?array $continuer, ?array $aCommencer, int $completion): array
    {
        $m = new \ReflectionMethod(Dashboard::class, 'aFaire');

        return $m->invoke(new Dashboard, $evenements, $seance, $nonLus, $continuer, $aCommencer, $completion, ['téléphone', 'photo']);
    }

    private function evenement(int $id, string $debut, bool $inscrit = false): array
    {
        return ['id' => $id, 'title' => "Événement {$id}", 'starts_at' => $debut, 'registered' => $inscrit, 'refus' => null,
            'en_ligne' => false, 'en_cours' => false, 'statut' => 'publie', 'passe' => false, 'location' => 'Abidjan'];
    }

    public function test_trois_actions_au_plus_par_ordre_d_urgence(): void
    {
        $evenements = collect([
            ['en_cours' => true] + $this->evenement(1, now()->subMinutes(5)->toIso8601String(), true),
            $this->evenement(2, now()->addDays(4)->toIso8601String()),
        ]);
        $continuer = ['titre' => 'Trésorerie', 'module' => 'Module 2 sur 4', 'pct' => 25, 'url' => '/espace-membre/formations/1'];

        $actions = $this->aFaire($evenements, null, 2, $continuer, null, 60);

        $this->assertCount(3, $actions);
        $this->assertSame(['participer', 'messages', 'formation'], array_column($actions, 'cle'));
        $this->assertSame('Lire mes messages', $actions[1]['bouton']);
    }

    public function test_inscription_en_un_clic_au_prochain_evenement_et_profil_avec_pourcentage(): void
    {
        $actions = $this->aFaire(collect([$this->evenement(7, now()->addDays(3)->toIso8601String())]), null, 0, null, null, 40);

        $this->assertSame(['profil', 'evenement'], array_column($actions, 'cle'));
        $this->assertStringContainsString('40 %', $actions[0]['titre']);
        $this->assertSame(7, $actions[1]['inscrire']);
    }

    public function test_rien_a_faire_quand_tout_est_fait(): void
    {
        $this->assertSame([], $this->aFaire(collect(), null, 0, null, null, 100));
    }
}

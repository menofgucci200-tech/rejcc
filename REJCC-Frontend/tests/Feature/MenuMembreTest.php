<?php

namespace Tests\Feature;

use App\Support\MenuMembre;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Menu de l'espace membre ramené à 8 entrées : groupes Formations, Réseau et
 * Opportunités, barre d'onglets commune, pastilles cumulées et verrous.
 */
class MenuMembreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            '*/nav-compteurs' => Http::response(['compteurs' => ['messages' => 3, 'mentorat' => 2, 'groupes' => 1, 'communaute' => 0]]),
            '*' => Http::response(['ok' => true]),
        ]);
    }

    /** Membre connecté (abonné ou non) sur la page d'une route donnée. */
    private function surLaPage(string $route, array $user = []): void
    {
        session([
            'api_token' => 'jeton-essai',
            'api_user' => $user + ['id' => 1, 'prenom' => 'Awa', 'nom' => 'Essai', 'role' => 'member', 'subscription_active' => true],
        ]);
        $r = app('router')->getRoutes()->getByName($route);
        request()->setRouteResolver(fn () => $r);
    }

    private function libellesMenu(string $html): array
    {
        preg_match_all('/data-test="menu-([a-z0-9-]+)"/', $html, $m);

        return array_values(array_filter($m[1], fn ($cle) => $cle !== 'membre' && ! str_starts_with($cle, 'utilisateur')));
    }

    public function test_le_menu_compte_huit_entrees_dans_l_ordre(): void
    {
        $this->surLaPage('espace-membre.dashboard');
        $html = Blade::render('<x-member-light.sidebar />');

        $this->assertSame(
            ['accueil', 'formations', 'communaute', 'reseau', 'messagerie', 'evenements', 'opportunites', 'documents'],
            $this->libellesMenu($html),
        );
        // Les pages regroupées ne sont plus des entrées du menu.
        $this->assertStringNotContainsString('data-test="menu-catalogue"', $html);
        $this->assertStringNotContainsString('data-test="menu-annuaire"', $html);
        // Carte, profil et abonnement restent dans le menu de la carte utilisateur.
        $this->assertStringContainsString('data-test="menu-utilisateur-ma-carte-membre"', $html);
        $this->assertStringContainsString('data-test="menu-utilisateur-mon-profil"', $html);
        $this->assertStringContainsString('data-test="menu-utilisateur-mon-abonnement"', $html);
    }

    public function test_l_entree_du_groupe_est_active_et_les_onglets_s_affichent(): void
    {
        $this->surLaPage('espace-membre.catalogue');

        $menu = Blade::render('<x-member-light.sidebar />');
        $this->assertMatchesRegularExpression('/data-test="menu-formations"\s+aria-current="page"/', $menu);

        $onglets = Blade::render('<x-member-light.onglets />');
        $this->assertStringContainsString('data-test="onglets-formations"', $onglets);
        foreach (['mes-formations', 'catalogue', 'parcours', 'certificats'] as $o) {
            $this->assertStringContainsString('data-test="onglet-'.$o.'"', $onglets);
        }
        $this->assertMatchesRegularExpression('/data-test="onglet-catalogue"[^>]*aria-current="page"/', $onglets);
        $this->assertStringNotContainsString('onglet-mes-sessions', $onglets);
        $this->assertSame('Formations', MenuMembre::rubrique('espace-membre.catalogue'));
    }

    public function test_les_pages_de_detail_activent_leur_groupe(): void
    {
        $this->surLaPage('espace-membre.groupes.membres');

        $this->assertSame('reseau', MenuMembre::groupeCourant()['cle']);
        $this->assertSame('Réseau', MenuMembre::rubrique('espace-membre.groupes.membres'));
    }

    public function test_les_pastilles_se_cumulent_sur_le_groupe_et_restent_sur_l_onglet(): void
    {
        $this->surLaPage('espace-membre.mentorat');

        $reseau = collect(MenuMembre::entrees())->firstWhere('cle', 'reseau');
        $this->assertSame(3, $reseau['badge']);

        $onglets = Blade::render('<x-member-light.onglets />');
        $this->assertSame(2, substr_count($onglets, 'data-test="pastille-onglet"'));
    }

    public function test_un_non_abonne_voit_les_cadenas_et_le_groupe_reseau_ouvre_les_groupes(): void
    {
        $this->surLaPage('espace-membre.directory', ['subscription_active' => false]);

        $reseau = collect(MenuMembre::entrees())->firstWhere('cle', 'reseau');
        $this->assertSame('espace-membre.groupes', $reseau['route']);
        $this->assertTrue(collect($reseau['onglets'])->firstWhere('label', 'Annuaire')['verrouille']);

        $onglets = Blade::render('<x-member-light.onglets />');
        $this->assertStringContainsString('Annuaire — réservé aux abonnés', $onglets);

        // Abonné : le groupe ouvre l'annuaire, sans cadenas.
        $this->surLaPage('espace-membre.directory');
        $reseau = collect(MenuMembre::entrees())->firstWhere('cle', 'reseau');
        $this->assertSame('espace-membre.directory', $reseau['route']);
        $this->assertFalse(collect($reseau['onglets'])->firstWhere('label', 'Annuaire')['verrouille']);
    }

    public function test_un_formateur_retrouve_mes_sessions_en_onglet_et_dans_son_menu(): void
    {
        $this->surLaPage('espace-membre.sessions-animees', ['est_formateur' => true]);

        $onglets = Blade::render('<x-member-light.onglets />');
        $this->assertMatchesRegularExpression('/data-test="onglet-mes-sessions"[^>]*aria-current="page"/', $onglets);

        $menu = Blade::render('<x-member-light.sidebar />');
        $this->assertStringContainsString('data-test="menu-utilisateur-mes-sessions-formateur"', $menu);
    }

    public function test_hors_groupe_aucune_barre_d_onglets(): void
    {
        $this->surLaPage('espace-membre.evenements');

        $this->assertNull(MenuMembre::groupeCourant());
        $this->assertSame('', trim(Blade::render('<x-member-light.onglets />')));
    }
}

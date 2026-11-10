<?php

namespace App\Livewire\Admin;

use App\Support\StatistiquesSite;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Statistiques du site public (rejcc.site) pour la Communication : visites,
 * visiteurs, pages les plus vues, provenances, téléphone ou ordinateur, et
 * clics des liens de suivi. Mesure anonyme et sans cookie.
 */
#[Layout('layouts.admin-light')]
class StatistiquesDuSite extends Component
{
    /** « 30j » (30 derniers jours) ou un mois AAAA-MM. */
    #[Url(as: 'periode')]
    public string $periode = '30j';

    public function render()
    {
        $mois = StatistiquesSite::moisDisponibles();
        if ($this->periode !== '30j' && ! isset($mois[$this->periode])) {
            $this->periode = '30j';
        }
        $stats = $this->periode === '30j'
            ? StatistiquesSite::derniersJours(30)
            : StatistiquesSite::mois((int) substr($this->periode, 0, 4), (int) substr($this->periode, 5, 2));
        $clics = StatistiquesSite::clicsLiens(Carbon::parse($stats['du']), Carbon::parse($stats['au']));

        return view('livewire.admin.statistiques-du-site', [
            'stats' => $stats,
            'moisDisponibles' => $mois,
            'clics' => ($clics['ok'] ?? false) ? $clics : null,
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use App\Support\StatistiquesSite;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Bilan mensuel du département Communication : régularité des publications
 * (qui publie quand c'est son tour), CathoImpact, chiffres du réseau, site
 * (visites et liens de suivi) et demandes des départements. Version
 * imprimable / PDF par la fonction d'impression du navigateur.
 */
#[Layout('layouts.admin-light')]
class BilanCommunication extends Component
{
    #[Url]
    public string $mois = '';

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->mois)) {
            $this->mois = now()->format('Y-m');
        }
    }

    public function render()
    {
        $bilan = Api::get('/admin/communication/bilan', ['mois' => $this->mois], Api::token());
        [$annee, $m] = array_map('intval', explode('-', $this->mois));
        $debut = Carbon::create($annee, $m, 1);

        // Site : fichiers de mesure partagés et clics des liens de suivi.
        $site = rescue(fn () => StatistiquesSite::mois($annee, $m), null, false);
        $clics = StatistiquesSite::clicsLiens($debut, $debut->copy()->endOfMonth());

        // Mois proposés : les 12 derniers et le mois en cours.
        $moisListe = [];
        for ($d = now()->startOfMonth(); $d->gte(now()->startOfMonth()->subMonths(12)); $d->subMonth()) {
            $moisListe[$d->format('Y-m')] = ucfirst($d->locale('fr')->isoFormat('MMMM YYYY'));
        }

        return view('livewire.admin.bilan-communication', [
            'ok' => $bilan['ok'] ?? false,
            'bilan' => $bilan,
            'site' => $site,
            'clics' => ($clics['ok'] ?? false) ? $clics : null,
            'moisListe' => $moisListe,
            'enCours' => $this->mois === now()->format('Y-m'),
        ]);
    }
}

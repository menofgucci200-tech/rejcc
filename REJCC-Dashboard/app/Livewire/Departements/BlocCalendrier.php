<?php

namespace App\Livewire\Departements;

use App\Support\AdminNav;
use App\Support\AdminSections;
use App\Support\Api;
use Livewire\Component;

/**
 * Bloc « Calendrier éditorial » de l'espace Communication : tour en cours,
 * publications d'aujourd'hui et de demain (« Marquer publié » en un clic),
 * retards et lien vers le calendrier. Source : /admin/communication/calendrier/aujourdhui.
 */
class BlocCalendrier extends Component
{
    public function marquerPublie(int $id, ?string $lien = null): void
    {
        abort_unless(AdminSections::allowed(session('api_user'), 'communication'), 403);
        $lien = trim((string) $lien);
        $r = Api::post("/admin/communication/publications/{$id}/publie", $lien !== '' ? ['lien' => $lien] : [], Api::token());
        $this->dispatch('rj-toast', type: ($r['ok'] ?? false) ? 'success' : 'error', message: ($r['ok'] ?? false)
            ? 'Bravo, publication marquée « publié ».'
            : (collect($r['errors'] ?? [])->flatten()->first() ?? ($r['message'] ?? 'Action impossible.')));
        AdminNav::oublier();
        // Le cockpit de l'espace (chiffres du mois, pastilles) se met à jour.
        $this->dispatch('publication-publiee');
    }

    public function render()
    {
        $r = AdminSections::allowed(session('api_user'), 'communication')
            ? Api::get('/admin/communication/calendrier/aujourdhui', [], Api::token())
            : [];

        return view('livewire.departements.bloc-calendrier', ['jour' => ($r['ok'] ?? false) ? $r : null]);
    }
}

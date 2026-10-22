<?php

namespace App\Livewire\Departements;

use App\Support\AdminNav;
use App\Support\AdminSections;
use App\Support\Api;
use App\Support\Espace;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Espace d'un département du Bureau : sa mission, son équipe et l'accès
 * direct aux rubriques qu'il gère. Un compte de département n'ouvre que le
 * sien ; l'administrateur général les ouvre tous.
 */
#[Layout('layouts.admin-light')]
class Show extends Component
{
    #[Locked]
    public string $slug;

    public function mount(string $slug): void
    {
        $user = session('api_user');
        abort_unless(Espace::adminGeneral($user) || Espace::departement($user) === $slug, 403, 'Cet espace appartient à un autre département.');
        $this->slug = $slug;
    }

    public function render()
    {
        $departement = collect(Api::get('/admin/departements', [], Api::token())['departements'] ?? [])->firstWhere('slug', $this->slug);
        abort_if($departement === null, 404, 'Département introuvable.');

        // Rubriques du menu que ce département gère, dans l'ordre du menu.
        $rubriques = collect(AdminNav::GROUPES)->flatMap(fn ($g) => $g['items'])
            ->filter(fn ($i) => in_array(AdminSections::ROUTES[$i['route']] ?? null, $departement['sections'], true))
            ->filter(fn ($i) => AdminSections::allowedRoute(session('api_user'), $i['route']))
            ->values();

        return view('livewire.departements.show', [
            'departement' => $departement,
            'rubriques' => $rubriques,
            'pastilles' => AdminNav::pastilles(),
            'adminGeneral' => Espace::adminGeneral(session('api_user')),
        ]);
    }
}

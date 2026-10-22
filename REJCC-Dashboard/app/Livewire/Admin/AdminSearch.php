<?php

namespace App\Livewire\Admin;

use App\Support\AdminNav;
use App\Support\Api;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Recherche de la barre du haut de l'administration : comptes, adhésions,
 * formations, événements, projets (selon les droits) et pages de l'admin.
 */
class AdminSearch extends Component
{
    public string $q = '';

    public function render()
    {
        $q = trim($this->q);
        $groupes = [];

        if (mb_strlen($q) >= 2) {
            foreach (Api::get('/admin/recherche', ['q' => $q], Api::token())['groupes'] ?? [] as $titre => $items) {
                $groupes[$titre] = array_map(fn ($i) => [
                    'titre' => $i['titre'],
                    'detail' => $i['detail'],
                    'icon' => $i['icon'],
                    'url' => route($i['route'], $i['params'] ?? []),
                ], $items);
            }

            // Pages de l'administration retrouvables par leur nom.
            $needle = Str::lower(Str::ascii($q));
            $pages = [];
            foreach (AdminNav::groupes() as $g) {
                foreach ($g['items'] as $i) {
                    if (str_contains(Str::lower(Str::ascii($i['label'])), $needle)) {
                        $pages[] = ['titre' => $i['label'], 'detail' => $g['label'], 'icon' => 'arrow-right', 'url' => route($i['route'])];
                    }
                }
            }
            if ($pages) {
                $groupes['Pages de l\'administration'] = array_slice($pages, 0, 4);
            }
        }

        return view('livewire.admin.admin-search', ['groupes' => $groupes, 'actif' => mb_strlen($q) >= 2]);
    }
}

<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Audit extends Component
{
    public string $filtre = 'toutes';

    public function setFiltre(string $filtre): void
    {
        $this->filtre = $filtre;
    }

    protected function data(): array
    {
        return [
            ['admin' => 'Aya Bomisso', 'action' => "a validé la pièce d'identité de Yves Kacou", 'date' => "aujourd'hui, 10:24", 'categorie' => 'membres'],
            ['admin' => 'Aya Bomisso', 'action' => "a suspendu le compte de Serge N'Guessan", 'date' => "aujourd'hui, 09:52", 'categorie' => 'membres'],
            ['admin' => 'Christ Yao', 'action' => 'a publié l\'article "Retour sur le forum entrepreneuriat 2026"', 'date' => 'hier, 17:10', 'categorie' => 'contenu'],
            ['admin' => 'Aya Bomisso', 'action' => 'a validé le projet "AgriConnect CI" pour l\'incubateur', 'date' => 'hier, 14:05', 'categorie' => 'contenu'],
            ['admin' => 'Christ Yao', 'action' => 'a approuvé l\'offre "Développeur mobile (Flutter)"', 'date' => 'hier, 11:30', 'categorie' => 'contenu'],
            ['admin' => 'Aya Bomisso', 'action' => 'a créé le compte membre de Kadidia Ouattara', 'date' => 'il y a 2 jours', 'categorie' => 'membres'],
            ['admin' => 'Aya Bomisso', 'action' => 'a promu Marc Kouassi au rôle de mentor référent', 'date' => 'il y a 3 jours', 'categorie' => 'membres'],
            ['admin' => 'Christ Yao', 'action' => "a mis à jour le contenu de la page d'accueil", 'date' => 'il y a 4 jours', 'categorie' => 'contenu'],
            ['admin' => 'Aya Bomisso', 'action' => 'a rejeté la candidature de partenariat de Kaya Digital', 'date' => 'il y a 5 jours', 'categorie' => 'contenu'],
            ['admin' => 'Aya Bomisso', 'action' => 'a supprimé un message signalé sur la communauté', 'date' => 'il y a 1 semaine', 'categorie' => 'contenu'],
        ];
    }

    public function render()
    {
        $entrees = Collection::make($this->data())->map(function ($e) {
            $e['initiales'] = collect(explode(' ', $e['admin']))->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
            $e['tint'] = $e['categorie'] === 'membres' ? '#E8EDF8' : '#F9E9E9';
            $e['iconColor'] = $e['categorie'] === 'membres' ? '#031D59' : '#AC0100';

            return $e;
        })->when($this->filtre !== 'toutes', fn ($c) => $c->where('categorie', $this->filtre))->values();

        return view('livewire.admin.audit', ['entrees' => $entrees]);
    }
}

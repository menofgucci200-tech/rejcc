<?php

namespace App\Livewire\Admin;

use App\Models\Contact;
use App\Models\Document;
use App\Models\Member;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'membres' => User::where('role', 'member')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'adhesions' => Member::count(),
            'contacts' => Contact::count(),
            'documents' => Document::count(),
            'non_traites' => Contact::where('traite', false)->count(),
        ];

        $cards = [
            ['icon' => 'users', 'label' => 'Membres actifs', 'value' => $stats['membres'], 'route' => 'admin.members', 'color' => 'bg-brand'],
            ['icon' => 'file-text', 'label' => "Demandes d'adhésion", 'value' => $stats['adhesions'], 'route' => 'admin.adhesions', 'color' => 'bg-azure'],
            ['icon' => 'message-circle', 'label' => 'Messages contact', 'value' => $stats['contacts'], 'route' => 'admin.contacts', 'color' => 'bg-accent'],
            ['icon' => 'alert-circle', 'label' => 'Contacts non traités', 'value' => $stats['non_traites'], 'route' => 'admin.contacts', 'color' => 'bg-orange-500'],
            ['icon' => 'folder-open', 'label' => 'Documents', 'value' => $stats['documents'], 'route' => 'admin.documents', 'color' => 'bg-emerald-600'],
            ['icon' => 'shield-check', 'label' => 'Administrateurs', 'value' => $stats['admins'], 'route' => 'admin.members', 'color' => 'bg-purple-600'],
        ];

        return view('livewire.admin.dashboard', ['cards' => $cards]);
    }
}

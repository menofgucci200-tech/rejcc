<?php

namespace App\Livewire\Admin;

use App\Support\Api;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin-light')]
class Notifications extends Component
{
    public string $type = 'info';

    public string $title = '';

    public string $body = '';

    public string $link = '';

    /** Destinataire : '' = tous les membres, sinon id du membre. */
    public string $cible = '';

    /** @var array<int, array{id:int, label:string}> */
    public array $membres = [];

    public ?int $sentTo = null;

    public function mount(): void
    {
        // Liste des membres pour le ciblage. Vide (envoi à tous uniquement)
        // si l'admin n'a pas la permission « membres ».
        $this->membres = collect(Api::get('/admin/members', [], Api::token())['members'] ?? [])
            ->map(fn (array $m) => [
                'id' => $m['id'],
                'label' => trim(($m['prenom'] ?? '').' '.($m['nom'] ?? '')).' — '.($m['email'] ?? ''),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function send(): void
    {
        $this->validate([
            'title' => 'required|string|max:150',
            'body' => 'nullable|string|max:500',
            'link' => 'nullable|string|max:300',
            'type' => 'in:info,message,alert',
        ]);

        $result = Api::post('/admin/notifications/broadcast', [
            'title' => $this->title,
            'body' => $this->body ?: null,
            'link' => $this->link ?: null,
            'type' => $this->type,
            'user_id' => $this->cible !== '' ? (int) $this->cible : null,
        ], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->addError('title', $result['message'] ?? 'L\'envoi a échoué.');

            return;
        }

        $this->sentTo = $result['sent_to'] ?? 0;
        $this->reset(['title', 'body', 'link', 'type', 'cible']);
        $this->type = 'info';
    }

    public function render()
    {
        $historique = Collection::make(Api::get('/admin/notifications/history', [], Api::token())['historique'] ?? [])
            ->map(function ($h) {
                $h['created_at'] = \Carbon\Carbon::parse($h['created_at']);

                return (object) $h;
            });

        return view('livewire.admin.notifications', ['historique' => $historique]);
    }
}

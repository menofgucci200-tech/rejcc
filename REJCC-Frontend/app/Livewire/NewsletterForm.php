<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Component;

class NewsletterForm extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function submit(): void
    {
        $this->validate(['email' => 'required|email|max:150']);

        $result = Api::post('/newsletter', ['email' => $this->email]);

        if (! ($result['ok'] ?? false)) {
            $this->addError('email', $result['message'] ?? 'Une erreur est survenue, réessayez.');

            return;
        }

        $this->email = '';
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.newsletter-form');
    }
}

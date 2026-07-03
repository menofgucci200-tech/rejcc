<?php

namespace App\Livewire;

use App\Models\Contact;
use Livewire\Component;

class ContactForm extends Component
{
    public string $nom = '';

    public string $email = '';

    public string $sujet = '';

    public string $message = '';

    public bool $sent = false;

    protected function rules(): array
    {
        return [
            'nom' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:150',
            'sujet' => 'required|string|min:2|max:150',
            'message' => 'required|string|min:10|max:1500',
        ];
    }

    public function submit(): void
    {
        $data = $this->validate();

        Contact::create($data);

        $this->reset(['nom', 'email', 'sujet', 'message']);
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}

<?php

namespace App\Livewire;

use App\Support\Api;
use App\Support\Content\PartnersContent;
use Livewire\Component;

class PartenariatForm extends Component
{
    public string $organisation = '';

    public string $contact = '';

    public string $email = '';

    public string $telephone = '';

    public string $type = '';

    public string $message = '';

    public bool $sent = false;

    protected function rules(): array
    {
        return [
            'organisation' => 'required|string|min:2|max:150',
            'contact' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:150',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'type' => 'required|string|min:2|max:80',
            'message' => 'required|string|min:10|max:1500',
        ];
    }

    public function submit(): void
    {
        $data = $this->validate();

        $result = Api::post('/partenariat', $data);

        if (! ($result['ok'] ?? false)) {
            $this->addError('message', $result['message'] ?? 'Une erreur est survenue, réessayez.');

            return;
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.partenariat-form', [
            'partnershipTypes' => PartnersContent::partnershipTypes(),
        ]);
    }
}

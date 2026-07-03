<?php

namespace App\Livewire;

use App\Models\Member;
use App\Models\Payment;
use App\Support\Content\MembershipContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class AdhesionForm extends Component
{
    public int $step = 0;

    public string $status = 'idle';

    public string $reference = '';

    public string $profil = '';

    public string $prenom = '';

    public string $nom = '';

    public string $email = '';

    public string $telephone = '';

    public string $genre = '';

    public string $ville = '';

    public string $secteur = '';

    public string $organisation = '';

    public string $message = '';

    public string $paiement = '';

    public bool $consent = false;

    protected function stepOneRules(): array
    {
        return [
            'profil' => 'required|in:etudiant,porteur,entrepreneur',
            'prenom' => 'required|string|min:2|max:80',
            'nom' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:150',
            'telephone' => ['required', 'regex:/^[0-9]{10}$/'],
            'genre' => 'required|in:Homme,Femme',
            'ville' => 'required|string|min:2|max:80',
            'secteur' => 'required|string|max:100',
            'organisation' => 'nullable|string|max:120',
            'message' => 'nullable|string|max:800',
        ];
    }

    protected function stepTwoRules(): array
    {
        return [
            'paiement' => 'required|in:wave,orange,djamo',
            'consent' => 'accepted',
        ];
    }

    public function selectGenre(string $genre): void
    {
        $this->genre = $genre;
    }

    public function selectPaiement(string $paiement): void
    {
        $this->paiement = $paiement;
    }

    public function next(): void
    {
        $this->validate($this->stepOneRules());
        $this->step = 1;
    }

    public function back(): void
    {
        $this->step = 0;
    }

    public function submit(): void
    {
        $this->validate($this->stepTwoRules());

        $reference = 'REJCC-'.strtoupper(Str::random(5)).'-'.strtoupper(Str::random(3));

        $member = Member::create([
            'reference' => $reference,
            'profil' => $this->profil,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'genre' => $this->genre,
            'ville' => $this->ville,
            'secteur' => $this->secteur,
            'organisation' => $this->organisation ?: null,
            'message' => $this->message ?: null,
            'paiement' => $this->paiement,
            'statut' => 'pending',
        ]);

        Payment::create([
            'member_id' => $member->id,
            'reference' => $reference,
            'provider' => $this->paiement,
            'amount' => 10000,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        $this->reference = $reference;
        $this->status = 'success';
    }

    public function render()
    {
        $user = Auth::user();
        $dashboardHref = '/inscription';
        $dashboardLabel = 'Créer mon compte membre';

        if ($user) {
            if ($user->role === 'admin') {
                $dashboardHref = '/admin';
                $dashboardLabel = 'Accéder au back-office';
            } else {
                $dashboardHref = '/espace-membre';
                $dashboardLabel = 'Accéder à mon espace membre';
            }
        }

        return view('livewire.adhesion-form', [
            'fee' => MembershipContent::formatPrice(MembershipContent::ADHESION_FEE),
            'currency' => MembershipContent::CURRENCY,
            'period' => MembershipContent::ADHESION_PERIOD,
            'profiles' => MembershipContent::profiles(),
            'paymentMethods' => MembershipContent::paymentMethods(),
            'sectors' => \App\Models\Sector::orderBy('ordre')->get(),
            'dashboardHref' => $dashboardHref,
            'dashboardLabel' => $dashboardLabel,
        ]);
    }
}

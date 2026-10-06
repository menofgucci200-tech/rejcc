<?php

namespace App\Livewire;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Vérification publique d'un certificat ou d'une attestation REJCC : par le
 * QR code (signé), par le code de vérification, ou en déposant le PDF reçu
 * (son empreinte est comparée à celle du fichier délivré ; le fichier n'est
 * ni transmis ni conservé). Seul le registre fait foi.
 */
#[Layout('layouts.site', ['noindex' => true])]
#[Title('Vérifier un certificat')]
class VerifierCertificat extends Component
{
    use WithFileUploads;

    public string $code = '';

    public string $saisie = '';

    public ?array $resultat = null;

    public ?array $resultatFichier = null;

    public $fichier = null;

    public ?string $erreur = null;

    public function mount(?string $code = null): void
    {
        if ($code) {
            $this->code = strtoupper($code);
            $this->saisie = $this->code;
            $this->resultat = $this->interroger($code, (string) request()->query('s', ''));
        }
    }

    private function interroger(string $code, string $signature = ''): array
    {
        $r = Api::visiteur('get', '/certificats/verifier/'.rawurlencode($code), array_filter(['s' => $signature]));
        if (($r['_statut'] ?? 0) === 429) {
            return ['resultat' => 'limite'];
        }

        return $r + ['resultat' => 'inconnu'];
    }

    public function verifier()
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->saisie));
        if (strlen($code) !== 9) {
            $this->erreur = 'Le code de vérification compte 9 caractères (ex. : K7Q-M2X-9PA). Il figure en haut à gauche du certificat.';

            return null;
        }
        $this->erreur = null;

        return $this->redirectRoute('verifier', ['code' => implode('-', str_split($code, 3))], navigate: true);
    }

    public function updatedFichier(): void
    {
        $this->erreur = null;
        $this->validate(['fichier' => 'required|file|mimes:pdf|max:15360'], [
            'fichier.mimes' => 'Déposez le certificat au format PDF.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 15 Mo.',
        ]);
        $empreinte = hash_file('sha256', $this->fichier->getRealPath());
        $this->fichier->delete();
        $this->fichier = null;

        $r = Api::visiteur('post', '/certificats/verifier-fichier', array_filter(['empreinte' => $empreinte, 'code' => $this->code ?: null]));
        $this->resultatFichier = ($r['_statut'] ?? 0) === 429 ? ['resultat' => 'limite'] : $r + ['resultat' => 'inconnu'];
    }

    public function render()
    {
        return view('livewire.verifier-certificat');
    }
}

<?php

namespace App\Livewire\Member;

use App\Support\Api;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Détail d'une formation à contenu réel : sommaire des modules (titre,
 * description, vidéo/document), consultation puis validation dans l'ordre.
 */
#[Layout('layouts.member-light')]
class FormationDetail extends Component
{
    public int $formationId;

    public ?int $moduleOuvert = null;

    public ?string $message = null;

    /** Message d'échec (quiz non réussi, ordre…) affiché dans le module ouvert. */
    public ?string $erreur = null;

    /** Réponses aux quiz : [moduleId => [indexQuestion => indexChoix]]. */
    public array $reponses = [];

    // Examen final de certification
    public bool $examenOuvert = false;

    public array $examenQuestions = [];

    public array $reponsesExamen = [];

    public ?array $resultatExamen = null;

    public function ouvrirExamen(): void
    {
        $result = Api::get("/formations/{$this->formationId}/examen", [], Api::token());

        if (! ($result['ok'] ?? false)) {
            $this->resultatExamen = ['reussi' => false, 'message' => $result['message'] ?? 'Examen indisponible.'];

            return;
        }

        $this->examenQuestions = $result['questions'] ?? [];
        $this->reponsesExamen = [];
        $this->resultatExamen = null;
        $this->examenOuvert = true;
    }

    public function passerExamen(): void
    {
        $reponses = [];
        foreach (array_keys($this->examenQuestions) as $i) {
            $reponses[$i] = isset($this->reponsesExamen[$i]) ? (int) $this->reponsesExamen[$i] : -1;
        }

        $result = Api::post("/formations/{$this->formationId}/examen", ['reponses' => $reponses], Api::token());

        $this->resultatExamen = [
            'reussi' => (bool) ($result['reussi'] ?? false),
            'score' => $result['score'] ?? null,
            'message' => $result['message'] ?? null,
        ];

        if ($this->resultatExamen['reussi']) {
            $this->examenOuvert = false;
            $this->message = "Examen réussi ({$result['score']} %) : formation terminée, votre certificat est délivré !";
        }
    }

    public function mount(int $formationId): void
    {
        $this->formationId = $formationId;
    }

    public function toggleModule(int $moduleId): void
    {
        $this->erreur = null;
        $this->moduleOuvert = $this->moduleOuvert === $moduleId ? null : $moduleId;
    }

    public function validerModule(int $moduleId): void
    {
        $this->erreur = null;
        $reponses = array_map('intval', (array) ($this->reponses[$moduleId] ?? []));

        $result = Api::post("/formations/{$this->formationId}/modules/{$moduleId}/complete", ['reponses' => $reponses], Api::token());

        if (! ($result['ok'] ?? false)) {
            // Le module reste ouvert pour relire le contenu et retenter le quiz.
            $this->erreur = $result['message'] ?? 'Une erreur est survenue.';
            $this->message = null;

            return;
        }

        $score = $result['quiz_score'] ?? null;
        $this->message = ($result['completed'] ?? false)
            ? 'Formation terminée, bravo !'
            : ($score !== null ? "Quiz réussi ({$score} %) : module validé !" : 'Module validé !');
        $this->moduleOuvert = null;
    }

    public function render()
    {
        $result = Api::get("/formations/{$this->formationId}/modules", [], Api::token());

        return view('livewire.member.formation-detail', [
            'ok' => $result['ok'] ?? false,
            'reserveAbonnes' => ($result['code'] ?? null) === 'subscription_required',
            'erreurAcces' => $result['message'] ?? null,
            'formation' => $result['formation'] ?? null,
            'modules' => $result['modules'] ?? [],
            'progress' => $result['progress'] ?? 0,
            'completed' => $result['completed'] ?? false,
            'telechargementAutorise' => (bool) ($result['telechargement_autorise'] ?? false),
            'examen' => $result['examen'] ?? null,
        ]);
    }
}

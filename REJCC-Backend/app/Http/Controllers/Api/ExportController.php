<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Formation;
use App\Models\MembershipApplication;
use App\Models\NewsletterSubscriber;
use App\Models\Opportunity;
use App\Models\User;

/**
 * Export des jeux de données de la base sous forme de tableau
 * (colonnes + lignes) que le frontend transforme en CSV téléchargeable.
 */
class ExportController extends Controller
{
    public function data(string $dataset)
    {
        $export = match ($dataset) {
            'members' => $this->members(),
            'candidatures' => $this->candidatures(),
            'contacts' => $this->contacts(),
            'newsletter' => $this->newsletter(),
            'formations' => $this->formations(),
            'evenements' => $this->evenements(),
            'opportunites' => $this->opportunites(),
            default => null,
        };

        if (! $export) {
            return response()->json(['ok' => false, 'message' => 'Jeu de données inconnu.'], 404);
        }

        return response()->json([
            'ok' => true,
            'filename' => 'rejcc-'.$dataset.'-'.now()->format('Y-m-d'),
            'columns' => $export['columns'],
            'rows' => $export['rows'],
        ]);
    }

    private function members(): array
    {
        $roles = ['admin' => 'Administrateur', 'mentor' => 'Mentor', 'member' => 'Membre'];

        return [
            'columns' => ['N° membre', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Rôle', 'Ville', 'Secteur', 'Statut', 'Inscrit le'],
            'rows' => User::orderBy('created_at')->get()->map(fn (User $u) => [
                $u->memberNumber(), $u->prenom, $u->nom, $u->email, $u->telephone,
                $roles[$u->role] ?? $u->role, $u->ville, $u->secteur,
                $u->is_active ? 'Actif' : 'Suspendu', $u->created_at?->format('d/m/Y'),
            ])->all(),
        ];
    }

    private function candidatures(): array
    {
        return [
            'columns' => ['Prénom', 'Nom', 'Sexe', 'Âge', 'WhatsApp', 'Email', 'Diocèse', 'Paroisse', 'Ville', 'Niveau études', 'Domaines formation', 'Statut', 'Soumise le'],
            'rows' => MembershipApplication::orderBy('created_at')->get()->map(fn ($a) => [
                $a->prenom, $a->nom, $a->sexe, $a->tranche_age, $a->whatsapp, $a->email,
                $a->diocese, $a->paroisse, $a->ville, $a->niveau_etudes, $a->domaines_formation,
                ucfirst($a->statut), $a->created_at?->format('d/m/Y'),
            ])->all(),
        ];
    }

    private function contacts(): array
    {
        return [
            'columns' => ['Nom', 'Email', 'Sujet', 'Message', 'Traité', 'Reçu le'],
            'rows' => Contact::orderBy('created_at')->get()->map(fn ($c) => [
                $c->nom, $c->email, $c->sujet, $c->message, $c->traite ? 'Oui' : 'Non', $c->created_at?->format('d/m/Y H:i'),
            ])->all(),
        ];
    }

    private function newsletter(): array
    {
        return [
            'columns' => ['Email', 'Inscrit le'],
            'rows' => NewsletterSubscriber::orderBy('created_at')->get()->map(fn ($n) => [
                $n->email, $n->created_at?->format('d/m/Y'),
            ])->all(),
        ];
    }

    private function formations(): array
    {
        return [
            'columns' => ['Titre', 'Catégorie', 'Durée', 'Niveau', 'Modules', 'Gratuite', 'Certifiante', 'Publiée', 'Inscrits'],
            'rows' => Formation::withCount('enrollments')->orderBy('title')->get()->map(fn (Formation $f) => [
                $f->title, $f->category, $f->duration, $f->level, $f->modules_count,
                $f->is_free ? 'Oui' : 'Non', $f->is_certifying ? 'Oui' : 'Non',
                $f->is_published ? 'Oui' : 'Non', $f->enrollments_count,
            ])->all(),
        ];
    }

    private function evenements(): array
    {
        return [
            'columns' => ['Titre', 'Catégorie', 'Date', 'Lieu', 'Inscrits'],
            'rows' => Event::withCount('registrations')->orderByDesc('starts_at')->get()->map(fn (Event $e) => [
                $e->title, $e->category, $e->starts_at?->format('d/m/Y H:i'), $e->location, $e->registrations_count,
            ])->all(),
        ];
    }

    private function opportunites(): array
    {
        return [
            'columns' => ['Titre', 'Type', 'Description', 'Contact', 'Date limite', 'Publiée le'],
            'rows' => Opportunity::orderByDesc('created_at')->get()->map(fn ($o) => [
                $o->title, $o->type, $o->description, $o->contact, $o->deadline?->format('d/m/Y'), $o->created_at?->format('d/m/Y'),
            ])->all(),
        ];
    }
}

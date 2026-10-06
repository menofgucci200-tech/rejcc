<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Formation;
use App\Models\MembershipApplication;
use App\Models\EventRegistration;
use App\Models\NewsletterSubscriber;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Export des jeux de données de la base sous forme de tableau
 * (colonnes + lignes) que le frontend transforme en CSV téléchargeable.
 */
class ExportController extends Controller
{
    public function data(Request $request, string $dataset)
    {
        $export = match ($dataset) {
            'members' => $this->members(),
            'candidatures' => $this->candidatures(),
            'contacts' => $this->contacts(),
            'newsletter' => $this->newsletter(),
            'formations' => $this->formations(),
            'evenements' => $this->evenements(),
            'opportunites' => $this->opportunites(),
            'participants' => $this->participants($request->query('event')),
            'groupes' => $this->groupes($request->query('group')),
            'projets' => $this->projets(),
            'documents' => $this->documents(),
            'certificats' => $this->certificats(),
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
            'columns' => ['Prénom', 'Nom', 'Sexe', 'Âge', 'Téléphone', 'Email', 'Diocèse', 'Paroisse', 'Ville', 'Niveau études', 'Domaines formation', 'Statut', 'Soumise le'],
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
        $statuts = ['brouillon' => 'Brouillon', 'publie' => 'Publié', 'annule' => 'Annulé'];

        return [
            'columns' => ['Titre', 'Catégorie', 'Statut', 'Date', 'Lieu', 'Capacité', 'Inscrits', 'dont invités', 'Présents'],
            'rows' => Event::withCount([
                'registrations',
                'registrations as invites_count' => fn ($q) => $q->whereNull('user_id'),
                'registrations as presents_count' => fn ($q) => $q->whereNotNull('present_at'),
            ])->orderByDesc('starts_at')->get()->map(fn (Event $e) => [
                $e->title, $e->category, $statuts[$e->statut] ?? $e->statut, $e->starts_at?->format('d/m/Y H:i'),
                $e->en_ligne ? 'En ligne' : $e->location, $e->capacity, $e->registrations_count, $e->invites_count, $e->presents_count,
            ])->all(),
        ];
    }

    /** Liste unique des inscrits (membres et invités), d'un événement ou de tous. */
    private function participants(?string $eventId): array
    {
        $query = EventRegistration::with(['event:id,title,champs', 'user:id,prenom,nom,email,telephone,ville'])->orderBy('event_id')->orderBy('created_at');

        // Filtre optionnel sur un événement précis (bouton « Exporter » d'un événement).
        $event = $eventId ? Event::find($eventId) : null;
        if ($event) {
            $query->where('event_id', $event->id);
        }

        // Colonnes des questions personnalisées (uniquement pour un export ciblé).
        $fields = $event?->champs ?? [];
        $columns = array_merge(
            ['Événement', 'Type', 'N° membre', 'Prénom', 'Nom', 'Téléphone', 'Email', 'Ville', 'Billet', 'Inscrit le', 'Présent'],
            array_map(fn ($f) => $f['label'], $fields),
        );

        return [
            'columns' => $columns,
            'rows' => $query->get()->map(function (EventRegistration $r) use ($fields) {
                $u = $r->user;
                $row = [
                    $r->event?->title,
                    $u ? 'Membre' : ($r->se_dit_membre ? 'Invité (se dit membre)' : 'Invité'),
                    $u?->memberNumber(), $u->prenom ?? $r->prenom, $u->nom ?? $r->nom,
                    $u->telephone ?? $r->telephone, $u->email ?? $r->email, $u?->ville,
                    $r->billet, $r->created_at?->format('d/m/Y H:i'), $r->present_at?->format('d/m/Y H:i') ?? 'Non',
                ];
                foreach ($fields as $f) {
                    $v = $r->reponses[$f['key']] ?? '';
                    if ($f['type'] === 'checkbox') {
                        $v = $v === true ? 'Oui' : ($v === false ? 'Non' : '');
                    }
                    $row[] = is_array($v) ? implode(', ', $v) : (string) $v;
                }

                return $row;
            })->all(),
        ];
    }

    /** Projets proposés par les membres, avec porteur, équipe et suivi. */
    private function certificats(): array
    {
        $certs = \App\Models\Certificate::with('user:id,email')->orderByDesc('delivre_le')->get();

        return [
            'columns' => ['Référence', 'Code de vérification', 'Type', 'Titulaire', 'Email', 'Intitulé', 'Formation / événement / parcours', 'Délivré le', 'Statut', 'Motif de révocation', 'Vérifications', 'Affiché sur la bio'],
            'rows' => $certs->map(fn (\App\Models\Certificate $c) => [
                $c->reference, $c->codeLisible(), \App\Models\Certificate::TYPES[$c->type] ?? $c->type, $c->nom, $c->user?->email ?? $c->email,
                $c->intitule, $c->titre, $c->delivre_le->format('d/m/Y'), $c->statut === 'valide' ? 'Valide' : 'Révoqué',
                $c->motif_revocation, (int) $c->verifications, $c->visible_bio ? 'Oui' : 'Non',
            ])->all(),
        ];
    }

    private function documents(): array
    {
        $statuts = ['publie' => 'Publié', 'en_attente' => 'En attente', 'refuse' => 'Refusé'];
        $docs = \App\Models\Document::with(['categorie:id,nom', 'groupe:id,name', 'auteur:id,prenom,nom,role'])->orderByDesc('created_at')->get();

        return [
            'columns' => ['Titre', 'Catégorie', 'Type', 'Taille', 'Accès', 'Groupe', 'Statut', 'Proposé par', 'Vues', 'Téléchargements', 'Publié le'],
            'rows' => $docs->map(fn (\App\Models\Document $d) => [
                $d->title, $d->categorie?->nom ?? $d->category, $d->typeLabel(), $d->tailleLisible(),
                \App\Models\Document::ACCES[$d->acces] ?? $d->acces, $d->groupe?->name, $statuts[$d->statut] ?? $d->statut,
                $d->auteur && $d->auteur->role !== 'admin' ? trim($d->auteur->prenom.' '.$d->auteur->nom) : '',
                $d->vues, $d->telechargements, $d->publie_at?->format('d/m/Y'),
            ])->all(),
        ];
    }

    private function projets(): array
    {
        $projets = \App\Models\Project::with(['porteur:id,prenom,nom,email,telephone', 'groupe:id,name'])
            ->withCount(['suivis', 'equipe as equipe_count' => fn ($q) => $q->where('statut', 'membre')])
            ->orderByDesc('created_at')->get();

        return [
            'columns' => ['Projet', 'Porteur', 'Email', 'Téléphone', 'Secteur', 'Ville', 'Statut', 'Stade', 'Besoins', 'Équipe (plateforme)', 'Suivis', 'Vues', 'Site public', 'À la une', 'Soumis le', 'Décision le'],
            'rows' => $projets->map(fn (\App\Models\Project $p) => [
                $p->title, $p->porteur ? trim($p->porteur->prenom.' '.$p->porteur->nom) : '', $p->porteur?->email, $p->porteur?->telephone,
                $p->groupe?->name, $p->ville, \App\Models\Project::STATUTS[$p->statut] ?? $p->statut, \App\Models\Project::STADES[$p->stade] ?? $p->stade,
                implode(', ', array_map(fn ($b) => \App\Models\Project::BESOINS[$b] ?? $b, $p->besoins ?? [])),
                1 + $p->equipe_count, $p->suivis_count, $p->vues, $p->public_ok ? 'Oui' : 'Non', $p->a_la_une ? 'Oui' : 'Non',
                $p->soumis_at?->format('d/m/Y'), $p->decide_at?->format('d/m/Y'),
            ])->all(),
        ];
    }

    private function opportunites(): array
    {
        $offres = Opportunity::with(['author:id,prenom,nom,email', 'groupe:id,name'])
            ->withCount(['candidatures as candidatures_count' => fn ($q) => $q->where('statut', '!=', 'retiree'),
                'candidatures as retenues_count' => fn ($q) => $q->where('statut', 'retenue')])
            ->orderByDesc('created_at')->get();

        return [
            'columns' => ['Offre', 'Type', 'Contrat', 'Entreprise', 'Secteur', 'Ville', 'Mode', 'Rémunération', 'Statut', 'Auteur', 'Email auteur', 'Vues', 'Candidatures', 'Retenues', 'Date limite', 'Publiée le', 'En ligne jusqu\'au'],
            'rows' => $offres->map(fn (Opportunity $o) => [
                $o->title, Opportunity::TYPES[$o->type] ?? $o->type, Opportunity::CONTRATS[$o->contrat] ?? '', $o->entreprise, $o->groupe?->name, $o->lieu,
                Opportunity::TELETRAVAIL[$o->teletravail] ?? '', $o->remuneration, $o->estExpiree() ? 'Expirée' : (Opportunity::STATUTS[$o->statut] ?? $o->statut),
                $o->author ? trim($o->author->prenom.' '.$o->author->nom) : '', $o->author?->email, $o->vues, $o->candidatures_count, $o->retenues_count,
                $o->deadline?->format('d/m/Y'), $o->publie_at?->format('d/m/Y'), $o->expire_le?->format('d/m/Y'),
            ])->all(),
        ];
    }

    /** Membres des groupes sectoriels (tous, ou d'un seul groupe) avec leur fiche professionnelle. */
    private function groupes($groupId): array
    {
        $query = \App\Models\Group::with(['users' => fn ($q) => $q->orderBy('prenom')->orderBy('nom')])->orderBy('ordre');
        if ($groupId) {
            $query->whereKey((int) $groupId);
        }

        $rows = [];
        foreach ($query->get() as $g) {
            foreach ($g->users as $u) {
                $avis = \App\Models\MemberReview::resume($u->id);
                $rows[] = [
                    $g->name, $u->memberNumber(), $u->prenom, $u->nom, $u->email, $u->telephone, $u->ville,
                    $u->pivot->specialite, implode(', ', json_decode((string) $u->pivot->services, true) ?: []),
                    $u->pivot->zone, $u->pivot->disponibilites,
                    $avis['moyenne'] !== null ? str_replace('.', ',', (string) $avis['moyenne']).'/5 ('.$avis['nombre'].')' : '',
                    $u->is_active ? 'Actif' : 'Suspendu', $u->pivot->created_at?->format('d/m/Y'),
                ];
            }
        }

        return [
            'columns' => ['Groupe', 'N° membre', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Ville', 'Spécialité', 'Services', "Zone d'intervention", 'Disponibilités', 'Note', 'Statut', 'Rejoint le'],
            'rows' => $rows,
        ];
    }
}

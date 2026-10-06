<?php

namespace App\Models;

use App\Mail\InfoEvenement;
use App\Support\Mailer;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'excerpt', 'body', 'location', 'en_ligne', 'lien_visio', 'category', 'statut', 'motif_annulation',
        'starts_at', 'ends_at', 'time_label', 'image', 'capacity', 'reserve_abonnes', 'inscriptions_ouvertes', 'date_limite',
        'inscription_publique', 'champs', 'ancien_slug', 'attestation',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'date_limite' => 'datetime',
        'body' => 'array',
        'en_ligne' => 'boolean',
        'reserve_abonnes' => 'boolean',
        'inscriptions_ouvertes' => 'boolean',
        'inscription_publique' => 'boolean',
        'attestation' => 'boolean',
        'champs' => 'array',
        'capacity' => 'integer',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /** Événements visibles par les membres (publiés ou annulés, pas les brouillons). */
    public function scopeVisibles($query)
    {
        return $query->whereIn('statut', ['publie', 'annule']);
    }

    /** Nombre de personnes inscrites (toutes inscriptions confondues). */
    public function nbInscrits(): int
    {
        return $this->registrations()->count();
    }

    public function placesRestantes(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->nbInscrits());
    }

    public function estPasse(): bool
    {
        return ($this->ends_at ?? $this->starts_at)->isPast();
    }

    /**
     * Raison pour laquelle ce membre ne peut pas s'inscrire (null s'il le peut).
     * Sans membre : règles de l'inscription publique (formulaire QR).
     */
    public function raisonRefus(?User $user): ?string
    {
        return match (true) {
            $this->statut === 'annule' => 'Cet événement est annulé.',
            $this->statut !== 'publie' => "Les inscriptions à cet événement ne sont pas encore ouvertes.",
            $this->starts_at->isPast() => 'Cet événement a déjà eu lieu.',
            ! $this->inscriptions_ouvertes => 'Les inscriptions sont fermées.',
            $this->date_limite !== null && $this->date_limite->isPast() => "La date limite d'inscription est dépassée.",
            $user === null && ! $this->inscription_publique => 'Cet événement est réservé aux membres du réseau.',
            $this->reserve_abonnes && ! $user?->hasActiveSubscription() => 'Cet événement est réservé aux membres à jour de leur abonnement annuel.',
            $this->placesRestantes() === 0 => 'Toutes les places ont été réservées : l\'événement est complet.',
            default => null,
        };
    }

    /**
     * Prévient tous les inscrits : notification sur la plateforme pour les
     * membres, e-mail pour les invités qui ont laissé une adresse.
     *
     * @return array{membres: int, invites: int}
     */
    public function prevenirInscrits(string $titre, string $texte): array
    {
        $n = ['membres' => 0, 'invites' => 0];
        foreach ($this->registrations()->with('user:id,email')->get() as $r) {
            if ($r->user_id) {
                MemberNotification::create([
                    'user_id' => $r->user_id,
                    'type' => 'info',
                    'title' => $titre,
                    'body' => $texte,
                    'link' => "/espace-membre/evenements?evenement={$this->id}",
                ]);
                $n['membres']++;
            } elseif ($r->email) {
                Mailer::send($r->email, new InfoEvenement($r, $this, $titre, $texte));
                $n['invites']++;
            }
        }

        return $n;
    }

    /** Événement par son slug, ou par le slug de l'ancien module « Inscriptions (QR) ». */
    public static function parSlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first() ?? static::where('ancien_slug', $slug)->first();
    }
}

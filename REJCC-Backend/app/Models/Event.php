<?php

namespace App\Models;

use App\Mail\InfoEvenement;
use App\Support\Mailer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'excerpt', 'body', 'location', 'en_ligne', 'lien_visio', 'category', 'formation_id', 'animateur', 'hote_meet', 'replay_url', 'support_url', 'cible_formation', 'cible_groupes', 'cible_profils', 'google_event_id', 'statut', 'motif_annulation',
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
        'cible_formation' => 'boolean',
        'cible_groupes' => 'array',
        'cible_profils' => 'array',
        'capacity' => 'integer',
    ];

    /** Catégorie des sessions live de formation (événements en ligne rattachés à une formation). */
    public const CATEGORIE_LIVE = 'Formation live';

    public function formation()
    {
        return $this->belongsTo(Formation::class);
    }

    /** Sessions live de formation. */
    public function scopeLives($query)
    {
        return $query->where('category', self::CATEGORIE_LIVE);
    }

    public function estLive(): bool
    {
        return $this->category === self::CATEGORIE_LIVE;
    }

    public function formateurs()
    {
        return $this->belongsToMany(Formateur::class, 'event_formateur')->withPivot('jeton')->withTimestamps();
    }

    /** Profils de membres que l'on peut viser (champ « profil » du compte). */
    public const PROFILS = ['etudiant' => 'Étudiants', 'porteur' => 'Porteurs de projet', 'entrepreneur' => 'Entrepreneurs'];

    /** La session vise-t-elle un public restreint ? */
    public function estCiblee(): bool
    {
        return $this->cible_formation || ! empty($this->cible_groupes) || ! empty($this->cible_profils);
    }

    /**
     * Membres visés (actifs). Les critères se cumulent : inscrits à la
     * formation liée, ET membres d'un des groupes sectoriels choisis, ET d'un
     * des profils choisis. Sans critère : tous les membres.
     */
    public function publicVise()
    {
        $q = User::query()->where('is_active', true)->whereIn('role', ['member', 'mentor']);
        if ($this->cible_formation && $this->formation_id) {
            $q->whereIn('id', FormationEnrollment::where('formation_id', $this->formation_id)->select('user_id'));
        }
        if (! empty($this->cible_groupes)) {
            $q->whereIn('id', DB::table('group_user')->whereIn('group_id', $this->cible_groupes)->select('user_id'));
        }
        if (! empty($this->cible_profils)) {
            $q->whereIn('profil', $this->cible_profils);
        }

        return $q;
    }

    /** Ce membre fait-il partie du public visé ? (les administrateurs voient tout) */
    public function viseLeMembre(User $user): bool
    {
        if (! $this->estCiblee() || $user->role === 'admin') {
            return true;
        }

        return $this->publicVise()->whereKey($user->id)->exists();
    }

    /**
     * Garde, dans une liste d'événements, ceux que ce membre peut voir : les
     * événements ouverts à tous et les sessions qui le visent (les
     * administrateurs voient tout).
     */
    public static function filtrerPourLeMembre($events, User $user)
    {
        if ($user->role === 'admin') {
            return $events;
        }
        $formations = FormationEnrollment::where('user_id', $user->id)->pluck('formation_id')->map(fn ($f) => (int) $f)->all();
        $groupes = DB::table('group_user')->where('user_id', $user->id)->pluck('group_id')->map(fn ($g) => (int) $g)->all();

        return $events->filter(fn (self $e) => (! $e->cible_formation || in_array((int) $e->formation_id, $formations, true))
            && (empty($e->cible_groupes) || array_intersect(array_map('intval', $e->cible_groupes), $groupes) !== [])
            && (empty($e->cible_profils) || in_array($user->profil, $e->cible_profils, true)))->values();
    }

    public function quand(): string
    {
        return $this->starts_at->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm');
    }

    /** Notifie tous les membres actifs d'un nouvel événement (une seule fois). */
    public function annoncer(): int
    {
        $body = $this->quand().($this->en_ligne ? ' — en ligne' : ($this->location ? " — {$this->location}" : ''))
            .($this->reserve_abonnes ? '. Réservé aux membres à jour de leur abonnement.' : '. Inscrivez-vous depuis la fiche de l\'événement.');
        $titre = $this->estLive() ? "Nouvelle session live : {$this->title}" : "Nouvel événement : {$this->title}";
        $n = 0;
        $this->publicVise()->select('id')
            ->chunkById(500, function ($users) use ($body, $titre, &$n) {
                $now = now();
                MemberNotification::insert($users->map(fn ($u) => [
                    'user_id' => $u->id, 'type' => 'info', 'title' => $titre,
                    'body' => ucfirst($body), 'link' => "/espace-membre/evenements?evenement={$this->id}",
                    'created_at' => $now, 'updated_at' => $now,
                ])->all());
                $n += $users->count();
            });
        $this->forceFill(['annonce_at' => now()])->save();

        return $n;
    }

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

    /** Le bouton « Rejoindre la visio » est actif : 15 minutes avant le début, jusqu'à la fin. */
    public function visioOuverte(): bool
    {
        return $this->en_ligne && $this->statut === 'publie' && $this->starts_at->copy()->subMinutes(15)->isPast() && ! $this->estPasse();
    }

    /** Session reportée : les rappels repartent pour la nouvelle date. */
    public function relancerRappels(): void
    {
        $this->registrations()->update(['rappel_at' => null, 'rappel_h1_at' => null]);
    }

    /**
     * Raison pour laquelle ce membre ne peut pas s'inscrire (null s'il le peut).
     * Sans membre : règles de l'inscription publique (formulaire QR).
     */
    public function raisonRefus(?User $user): ?string
    {
        return match (true) {
            $this->statut === 'annule' => 'Cet événement est annulé.',
            $this->statut !== 'publie' => 'Les inscriptions à cet événement ne sont pas encore ouvertes.',
            // Une session en ligne reste ouverte jusqu'à sa fin : on peut la rejoindre en cours.
            ($this->en_ligne ? $this->estPasse() : $this->starts_at->isPast()) => 'Cet événement a déjà eu lieu.',
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

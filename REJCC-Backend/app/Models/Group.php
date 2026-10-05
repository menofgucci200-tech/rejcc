<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'ordre', 'icone', 'couleur', 'whatsapp_url', 'referent_id', 'annonce', 'annonce_at'];

    protected function casts(): array
    {
        return ['annonce_at' => 'datetime'];
    }

    /** Colonnes de la fiche professionnelle du membre dans le groupe. */
    public const FICHE = ['specialite', 'services', 'zone', 'disponibilites', 'telephone_visible'];

    /**
     * Mots-clés (début de mot, en minuscules) qui rattachent un secteur
     * d'activité, un titre ou des compétences à un groupe : sert à suggérer
     * les groupes à rejoindre.
     */
    public const MOTS_CLES = [
        'agriculture-peche' => ['agri', 'agro', 'élevage', 'elevage', 'pêche', 'peche', 'ferme', 'plantation', 'aviculture', 'pisciculture', 'cacao', 'maraîch'],
        'informatique-technologie' => ['informati', 'numérique', 'numerique', 'digital', 'développeu', 'developpeu', 'logiciel', 'web', 'tech', 'télécom', 'telecom', 'data'],
        'communication-medias' => ['communication', 'média', 'media', 'journalis', 'marketing', 'publicité', 'graphis', 'photograph', 'audiovisuel'],
        'finance-investissement' => ['financ', 'banque', 'bancaire', 'assurance', 'investiss', 'comptab', 'audit', 'microfinance'],
        'administration-gestion' => ['administrat', 'gestion', 'ressources humaines', 'secrétari', 'secretari', 'juridique', 'droit', 'avocat', 'notaire'],
        'education-formation' => ['éducation', 'education', 'enseign', 'formation', 'professeur', 'école', 'ecole', 'pédagog', 'coach'],
        'sante-bien-etre' => ['santé', 'sante', 'médec', 'medec', 'infirm', 'pharmac', 'bien-être', 'bien-etre', 'sage-femme', 'nutrition', 'kiné'],
        'btp-construction' => ['btp', 'construction', 'bâtiment', 'batiment', 'plomb', 'électric', 'electric', 'maçon', 'macon', 'architect', 'génie civil', 'immobili', 'menuis', 'peint'],
        'industrie-production' => ['industri', 'production', 'usine', 'mécani', 'mecani', 'transformation', 'manufactur'],
        'commerce-distribution' => ['commerce', 'commerçant', 'commercant', 'vente', 'distribution', 'boutique', 'import', 'export', 'négoce', 'negoce'],
        'transport-logistique' => ['transport', 'logistique', 'chauffeur', 'livraison', 'transit', 'douane'],
        'hotellerie-tourisme' => ['hôtel', 'hotel', 'tourisme', 'restaura', 'traiteur', 'cuisin', 'voyage'],
        'artisanat-creation' => ['artisan', 'couture', 'coutur', 'styliste', 'mode', 'coiff', 'esthéti', 'bijou', 'création', 'creation', 'tailleur'],
        'evenementiel-loisirs' => ['événement', 'evenement', 'loisir', 'musique', 'animation', 'décoration', 'decoration', 'sport', 'culture'],
        'developpement-durable-environnement' => ['environnement', 'durable', 'écolog', 'ecolog', 'recyclage', 'énergie', 'energie', 'solaire', 'climat'],
        'action-sociale-solidarite' => ['social', 'sociale', 'solidarit', 'humanitaire', 'ong', 'association', 'caritati', 'diaconie'],
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(self::FICHE)->withTimestamps();
    }

    public function referent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referent_id');
    }

    /** Le groupe correspond-il au profil (secteur, titre, compétences) du membre ? */
    public function correspondA(User $user): bool
    {
        $mots = self::MOTS_CLES[$this->slug] ?? [];
        if ($mots === []) {
            return false;
        }
        $profil = mb_strtolower(implode(' ', array_filter([
            $user->secteur, $user->titre, $user->organisation,
            implode(' ', (array) ($user->competences ?? [])),
        ])));
        if ($profil === '') {
            return false;
        }
        foreach ($mots as $mot) {
            if (preg_match('/(^|[^\pL])'.preg_quote($mot, '/').'/u', $profil)) {
                return true;
            }
        }

        return false;
    }
}

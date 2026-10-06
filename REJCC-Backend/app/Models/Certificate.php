<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Certificat ou attestation inscrit au registre officiel du REJCC. */
class Certificate extends Model
{
    public const TYPES = [
        'formation' => 'Certificat de formation',
        'evenement' => "Attestation d'événement",
        'parcours' => 'Attestation de parcours',
    ];

    /** Alphabet sans caractères ambigus (0/O, 1/I/L). */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'user_id', 'type', 'source_id', 'reference', 'code', 'nom', 'email', 'intitule', 'titre', 'details',
        'signataires', 'lieu', 'delivre_le', 'statut', 'revoque_at', 'motif_revocation', 'remplace_par_id',
        'fichier', 'empreinte', 'signe_electroniquement', 'visible_bio', 'correction_demandee',
        'correction_demandee_at', 'verifications', 'derniere_verification_at',
    ];

    protected $casts = [
        'details' => 'array', 'signataires' => 'array', 'delivre_le' => 'date', 'revoque_at' => 'datetime',
        'signe_electroniquement' => 'boolean', 'visible_bio' => 'boolean', 'correction_demandee_at' => 'datetime',
        'derniere_verification_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function remplacant()
    {
        return $this->belongsTo(self::class, 'remplace_par_id');
    }

    public static function nouveauCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 9; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /** « k7q-m2x 9pa » → « K7QM2X9PA ». */
    public static function normaliserCode(?string $saisie): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $saisie));
    }

    /** Code lisible : K7Q-M2X-9PA. */
    public function codeLisible(): string
    {
        return implode('-', str_split($this->code, 3));
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    /** Données signées : toute modification du titulaire, du titre ou de la date invalide la signature. */
    public function donneesSignees(): string
    {
        return implode('|', ['REJCC', 'v1', $this->code, $this->reference, $this->nom, $this->titre, $this->delivre_le->toDateString()]);
    }

    public function urlVerification(bool $avecSignature = true): string
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/verifier/'.$this->codeLisible();

        return $avecSignature ? $url.'?s='.\App\Support\SignatureCertificat::signer($this) : $url;
    }

    /** Fiche publique (page de vérification) : jamais d'email ni d'information privée. */
    public function payloadPublic(): array
    {
        $d = $this->details ?? [];

        return [
            'reference' => $this->reference,
            'code' => $this->codeLisible(),
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? $this->type,
            'intitule' => $this->intitule,
            'nom' => $this->nom,
            'titre' => $this->titre,
            'details' => array_intersect_key($d, array_flip(['duree', 'modules', 'score', 'competences', 'date_evenement', 'lieu_evenement', 'formations'])),
            'lieu' => $this->lieu,
            'delivre_le' => $this->delivre_le->toDateString(),
            'statut' => $this->statut,
            'revoque_at' => $this->revoque_at?->toIso8601String(),
            'motif_revocation' => $this->statut === 'revoque' ? $this->motif_revocation : null,
            'remplace_par' => $this->remplacant?->codeLisible(),
            'a_fichier' => (bool) $this->fichier,
            'signe_electroniquement' => $this->signe_electroniquement,
        ];
    }

    public function payload(): array
    {
        return $this->payloadPublic() + [
            'id' => $this->id,
            'url_verification' => $this->urlVerification(false),
            'visible_bio' => $this->visible_bio,
            'correction_demandee' => $this->correction_demandee,
            'correction_demandee_at' => $this->correction_demandee_at?->toIso8601String(),
            'verifications' => (int) $this->verifications,
            'derniere_verification_at' => $this->derniere_verification_at?->toIso8601String(),
        ];
    }
}

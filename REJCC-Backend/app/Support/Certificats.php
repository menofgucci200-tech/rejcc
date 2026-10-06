<?php

namespace App\Support;

use App\Mail\CertificatDelivre;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\FormationEnrollment;
use App\Models\MemberNotification;
use App\Models\Path;
use App\Models\PathBadge;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Délivrance des certificats et attestations : inscription au registre
 * officiel (nom figé, code de vérification aléatoire), notification et email
 * au titulaire, production du PDF officiel. Chaque délivrance est idempotente :
 * une même réussite ne produit jamais deux certificats.
 */
class Certificats
{
    public const CLE_REGLAGES = 'certificats.reglages';

    public static function reglages(): array
    {
        $r = (array) (SiteSetting::where('key', self::CLE_REGLAGES)->value('value') ?? []);

        return [
            'lieu' => $r['lieu'] ?? 'Abidjan',
            'signataires' => $r['signataires'] ?? [
                ['nom' => '', 'fonction' => 'Pour le Bureau exécutif', 'signature' => null],
                ['nom' => '', 'fonction' => 'Responsable des formations', 'signature' => null],
            ],
            'cachet' => $r['cachet'] ?? null,
        ];
    }

    public static function enregistrerReglages(array $r): void
    {
        SiteSetting::updateOrCreate(['key' => self::CLE_REGLAGES], ['value' => $r]);
    }

    // ── Délivrance ───────────────────────────────────────────────────────

    /** Formation certifiante terminée (examen réussi) : certificat de réussite. */
    public static function pourFormation(FormationEnrollment $e): ?Certificate
    {
        $e->loadMissing('formation', 'user');
        $f = $e->formation;
        if (! $e->completed_at || ! $f || ! $f->is_certifying || ! $f->modules()->exists() || ! $e->user) {
            return null;
        }
        if ($deja = self::existant('formation', $e->id)) {
            return $deja;
        }

        return self::creer([
            'user_id' => $e->user_id,
            'type' => 'formation',
            'source_id' => $e->id,
            'reference' => $e->certificateReference(),
            'nom' => self::nomDe($e->user),
            'intitule' => 'Certificat de réussite',
            'titre' => $f->title,
            'details' => array_filter([
                'duree' => $f->duration,
                'modules' => (int) $f->modules()->count(),
                'score' => $e->examen_score !== null ? (int) $e->examen_score : null,
                'competences' => array_values(array_filter((array) ($f->competences ?? []))),
            ], fn ($v) => $v !== null && $v !== [] && $v !== ''),
            'delivre_le' => $e->completed_at->toDateString(),
        ]);
    }

    /** Présence pointée à un événement qui délivre une attestation. */
    public static function pourEvenement(EventRegistration $r): ?Certificate
    {
        $r->loadMissing('event', 'user');
        $ev = $r->event;
        if (! $r->present_at || ! $ev || ! $ev->attestation || $ev->statut === 'annule') {
            return null;
        }
        if ($deja = self::existant('evenement', $r->id)) {
            return $deja;
        }
        $nom = $r->user ? self::nomDe($r->user) : trim($r->prenom.' '.$r->nom);
        if ($nom === '') {
            return null;
        }

        return self::creer([
            'user_id' => $r->user_id,
            'email' => $r->user ? null : $r->email,
            'type' => 'evenement',
            'source_id' => $r->id,
            'reference' => 'REJCC-ATT-E-'.$ev->starts_at->format('Y').'-'.str_pad((string) $r->id, 4, '0', STR_PAD_LEFT),
            'nom' => $nom,
            'intitule' => 'Attestation de participation',
            'titre' => $ev->title,
            'details' => array_filter([
                'date_evenement' => $ev->starts_at->toDateString(),
                'lieu_evenement' => $ev->en_ligne ? 'En ligne' : $ev->location,
                'duree' => $ev->time_label,
                'categorie' => $ev->category,
            ]),
            'delivre_le' => ($ev->ends_at ?? $ev->starts_at)->min(now())->toDateString(),
        ]);
    }

    /** Toutes les formations d'un parcours terminées : attestation de parcours. */
    public static function pourParcours(PathBadge $b): ?Certificate
    {
        $b->loadMissing('user');
        $p = Path::with('formations:id,title')->find($b->path_id);
        if (! $p || ! $b->user) {
            return null;
        }
        if ($deja = self::existant('parcours', $b->id)) {
            return $deja;
        }
        $obtenu = $b->obtenu_at ? \Illuminate\Support\Carbon::parse($b->obtenu_at) : now();

        return self::creer([
            'user_id' => $b->user_id,
            'type' => 'parcours',
            'source_id' => $b->id,
            'reference' => 'REJCC-ATT-P-'.$obtenu->format('Y').'-'.str_pad((string) $b->id, 4, '0', STR_PAD_LEFT),
            'nom' => self::nomDe($b->user),
            'intitule' => 'Attestation de parcours',
            'titre' => $p->title,
            'details' => ['formations' => $p->formations->pluck('title')->values()->all()],
            'delivre_le' => $obtenu->toDateString(),
        ]);
    }

    private static function existant(string $type, int $sourceId): ?Certificate
    {
        // Le certificat en vigueur (une réémission remplace l'ancien, révoqué).
        return Certificate::where('type', $type)->where('source_id', $sourceId)
            ->orderByRaw("CASE statut WHEN 'valide' THEN 0 ELSE 1 END")->latest('id')->first();
    }

    private static function nomDe($user): string
    {
        return trim(($user->prenom ?? '').' '.($user->nom ?? '')) ?: (string) $user->name;
    }

    private static function creer(array $data): Certificate
    {
        $r = self::reglages();
        $c = Certificate::create($data + [
            'code' => Certificate::nouveauCode(),
            'lieu' => $r['lieu'],
            'signataires' => $r['signataires'],
            'statut' => 'valide',
        ]);
        $c->setAttribute('details', array_merge($c->details ?? [], ['cachet' => $r['cachet']]));
        $c->save();

        self::prevenir($c);
        // Le PDF officiel est produit juste après la réponse (ou à la première demande).
        dispatch(fn () => self::pdfSilencieux($c->fresh()))->afterResponse();

        return $c;
    }

    private static function prevenir(Certificate $c, bool $reemission = false): void
    {
        $quoi = match ($c->type) {
            'formation' => 'Votre certificat',
            'evenement' => 'Votre attestation de participation',
            default => 'Votre attestation de parcours',
        };
        if ($c->user_id) {
            MemberNotification::create([
                'user_id' => $c->user_id, 'type' => 'success',
                'title' => $reemission ? "{$quoi} a été mis à jour" : "{$quoi} est disponible",
                'body' => "« {$c->titre} » — référence {$c->reference}. Téléchargez-le et partagez son lien de vérification.",
                'link' => '/espace-membre/certificats?certificat='.$c->id,
            ]);
        }
        Mailer::send($c->user?->email ?? $c->email, new CertificatDelivre($c, $reemission));
    }

    // ── Vie du certificat ────────────────────────────────────────────────

    /** Révocation : le certificat reste au registre mais s'affiche « Révoqué ». */
    public static function revoquer(Certificate $c, string $motif, bool $prevenir = true): void
    {
        $c->update(['statut' => 'revoque', 'revoque_at' => now(), 'motif_revocation' => $motif]);
        if ($prevenir && $c->user_id) {
            MemberNotification::create([
                'user_id' => $c->user_id, 'type' => 'warning',
                'title' => "Certificat révoqué : {$c->titre}",
                'body' => "Motif : {$motif}",
                'link' => '/espace-membre/certificats',
            ]);
        }
    }

    /**
     * Correction (nom mal orthographié…) : un nouveau certificat est délivré
     * avec un nouveau code ; l'ancien est révoqué et renvoie vers le nouveau.
     */
    public static function reemettre(Certificate $ancien, array $changements, string $motif): Certificate
    {
        $n = Certificate::where('type', $ancien->type)->where('source_id', $ancien->source_id)->count(); // 1re correction → -R1
        $base = preg_replace('/-R\d+$/', '', $ancien->reference);
        $nouveau = $ancien->replicate(['code', 'reference', 'fichier', 'empreinte', 'signe_electroniquement', 'verifications',
            'derniere_verification_at', 'revoque_at', 'motif_revocation', 'remplace_par_id', 'correction_demandee', 'correction_demandee_at']);
        $nouveau->fill(array_intersect_key($changements, array_flip(['nom', 'titre'])) + [
            'code' => Certificate::nouveauCode(),
            'reference' => $base.'-R'.$n,
            'statut' => 'valide',
            'signataires' => self::reglages()['signataires'],
        ]);
        $nouveau->save();
        self::revoquer($ancien, 'Remplacé par le certificat '.$nouveau->reference.' — '.$motif, false);
        $ancien->update(['remplace_par_id' => $nouveau->id]);

        self::prevenir($nouveau, true);
        dispatch(fn () => self::pdfSilencieux($nouveau->fresh()))->afterResponse();

        return $nouveau;
    }

    // ── PDF officiel ─────────────────────────────────────────────────────

    /** Chemin du PDF officiel (produit une seule fois puis conservé tel quel). */
    public static function pdf(Certificate $c): string
    {
        if ($c->fichier && Storage::disk('local')->exists($c->fichier)) {
            return $c->fichier;
        }
        [$contenu, $signe] = CertificatPdf::generer($c);
        $chemin = 'certificats/'.$c->delivre_le->format('Y').'/'.$c->reference.'-'.$c->code.'.pdf';
        Storage::disk('local')->put($chemin, $contenu);
        $c->update(['fichier' => $chemin, 'empreinte' => hash('sha256', $contenu), 'signe_electroniquement' => $signe]);

        return $chemin;
    }

    private static function pdfSilencieux(?Certificate $c): void
    {
        if (! $c) {
            return;
        }
        try {
            self::pdf($c);
        } catch (Throwable $e) {
            Log::warning("PDF du certificat {$c->reference} non produit : ".$e->getMessage());
        }
    }

    /**
     * Rattrapage (tâche planifiée, idempotente) : formations certifiantes
     * terminées, badges de parcours et présences aux événements terminés qui
     * n'ont pas encore leur certificat.
     */
    public static function rattraper(): array
    {
        $n = ['formation' => 0, 'evenement' => 0, 'parcours' => 0];
        $faits = fn (string $t) => Certificate::where('type', $t)->pluck('source_id')->all();

        foreach (FormationEnrollment::certificats()->whereNotIn('id', $faits('formation'))->get() as $e) {
            $n['formation'] += self::pourFormation($e) ? 1 : 0;
        }
        foreach (PathBadge::whereNotIn('id', $faits('parcours'))->get() as $b) {
            $n['parcours'] += self::pourParcours($b) ? 1 : 0;
        }
        $evenements = Event::where('attestation', true)->where('statut', '!=', 'annule')
            ->where(fn ($q) => $q->where('ends_at', '<=', now())->orWhere(fn ($w) => $w->whereNull('ends_at')->where('starts_at', '<=', now()->subHours(3))))
            ->pluck('id');
        foreach (EventRegistration::whereIn('event_id', $evenements)->whereNotNull('present_at')->whereNotIn('id', $faits('evenement'))->get() as $r) {
            $n['evenement'] += self::pourEvenement($r) ? 1 : 0;
        }

        return $n;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberNotification;
use App\Models\User;
use App\Support\Certificats;
use App\Support\SignatureCertificat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Certificats et attestations : espace du membre, vérification publique
 * (le registre fait foi, jamais le document imprimé) et administration.
 */
class CertificateController extends Controller
{
    // ── Espace membre ───────────────────────────────────────────────────

    public function mine(Request $request)
    {
        $certs = Certificate::with('remplacant')->where('user_id', $request->user()->id)
            ->where(fn ($q) => $q->where('statut', 'valide')->orWhereNull('remplace_par_id')) // une version corrigée remplace l'ancienne
            ->orderByDesc('delivre_le')->orderByDesc('id')->get();

        return response()->json(['ok' => true, 'certificates' => $certs->map(fn ($c) => $c->payload())->values()]);
    }

    private function monCertificat(Request $request, int $id): ?Certificate
    {
        return Certificate::where('user_id', $request->user()->id)->find($id);
    }

    /** GET /my-certificates/{id}/pdf — le PDF officiel (produit une fois, conservé). */
    public function monPdf(Request $request, int $id)
    {
        $c = $this->monCertificat($request, $id);
        if (! $c || ! $c->estValide()) {
            return response()->json(['ok' => false, 'message' => 'Certificat introuvable.'], 404);
        }

        return $this->envoyerPdf($c);
    }

    public function bio(Request $request, int $id)
    {
        $c = $this->monCertificat($request, $id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Certificat introuvable.'], 404);
        }
        $c->update(['visible_bio' => $request->boolean('visible_bio')]);

        return response()->json(['ok' => true, 'certificate' => $c->payload()]);
    }

    /** POST /my-certificates/{id}/correction — signaler une erreur (nom mal orthographié…). */
    public function demanderCorrection(Request $request, int $id)
    {
        $c = $this->monCertificat($request, $id);
        if (! $c || ! $c->estValide()) {
            return response()->json(['ok' => false, 'message' => 'Certificat introuvable.'], 404);
        }
        $v = Validator::make($request->all(), ['message' => 'required|string|min:5|max:500'], [
            'message.required' => 'Indiquez ce qui doit être corrigé.',
            'message.min' => 'Précisez la correction souhaitée.',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $c->update(['correction_demandee' => trim($request->input('message')), 'correction_demandee_at' => now()]);

        return response()->json(['ok' => true, 'certificate' => $c->payload()]);
    }

    // ── Vérification publique ───────────────────────────────────────────

    /** GET /certificats/verifier/{code}?s=…&via=qr — fiche officielle du registre. */
    public function verifier(Request $request, string $code)
    {
        $c = Certificate::with('remplacant')->where('code', Certificate::normaliserCode($code))->first();
        $methode = $request->query('via') === 'qr' || $request->filled('s') ? 'qr' : 'code';
        if (! $c) {
            $this->journaliser($request, null, $methode, 'inconnu');

            return response()->json(['ok' => false, 'resultat' => 'inconnu', 'message' => "Aucun certificat ne correspond à ce code dans le registre du REJCC."], 404);
        }
        $this->journaliser($request, $c, $methode, $c->estValide() ? 'valide' : 'revoque');

        return response()->json([
            'ok' => true,
            'resultat' => $c->estValide() ? 'valide' : 'revoque',
            'certificat' => $c->payloadPublic(),
            // Signature du QR code : null si la page a été ouverte sans (saisie manuelle du code).
            'signature_qr' => $request->filled('s') ? SignatureCertificat::verifier($c, (string) $request->query('s')) : null,
            'cle_publique' => SignatureCertificat::clePublique(),
        ]);
    }

    /**
     * POST /certificats/verifier-fichier { empreinte, code? } — le PDF déposé
     * est-il exactement celui délivré par la plateforme ? (l'empreinte SHA-256
     * est calculée par le site, le fichier n'est jamais transmis ni conservé)
     */
    public function verifierFichier(Request $request)
    {
        $empreinte = strtolower((string) $request->input('empreinte'));
        if (! preg_match('/^[a-f0-9]{64}$/', $empreinte)) {
            return response()->json(['ok' => false, 'message' => 'Fichier illisible.'], 422);
        }
        $c = Certificate::with('remplacant')->where('empreinte', $empreinte)->first();
        if ($c) {
            $this->journaliser($request, $c, 'fichier', 'intact');

            return response()->json(['ok' => true, 'resultat' => $c->estValide() ? 'intact' : 'revoque', 'certificat' => $c->payloadPublic()]);
        }
        $attendu = $request->filled('code') ? Certificate::where('code', Certificate::normaliserCode($request->input('code')))->first() : null;
        $this->journaliser($request, $attendu, 'fichier', 'modifie');

        return response()->json([
            'ok' => false,
            'resultat' => $attendu ? 'modifie' : 'inconnu',
            'message' => $attendu
                ? "Ce fichier n'est pas celui délivré par le REJCC : il a été modifié."
                : "Ce fichier ne correspond à aucun certificat délivré par le REJCC.",
            'certificat' => $attendu?->payloadPublic(),
        ], 404);
    }

    /** GET /certificats/verifier/{code}/pdf — copie officielle, pour comparaison. */
    public function pdfPublic(string $code)
    {
        $c = Certificate::where('code', Certificate::normaliserCode($code))->first();
        if (! $c || ! $c->estValide()) {
            return response()->json(['ok' => false, 'message' => 'Certificat introuvable.'], 404);
        }

        return $this->envoyerPdf($c);
    }

    private function journaliser(Request $request, ?Certificate $c, string $methode, string $resultat): void
    {
        CertificateVerification::create([
            'certificate_id' => $c?->id, 'methode' => $methode, 'resultat' => $resultat,
            'ip_hash' => preg_match('/^[a-f0-9]{64}$/', (string) $request->header('X-Visiteur')) ? $request->header('X-Visiteur') : hash('sha256', $request->ip().'|'.config('app.key')),
            'agent' => Str::limit((string) $request->userAgent(), 150, ''),
        ]);
        if ($c) {
            $c->increment('verifications', 1, ['derniere_verification_at' => now()]);
        }
    }

    private function envoyerPdf(Certificate $c)
    {
        $chemin = Certificats::pdf($c);

        return response(Storage::disk('local')->get($chemin), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$c->reference.'.pdf"',
            'X-Empreinte' => $c->fresh()->empreinte,
            'Cache-Control' => 'private, no-store',
        ]);
    }

    // ── Administration ──────────────────────────────────────────────────

    public function adminIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Certificate::with('remplacant')
            ->when($request->query('type'), fn ($w, $t) => $w->where('type', $t))
            ->when($request->query('statut') === 'correction', fn ($w) => $w->whereNotNull('correction_demandee')->where('statut', 'valide'))
            ->when(in_array($request->query('statut'), ['valide', 'revoque'], true), fn ($w) => $w->where('statut', $request->query('statut')))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nom', 'like', "%{$q}%")->orWhere('titre', 'like', "%{$q}%")
                ->orWhere('reference', 'like', "%{$q}%")->orWhere('code', Certificate::normaliserCode($q))))
            ->orderByRaw('CASE WHEN correction_demandee IS NOT NULL AND statut = ? THEN 0 ELSE 1 END', ['valide'])
            ->orderByDesc('delivre_le')->orderByDesc('id');
        $page = $query->paginate(30);
        $emails = User::whereIn('id', collect($page->items())->pluck('user_id')->filter())->pluck('email', 'id');

        return response()->json([
            'ok' => true,
            'certificates' => collect($page->items())->map(fn (Certificate $c) => $c->payload() + [
                'email' => $c->user_id ? ($emails[$c->user_id] ?? null) : $c->email,
                'invite' => ! $c->user_id,
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'stats' => [
                'total' => Certificate::where('statut', 'valide')->count(),
                'formation' => Certificate::where('statut', 'valide')->where('type', 'formation')->count(),
                'evenement' => Certificate::where('statut', 'valide')->where('type', 'evenement')->count(),
                'parcours' => Certificate::where('statut', 'valide')->where('type', 'parcours')->count(),
                'revoques' => Certificate::where('statut', 'revoque')->count(),
                'corrections' => Certificate::whereNotNull('correction_demandee')->where('statut', 'valide')->count(),
                'verifications' => (int) Certificate::sum('verifications'),
                'verifications_30j' => CertificateVerification::where('created_at', '>=', now()->subDays(30))->count(),
                'tentatives_suspectes' => CertificateVerification::whereIn('resultat', ['inconnu', 'modifie'])->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function adminShow(int $id)
    {
        $c = Certificate::with('remplacant')->find($id);
        if (! $c) {
            return response()->json(['ok' => false, 'message' => 'Certificat introuvable.'], 404);
        }

        return response()->json([
            'ok' => true,
            'certificate' => $c->payload() + ['email' => $c->user?->email ?? $c->email, 'empreinte' => $c->empreinte, 'details_complets' => $c->details, 'signataires' => $c->signataires],
            'journal' => CertificateVerification::where('certificate_id', $c->id)->latest('created_at')->limit(20)->get()
                ->map(fn ($v) => ['methode' => $v->methode, 'resultat' => $v->resultat, 'le' => $v->created_at?->toIso8601String()]),
        ]);
    }

    public function adminPdf(int $id)
    {
        $c = Certificate::find($id);

        return $c ? $this->envoyerPdf($c) : response()->json(['ok' => false, 'message' => 'Certificat introuvable.'], 404);
    }

    public function revoquer(Request $request, int $id)
    {
        $c = Certificate::find($id);
        if (! $c || ! $c->estValide()) {
            return response()->json(['ok' => false, 'message' => 'Ce certificat n\'est pas valide.'], 422);
        }
        $motif = trim((string) $request->input('motif'));
        if (mb_strlen($motif) < 5) {
            return response()->json(['ok' => false, 'message' => 'Indiquez le motif de la révocation (il sera visible lors des vérifications).'], 422);
        }
        Certificats::revoquer($c, $motif);

        return response()->json(['ok' => true, 'certificate' => $c->fresh()->payload()]);
    }

    /** Correction : nouveau certificat (nouveau code), l'ancien est révoqué et renvoie vers lui. */
    public function reemettre(Request $request, int $id)
    {
        $c = Certificate::find($id);
        if (! $c || ! $c->estValide()) {
            return response()->json(['ok' => false, 'message' => 'Seul un certificat valide peut être corrigé.'], 422);
        }
        $v = Validator::make($request->all(), [
            'nom' => 'required|string|min:3|max:160',
            'titre' => 'nullable|string|min:3|max:255',
            'motif' => 'required|string|min:3|max:300',
        ], ['nom.required' => 'Indiquez le nom tel qu\'il doit figurer sur le certificat.', 'motif.required' => 'Indiquez le motif de la correction.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $nouveau = Certificats::reemettre($c, array_filter($v->safe()->only(['nom', 'titre'])), $v->validated()['motif']);

        return response()->json(['ok' => true, 'certificate' => $nouveau->payload()]);
    }

    /** La demande de correction n'est pas retenue : le membre en est informé. */
    public function refuserCorrection(Request $request, int $id)
    {
        $c = Certificate::find($id);
        if (! $c || ! $c->correction_demandee) {
            return response()->json(['ok' => false, 'message' => 'Aucune demande en cours.'], 404);
        }
        $reponse = trim((string) $request->input('reponse'));
        if ($c->user_id) {
            MemberNotification::create([
                'user_id' => $c->user_id, 'type' => 'info',
                'title' => "Votre demande de correction : {$c->titre}",
                'body' => $reponse ?: 'Après vérification, le certificat est conforme aux informations enregistrées.',
                'link' => '/espace-membre/certificats',
            ]);
        }
        $c->update(['correction_demandee' => null, 'correction_demandee_at' => null]);

        return response()->json(['ok' => true]);
    }

    public function reglages()
    {
        $r = Certificats::reglages();

        return response()->json(['ok' => true, 'reglages' => $r + [
            'signatures_apercu' => collect($r['signataires'])->map(fn ($s) => ! empty($s['signature']) && Storage::disk('local')->exists($s['signature'])
                ? 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($s['signature'])) : null)->all(),
            'cachet_apercu' => $r['cachet'] && Storage::disk('local')->exists($r['cachet'])
                ? 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($r['cachet'])) : null,
        ]]);
    }

    /**
     * PUT /admin/certificats/reglages — lieu de délivrance, signataires (nom,
     * fonction, signature scannée) et cachet. Les images arrivent en base64
     * (PNG/JPG, 1 Mo max) ; « supprimer » retire l'image existante.
     */
    public function enregistrerReglages(Request $request)
    {
        $v = Validator::make($request->all(), [
            'lieu' => 'required|string|min:2|max:80',
            'signataires' => 'required|array|size:2',
            'signataires.*.nom' => 'nullable|string|max:100',
            'signataires.*.fonction' => 'required|string|min:2|max:100',
            'signataires.*.signature_image' => 'nullable|string',
            'signataires.*.supprimer_signature' => 'nullable|boolean',
            'cachet_image' => 'nullable|string',
            'supprimer_cachet' => 'nullable|boolean',
        ], ['signataires.*.fonction.required' => 'Indiquez la fonction de chaque signataire.']);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $actuel = Certificats::reglages();
        $signataires = [];
        foreach ($request->input('signataires') as $i => $s) {
            $chemin = $actuel['signataires'][$i]['signature'] ?? null;
            if (! empty($s['supprimer_signature'])) {
                $chemin = null;
            }
            if (! empty($s['signature_image'])) {
                $chemin = $this->stockerImage($s['signature_image'], 'signature-'.($i + 1));
                if (! $chemin) {
                    return response()->json(['ok' => false, 'message' => 'Signature '.($i + 1).' : image PNG ou JPG de 1 Mo maximum.'], 422);
                }
            }
            $signataires[] = ['nom' => trim((string) ($s['nom'] ?? '')), 'fonction' => trim($s['fonction']), 'signature' => $chemin];
        }
        $cachet = $request->boolean('supprimer_cachet') ? null : $actuel['cachet'];
        if ($request->filled('cachet_image')) {
            $cachet = $this->stockerImage($request->input('cachet_image'), 'cachet');
            if (! $cachet) {
                return response()->json(['ok' => false, 'message' => 'Cachet : image PNG ou JPG de 1 Mo maximum.'], 422);
            }
        }
        Certificats::enregistrerReglages(['lieu' => trim($request->input('lieu')), 'signataires' => $signataires, 'cachet' => $cachet]);

        return $this->reglages();
    }

    private function stockerImage(string $base64, string $nom): ?string
    {
        $brut = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $base64), true);
        if ($brut === false || strlen($brut) > 1024 * 1024) {
            return null;
        }
        $info = @getimagesizefromstring($brut);
        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return null;
        }
        $chemin = 'certificats/reglages/'.$nom.'-'.Str::random(8).($info[2] === IMAGETYPE_PNG ? '.png' : '.jpg');
        Storage::disk('local')->put($chemin, $brut);

        return $chemin;
    }

    /** GET /admin/certificats/apercu — modèle rempli avec un exemple et les réglages actuels (rien n'est enregistré). */
    public function apercu()
    {
        $r = Certificats::reglages();
        $c = new Certificate([
            'type' => 'formation', 'reference' => 'REJCC-CERT-0000-EXEMPLE', 'code' => 'EXEMPLE00', 'nom' => 'Prénom Nom du membre',
            'intitule' => 'Certificat de réussite', 'titre' => 'Intitulé de la formation certifiante',
            'details' => ['duree' => '12 heures', 'modules' => 6, 'score' => 86, 'competences' => ['Compétence 1', 'Compétence 2', 'Compétence 3'], 'cachet' => $r['cachet']],
            'signataires' => $r['signataires'], 'lieu' => $r['lieu'], 'delivre_le' => now()->toDateString(),
        ]);
        [$pdf] = \App\Support\CertificatPdf::generer($c);

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="apercu-certificat.pdf"', 'Cache-Control' => 'no-store']);
    }

    /** POST /admin/events/{id}/attestations — délivrer maintenant aux présents. */
    public function attestationsEvenement(int $id)
    {
        $e = Event::find($id);
        if (! $e) {
            return response()->json(['ok' => false, 'message' => 'Événement introuvable.'], 404);
        }
        if (! $e->attestation) {
            return response()->json(['ok' => false, 'message' => "Activez d'abord « Délivrer une attestation de participation » pour cet événement."], 422);
        }
        if ($e->starts_at->isFuture()) {
            return response()->json(['ok' => false, 'message' => "L'événement n'a pas encore commencé."], 422);
        }
        $n = 0;
        foreach (EventRegistration::where('event_id', $e->id)->whereNotNull('present_at')->get() as $r) {
            $avant = Certificate::where('type', 'evenement')->where('source_id', $r->id)->exists();
            if (Certificats::pourEvenement($r) && ! $avant) {
                $n++;
            }
        }

        return response()->json(['ok' => true, 'delivrees' => $n, 'message' => $n ? "{$n} attestation(s) délivrée(s)." : 'Toutes les attestations des présents sont déjà délivrées.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunicationPublication;
use App\Models\CommunicationTour;
use App\Support\CalendrierEditorial as Cal;
use App\Support\CalendrierImport;
use App\Support\DemandesCommunication;
use App\Support\OutilsCommunication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Calendrier éditorial de la Communication (rubrique « communication ») :
 * publications prévues, tours de rôle, import du calendrier Excel, export
 * CSV et « Marquer publié » en un clic. Une demande d'un autre département
 * acceptée se planifie avec POST /publications et son demande_id.
 */
class CalendrierEditorialController extends Controller
{
    /** « AAAA-MM » → [année, mois] (mois courant si absent ou invalide). */
    private function mois(?string $mois): array
    {
        if ($mois && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mois, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [now()->year, now()->month];
    }

    /** Compte de test : il ne touche qu'à ce qu'il a créé (le vrai calendrier de la Com reste intact). */
    private function refusTesteur(Request $request, $element): ?JsonResponse
    {
        return OutilsCommunication::peutModifier($request->user(), $element->cree_par)
            ? null
            : response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
    }

    private function constantes(): array
    {
        return [
            'types' => collect(Cal::TYPES)->map(fn ($t) => $t['label']),
            'plan' => Cal::plan(),
            'canaux' => Cal::CANAUX,
            'formats' => Cal::FORMATS,
            'statuts' => Cal::STATUTS,
            'equipe' => Cal::equipe()->map(fn ($u) => ['id' => $u->id, 'nom' => Cal::nomCompte($u)])->values(),
        ];
    }

    /** GET /admin/communication/calendrier?mois=AAAA-MM&responsable=&canal=&statut= */
    public function index(Request $request)
    {
        [$annee, $mois] = $this->mois($request->query('mois'));
        $debut = Carbon::create($annee, $mois, 1);
        $fin = $debut->copy()->endOfMonth();
        $tours = Cal::toursEntre($debut->toDateString(), $fin->toDateString());

        $lignes = CommunicationPublication::with('responsableCompte:id,prenom,nom,email')
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->orderBy('date')->orderByRaw('heure is null')->orderBy('heure')->orderBy('id')->get()
            ->map(fn ($p) => Cal::ligne($p, $tours));

        // Filtres (sur le responsable effectif, tour compris).
        if (($r = $request->query('responsable')) !== null && $r !== '') {
            $lignes = $lignes->filter(fn ($l) => $r === 'aucun' ? $l['responsable_effectif']['nom'] === null : (string) $l['responsable_effectif']['id'] === (string) $r);
        }
        if (($c = $request->query('canal')) && isset(Cal::CANAUX[$c])) {
            $lignes = $lignes->filter(fn ($l) => in_array($c, $l['canaux'], true));
        }
        if ($s = $request->query('statut')) {
            $lignes = $lignes->filter(fn ($l) => $s === 'retard' ? $l['en_retard'] : $l['statut'] === $s);
        }

        $tourEnCours = Cal::tourDu(today());
        $retards = CommunicationPublication::with('responsableCompte:id,prenom,nom,email')->retards()
            ->orderBy('date')->limit(50)->get();
        $toursRetards = $retards->isEmpty() ? collect() : Cal::toursEntre($retards->first()->date->toDateString(), today()->toDateString());

        return response()->json(['ok' => true,
            'mois' => sprintf('%04d-%02d', $annee, $mois),
            'aujourdhui' => today()->toDateString(),
            'publications' => $lignes->values(),
            'tours' => $tours->map(fn ($t) => Cal::ligneTour($t))->values(),
            'tour_en_cours' => $tourEnCours ? Cal::ligneTour($tourEnCours) : null,
            'retards' => $retards->map(fn ($p) => Cal::ligne($p, $toursRetards))->values(),
        ] + $this->constantes());
    }

    /** GET /admin/communication/calendrier/aujourdhui : bloc de l'espace du département (et futur cockpit). */
    public function aujourdhui()
    {
        $demain = today()->addDay();
        $tours = Cal::toursEntre(today()->subDays(60)->toDateString(), $demain->toDateString());
        $prochaines = CommunicationPublication::with('responsableCompte:id,prenom,nom,email')
            ->whereBetween('date', [today()->toDateString(), $demain->toDateString()])->where('statut', '!=', 'annule')
            ->orderBy('date')->orderByRaw('heure is null')->orderBy('heure')->orderBy('id')->get()
            ->map(fn ($p) => Cal::ligne($p, $tours));
        $retards = CommunicationPublication::with('responsableCompte:id,prenom,nom,email')->retards()->orderBy('date')->get();
        $tour = Cal::tourDu(today());
        $debutMois = today()->startOfMonth()->toDateString();

        return response()->json(['ok' => true,
            'tour_en_cours' => $tour ? Cal::ligneTour($tour) : null,
            'aujourdhui' => $prochaines->where('date', today()->toDateString())->values(),
            'demain' => $prochaines->where('date', $demain->toDateString())->values(),
            'retards' => $retards->take(10)->map(fn ($p) => Cal::ligne($p, $tours))->values(),
            'chiffres' => [
                'retards' => $retards->count(),
                'prevues_mois' => CommunicationPublication::whereBetween('date', [$debutMois, today()->endOfMonth()->toDateString()])->where('statut', '!=', 'annule')->count(),
                'publiees_mois' => CommunicationPublication::whereBetween('date', [$debutMois, today()->endOfMonth()->toDateString()])->where('statut', 'publie')->count(),
            ],
        ]);
    }

    // ── Publications ────────────────────────────────────────────────────

    private function valider(Request $request, bool $partiel = false): array
    {
        $requis = $partiel ? 'sometimes|required' : 'required';

        return $request->validate([
            'date' => "{$requis}|date",
            'heure' => ['sometimes', 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'titre' => "{$requis}|string|min:2|max:200",
            'type' => [$partiel ? 'sometimes' : 'nullable', Rule::in(array_keys(Cal::TYPES))],
            'format' => 'sometimes|nullable|string|max:60',
            'canaux' => 'sometimes|nullable|array',
            'canaux.*' => [Rule::in(array_keys(Cal::CANAUX))],
            'responsable_id' => ['sometimes', 'nullable', 'integer', Rule::in(Cal::equipe()->pluck('id')->all())],
            'responsable' => 'sometimes|nullable|string|max:150',
            'notes' => 'sometimes|nullable|string|max:5000',
            'statut' => ['sometimes', Rule::in(array_keys(Cal::STATUTS))],
            'liens' => 'sometimes|nullable|array|max:10',
            'liens.*' => 'url:http,https|max:500',
            'demande_id' => 'sometimes|nullable|integer|min:1',
        ], [
            'heure.regex' => 'L\'heure doit être au format HH:MM.',
            'responsable_id.in' => 'Ce compte ne fait pas partie du département Communication.',
            'liens.*.url' => 'Le lien de la publication doit commencer par https://',
            'canaux.*.in' => 'Canal inconnu.',
        ]);
    }

    /** Un compte de l'équipe ou un nom libre, pas les deux ; statut publié → date de publication. */
    private function preparer(array $d, ?CommunicationPublication $p, Request $request): array
    {
        if (! empty($d['responsable_id'])) {
            $d['responsable'] = null;
        } elseif (array_key_exists('responsable', $d) && trim((string) $d['responsable']) !== '') {
            $d['responsable_id'] = null;
        }
        if (array_key_exists('liens', $d)) {
            $d['liens'] = array_values(array_unique(array_filter($d['liens'] ?? [])));
        }
        if (isset($d['statut'])) {
            if ($d['statut'] === 'publie' && ! $p?->publie_at) {
                $d['publie_at'] = now();
                $d['publie_par'] = $request->user()->id;
            } elseif ($d['statut'] !== 'publie') {
                $d['publie_at'] = $d['publie_par'] = null;
            }
        }
        // Nouvelle date : les rappels repartent pour la nouvelle échéance.
        if ($p && isset($d['date']) && Carbon::parse($d['date'])->toDateString() !== $p->date->toDateString()) {
            $d += ['rappel_veille_at' => null, 'rappel_jour_at' => null, 'retard_signale_at' => null];
        }

        return $d;
    }

    /** Sans responsable : celui du tour de rôle du jour. */
    private function responsableDuTour(array $d): array
    {
        if (empty($d['responsable_id']) && trim((string) ($d['responsable'] ?? '')) === '' && ($tour = Cal::tourDu($d['date']))) {
            $d['responsable_id'] = $tour->responsable_id;
            $d['responsable'] = $tour->responsable_id ? null : $tour->responsable;
        }

        return $d;
    }

    /** POST /admin/communication/publications (demande_id facultatif) */
    public function store(Request $request)
    {
        $d = $this->valider($request);
        $d['type'] = $d['type'] ?? 'autre';
        if (! array_key_exists('canaux', $d) || $d['canaux'] === null) {
            $d['canaux'] = Cal::canauxParDefaut($d['type']);
        }
        $d = $this->responsableDuTour($this->preparer($d + ['statut' => 'prevu'], null, $request));
        $p = CommunicationPublication::create($d + ['cree_par' => $request->user()->id]);

        return response()->json(['ok' => true, 'publication' => Cal::ligne($p->fresh('responsableCompte'))], 201);
    }

    /** GET /admin/communication/publications/{id} */
    public function show(int $id)
    {
        return response()->json(['ok' => true, 'publication' => Cal::ligne(CommunicationPublication::with('responsableCompte')->findOrFail($id))]);
    }

    /** PUT /admin/communication/publications/{id} */
    public function update(Request $request, int $id)
    {
        $p = CommunicationPublication::findOrFail($id);
        if ($refus = $this->refusTesteur($request, $p)) {
            return $refus;
        }
        $etaitPubliee = $p->statut === 'publie';
        $p->update($this->preparer($this->valider($request, partiel: true), $p, $request));
        // Passée à « publié » : la demande liée l'est aussi (e-mail au demandeur).
        if (! $etaitPubliee && $p->statut === 'publie') {
            DemandesCommunication::publicationPubliee($p, $request->user());
        }

        return response()->json(['ok' => true, 'publication' => Cal::ligne($p->fresh('responsableCompte'))]);
    }

    /** DELETE /admin/communication/publications/{id} */
    public function destroy(Request $request, int $id)
    {
        $p = CommunicationPublication::findOrFail($id);
        if ($refus = $this->refusTesteur($request, $p)) {
            return $refus;
        }
        $p->delete();

        return response()->json(['ok' => true]);
    }

    /** POST /admin/communication/publications/{id}/publie {lien?} : « Marquer publié » en un clic. */
    public function marquerPublie(Request $request, int $id)
    {
        $p = CommunicationPublication::findOrFail($id);
        if ($refus = $this->refusTesteur($request, $p)) {
            return $refus;
        }
        $d = $request->validate(['lien' => 'nullable|url:http,https|max:500'], ['lien.url' => 'Le lien de la publication doit commencer par https://']);
        $liens = $p->liens ?? [];
        if (! empty($d['lien']) && ! in_array($d['lien'], $liens, true)) {
            $liens[] = $d['lien'];
        }
        $etaitPubliee = $p->statut === 'publie';
        $p->update(['statut' => 'publie', 'liens' => $liens, 'publie_at' => $p->publie_at ?? now(), 'publie_par' => $p->publie_par ?? $request->user()->id]);
        if (! $etaitPubliee) {
            DemandesCommunication::publicationPubliee($p, $request->user());
        }

        return response()->json(['ok' => true, 'publication' => Cal::ligne($p->fresh('responsableCompte'))]);
    }

    // ── Import, export, duplication ─────────────────────────────────────

    /** POST /admin/communication/publications/import {fichier: base64, nom, apercu: bool} */
    public function importer(Request $request)
    {
        $d = $request->validate([
            'fichier' => 'required|string|max:6000000',
            'nom' => 'required|string|max:200',
            'apercu' => 'sometimes|boolean',
        ]);
        $nom = mb_strtolower($d['nom']);
        if (! preg_match('/\.(csv|txt|xlsx)$/', $nom)) {
            return response()->json(['ok' => false, 'message' => 'Format non pris en charge : envoyez un fichier .csv (Excel « CSV UTF-8 ») ou .xlsx.'], 422);
        }
        $contenu = base64_decode($d['fichier'], true);
        if ($contenu === false || $contenu === '') {
            return response()->json(['ok' => false, 'message' => 'Fichier vide ou illisible.'], 422);
        }
        try {
            $analyse = CalendrierImport::analyser(CalendrierImport::lignesBrutes($contenu, $nom), Cal::equipe());
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'message' => 'Fichier illisible. Dans Excel : Fichier → Enregistrer sous → « CSV UTF-8 (délimité par des virgules) ».'], 422);
        }
        if ($analyse['erreur']) {
            return response()->json(['ok' => false, 'message' => $analyse['erreur']], 422);
        }
        if (count($analyse['lignes']) > 500) {
            return response()->json(['ok' => false, 'message' => 'Fichier trop long : 500 lignes au plus par import.'], 422);
        }
        $valides = collect($analyse['lignes'])->filter(fn ($l) => $l['erreurs'] === []);
        $erreurs = collect($analyse['lignes'])->reject(fn ($l) => $l['erreurs'] === [])->values();

        if ($request->boolean('apercu')) {
            return response()->json(['ok' => true, 'apercu' => true, 'colonnes' => $analyse['colonnes'],
                'lignes' => $analyse['lignes'], 'valides' => $valides->count(), 'erreurs' => $erreurs->count()]);
        }

        $moi = $request->user()->id;
        DB::transaction(function () use ($valides, $moi) {
            foreach ($valides as $l) {
                $donnees = collect($l)->only(['date', 'heure', 'titre', 'type', 'format', 'canaux', 'responsable_id', 'responsable', 'notes'])->all();
                CommunicationPublication::create($this->responsableDuTour($donnees) + ['statut' => 'prevu', 'cree_par' => $moi]);
            }
        });

        return response()->json(['ok' => true, 'importees' => $valides->count(), 'erreurs' => $erreurs]);
    }

    /** GET /admin/communication/calendrier/modele : modèle CSV à remplir. */
    public function modele()
    {
        return response()->json(['ok' => true, 'nom' => 'modele-calendrier-editorial.csv', 'csv' => CalendrierImport::modele()]);
    }

    /** GET /admin/communication/calendrier/export?mois=AAAA-MM : CSV du mois (même disposition que le modèle). */
    public function exporter(Request $request)
    {
        [$annee, $mois] = $this->mois($request->query('mois'));
        $debut = Carbon::create($annee, $mois, 1);
        $tours = Cal::toursEntre($debut->toDateString(), $debut->copy()->endOfMonth()->toDateString());
        // Une cellule qui commence par = @ (ou + - hors nombre) serait lue par Excel comme une formule : elle est neutralisée.
        $cellule = function ($c) {
            $c = preg_match('/^(?:[=@\t\r]|[+\-](?!\s*\d))/', (string) $c) ? "'".$c : (string) $c;

            return preg_match('/[;"\r\n]/', $c) ? '"'.str_replace('"', '""', $c).'"' : $c;
        };
        $lignes = [['date', 'heure', 'sujet', 'style', 'canal', 'responsable', 'type', 'notes', 'statut', 'liens']];
        $pubs = CommunicationPublication::with('responsableCompte')
            ->whereBetween('date', [$debut->toDateString(), $debut->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')->orderByRaw('heure is null')->orderBy('heure')->get();
        foreach ($pubs as $p) {
            $l = Cal::ligne($p, $tours);
            $lignes[] = [$p->date->format('d/m/Y'), $p->heure, $p->titre, $p->format, implode(', ', $l['canaux_labels']),
                $l['responsable_effectif']['nom'], $l['type_label'], $p->notes, $l['statut_label'], implode(' ', $l['liens'])];
        }
        $csv = "\xEF\xBB\xBF".collect($lignes)->map(fn ($l) => collect($l)->map($cellule)->implode(';'))->implode("\r\n")."\r\n";

        return response()->json(['ok' => true, 'nom' => sprintf('calendrier-editorial-%04d-%02d.csv', $annee, $mois), 'csv' => $csv, 'nombre' => $pubs->count()]);
    }

    /** POST /admin/communication/calendrier/dupliquer {mois} : recopie le mois précédent (mêmes jours du mois). */
    public function dupliquer(Request $request)
    {
        [$annee, $mois] = $this->mois($request->input('mois'));
        $cible = Carbon::create($annee, $mois, 1);
        $source = $cible->copy()->subMonthNoOverflow();
        $pubs = CommunicationPublication::whereBetween('date', [$source->toDateString(), $source->copy()->endOfMonth()->toDateString()])
            ->where('statut', '!=', 'annule')->orderBy('date')->get();
        $n = 0;
        DB::transaction(function () use ($pubs, $cible, $request, &$n) {
            foreach ($pubs as $p) {
                if ($p->date->day > $cible->daysInMonth) {
                    continue; // 31 du mois précédent sans équivalent
                }
                CommunicationPublication::create([
                    'date' => $cible->copy()->day($p->date->day)->toDateString(),
                    'heure' => $p->heure, 'titre' => $p->titre, 'type' => $p->type, 'format' => $p->format,
                    'canaux' => $p->canaux, 'responsable_id' => $p->responsable_id, 'responsable' => $p->responsable,
                    'notes' => $p->notes, 'statut' => 'prevu', 'cree_par' => $request->user()->id,
                ]);
                $n++;
            }
        });

        return response()->json(['ok' => true, 'copiees' => $n]);
    }

    // ── Tours de rôle ───────────────────────────────────────────────────

    private function validerTour(Request $request, string $prefixe = ''): array
    {
        return $request->validate([
            "{$prefixe}du" => 'required|date',
            "{$prefixe}au" => "required|date|after_or_equal:{$prefixe}du",
            "{$prefixe}responsable_id" => ['nullable', 'integer', Rule::in(Cal::equipe()->pluck('id')->all())],
            "{$prefixe}responsable" => 'nullable|string|max:150',
        ], [
            "{$prefixe}au.after_or_equal" => 'La fin du tour doit suivre son début.',
            "{$prefixe}responsable_id.in" => 'Ce compte ne fait pas partie du département Communication.',
        ]);
    }

    private function exigerResponsable(array $t): ?JsonResponse
    {
        if (empty($t['responsable_id']) && trim((string) ($t['responsable'] ?? '')) === '') {
            return response()->json(['ok' => false, 'message' => 'Choisissez le responsable du tour.', 'errors' => ['responsable' => ['Choisissez le responsable du tour.']]], 422);
        }

        return null;
    }

    /** POST /admin/communication/tours */
    public function creerTour(Request $request)
    {
        $d = $this->validerTour($request);
        if ($r = $this->exigerResponsable($d)) {
            return $r;
        }
        $t = CommunicationTour::create([...$d, 'responsable' => ! empty($d['responsable_id']) ? null : $d['responsable'], 'cree_par' => $request->user()->id]);

        return response()->json(['ok' => true, 'tour' => Cal::ligneTour($t->fresh('responsableCompte'))], 201);
    }

    /** PUT /admin/communication/tours/{id} */
    public function modifierTour(Request $request, int $id)
    {
        $t = CommunicationTour::findOrFail($id);
        if ($refus = $this->refusTesteur($request, $t)) {
            return $refus;
        }
        $d = $this->validerTour($request);
        if ($r = $this->exigerResponsable($d)) {
            return $r;
        }
        $t->update([...$d, 'responsable' => ! empty($d['responsable_id']) ? null : $d['responsable']]);

        return response()->json(['ok' => true, 'tour' => Cal::ligneTour($t->fresh('responsableCompte'))]);
    }

    /** DELETE /admin/communication/tours/{id} */
    public function supprimerTour(Request $request, int $id)
    {
        $t = CommunicationTour::findOrFail($id);
        if ($refus = $this->refusTesteur($request, $t)) {
            return $refus;
        }
        $t->delete();

        return response()->json(['ok' => true]);
    }

    /** POST /admin/communication/tours/proposer {mois, membres: [{id}|{nom}], decalage?} : proposition à ajuster. */
    public function proposerTours(Request $request)
    {
        $d = $request->validate([
            'mois' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'membres' => 'required|array|min:1|max:20',
            'membres.*.id' => ['nullable', 'integer', Rule::in(Cal::equipe()->pluck('id')->all())],
            'membres.*.nom' => 'nullable|string|max:150',
            'decalage' => 'sometimes|integer|min:0|max:20',
        ], ['membres.required' => 'Choisissez au moins un membre de l\'équipe.']);
        $membres = collect($d['membres'])->filter(fn ($m) => ! empty($m['id']) || trim((string) ($m['nom'] ?? '')) !== '')->values()->all();
        if ($membres === []) {
            return response()->json(['ok' => false, 'message' => 'Choisissez au moins un membre de l\'équipe.'], 422);
        }
        [$annee, $mois] = $this->mois($d['mois']);
        $noms = Cal::equipe()->mapWithKeys(fn ($u) => [$u->id => Cal::nomCompte($u)]);
        $tours = collect(Cal::proposerTours($annee, $mois, $membres, (int) ($d['decalage'] ?? 0)))
            ->map(fn ($t) => $t + ['nom' => $t['responsable_id'] ? ($noms[$t['responsable_id']] ?? null) : $t['responsable']]);

        return response()->json(['ok' => true, 'tours' => $tours->values()]);
    }

    /** POST /admin/communication/tours/lot {mois, tours: [{du, au, responsable_id|responsable}]} : remplace les tours du mois. */
    public function enregistrerTours(Request $request)
    {
        $request->validate([
            'mois' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'tours' => 'present|array|max:31',
        ]);
        [$annee, $mois] = $this->mois($request->input('mois'));
        $debut = Carbon::create($annee, $mois, 1)->toDateString();
        $fin = Carbon::create($annee, $mois, 1)->endOfMonth()->toDateString();
        $d = $request->validate([
            'tours.*.du' => "required|date|after_or_equal:{$debut}|before_or_equal:{$fin}",
            'tours.*.au' => 'required|date|after_or_equal:tours.*.du',
            'tours.*.responsable_id' => ['nullable', 'integer', Rule::in(Cal::equipe()->pluck('id')->all())],
            'tours.*.responsable' => 'nullable|string|max:150',
        ], ['tours.*.du.after_or_equal' => 'Chaque tour doit commencer dans le mois choisi.', 'tours.*.du.before_or_equal' => 'Chaque tour doit commencer dans le mois choisi.']);
        foreach ($d['tours'] ?? [] as $i => $t) {
            if ($r = $this->exigerResponsable($t)) {
                return response()->json(['ok' => false, 'message' => 'Tour n° '.($i + 1).' : choisissez le responsable.'], 422);
            }
        }
        DB::transaction(function () use ($d, $debut, $fin, $request) {
            // Un compte de test ne remplace que ses propres tours.
            CommunicationTour::whereBetween('du', [$debut, $fin])
                ->when($request->user()->estTesteur(), fn ($q) => $q->where('cree_par', $request->user()->id))->delete();
            foreach ($d['tours'] ?? [] as $t) {
                CommunicationTour::create(['du' => $t['du'], 'au' => $t['au'], 'responsable_id' => $t['responsable_id'] ?? null,
                    'responsable' => ! empty($t['responsable_id']) ? null : ($t['responsable'] ?? null), 'cree_par' => $request->user()->id]);
            }
        });

        return response()->json(['ok' => true, 'tours' => Cal::toursEntre($debut, $fin)->map(fn ($t) => Cal::ligneTour($t))->values()]);
    }
}

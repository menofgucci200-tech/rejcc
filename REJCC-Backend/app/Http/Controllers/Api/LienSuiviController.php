<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LienSuivi;
use App\Models\LienSuiviClic;
use App\Support\OutilsCommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Liens de suivi de la Communication : le site (rejcc.site/l/{code}) demande
 * ici la cible du lien et fait compter le clic, puis redirige. Seules les
 * adresses enregistrées par la Com sont servies : aucune redirection ouverte.
 */
class LienSuiviController extends Controller
{
    /** GET /liens/{code}?compter=0|1 : cible d'un lien actif (public, limité par visiteur). */
    public function ouvrir(Request $request, string $code)
    {
        $lien = LienSuivi::where('code', mb_strtolower($code))->where('actif', true)->first();
        if (! $lien) {
            return response()->json(['ok' => false, 'message' => 'Lien introuvable.'], 404);
        }
        // Les robots d'aperçu (WhatsApp, Facebook…) reçoivent la cible sans compter de clic.
        if ($request->query('compter', '1') !== '0') {
            $lien->compterClic();
        }

        return response()->json(['ok' => true, 'cible' => $lien->cible, 'canal' => $lien->canal]);
    }

    /** GET /admin/liens-suivi : les liens avec leurs clics (total, 30 derniers jours) et le cumul par canal. */
    public function index()
    {
        $depuis = now()->subDays(29)->toDateString();
        $totaux = LienSuiviClic::query()->selectRaw('lien_id, sum(clics) as total')->groupBy('lien_id')->pluck('total', 'lien_id');
        $recents = LienSuiviClic::query()->where('jour', '>=', $depuis)->selectRaw('lien_id, sum(clics) as total')->groupBy('lien_id')->pluck('total', 'lien_id');

        $liens = LienSuivi::with('auteur:id,prenom,nom')->latest('id')->get()
            ->map(fn (LienSuivi $l) => $this->format($l) + [
                'clics_total' => (int) ($totaux[$l->id] ?? 0),
                'clics_30j' => (int) ($recents[$l->id] ?? 0),
            ]);

        $parCanal = collect(LienSuivi::CANAUX)->map(fn ($label, $canal) => [
            'canal' => $canal,
            'label' => $label,
            'liens' => $liens->where('canal', $canal)->count(),
            'clics_total' => $liens->where('canal', $canal)->sum('clics_total'),
            'clics_30j' => $liens->where('canal', $canal)->sum('clics_30j'),
        ])->filter(fn ($c) => $c['liens'] > 0)->values();

        return response()->json(['ok' => true, 'canaux' => LienSuivi::CANAUX, 'liens' => $liens, 'par_canal' => $parCanal]);
    }

    /** POST /admin/liens-suivi */
    public function store(Request $request)
    {
        $v = Validator::make($this->entree($request), $this->regles(), $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $lien = LienSuivi::create($v->validated() + ['cree_par' => $request->user()->id, 'actif' => true]);

        return response()->json(['ok' => true, 'lien' => $this->format($lien->load('auteur:id,prenom,nom'))], 201);
    }

    /** PUT /admin/liens-suivi/{id} : modification, ou simple activation/désactivation ({actif}). */
    public function update(Request $request, int $id)
    {
        $lien = LienSuivi::findOrFail($id);
        // Compte de test : jamais la cible d'un vrai lien diffusé par la Com.
        if (! OutilsCommunication::peutModifier($request->user(), $lien->cree_par)) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }
        $regles = collect($this->regles($lien->id))->map(fn ($r) => array_merge(['sometimes'], $r))->all()
            + ['actif' => ['sometimes', 'boolean']];
        $v = Validator::make($this->entree($request), $regles, $this->messages());
        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        $lien->update($v->validated());

        return response()->json(['ok' => true, 'lien' => $this->format($lien->fresh('auteur:id,prenom,nom'))]);
    }

    /** DELETE /admin/liens-suivi/{id} : supprime le lien et ses compteurs. */
    public function destroy(Request $request, int $id)
    {
        $lien = LienSuivi::findOrFail($id);
        if (! OutilsCommunication::peutModifier($request->user(), $lien->cree_par)) {
            return response()->json(['ok' => false, 'message' => OutilsCommunication::MESSAGE_TESTEUR], 403);
        }
        $lien->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * GET /admin/liens-suivi/statistiques?mois=AAAA-MM (ou ?du=AAAA-MM-JJ&au=AAAA-MM-JJ) :
     * clics de la période par jour, par canal et par lien.
     */
    public function statistiques(Request $request)
    {
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? Carbon::parse($v)->startOfDay() : null;
        [$du, $au] = [$date($request->query('du')), $date($request->query('au'))];
        if ($du && $au && $du->lte($au) && $du->diffInDays($au) <= 400) {
            [$debut, $fin, $mois] = [$du, $au, null];
        } else {
            $mois = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('mois')) ? $request->query('mois') : now()->format('Y-m');
            $debut = Carbon::createFromFormat('Y-m-d', $mois.'-01')->startOfDay();
            $fin = $debut->copy()->endOfMonth();
        }

        $lignes = LienSuiviClic::query()
            ->join('liens_suivi', 'liens_suivi.id', '=', 'liens_suivi_clics.lien_id')
            ->whereBetween('jour', [$debut->toDateString(), $fin->toDateString()])
            ->get(['liens_suivi_clics.jour', 'liens_suivi_clics.clics', 'liens_suivi.id', 'liens_suivi.code', 'liens_suivi.libelle', 'liens_suivi.canal']);

        $parJour = [];
        for ($j = $debut->copy(); $j->lte($fin); $j->addDay()) {
            $parJour[$j->toDateString()] = 0;
        }
        foreach ($lignes as $l) {
            $jour = substr((string) $l->jour, 0, 10);
            $parJour[$jour] = ($parJour[$jour] ?? 0) + $l->clics;
        }

        return response()->json([
            'ok' => true,
            'mois' => $mois,
            'du' => $debut->toDateString(),
            'au' => $fin->toDateString(),
            'total' => (int) $lignes->sum('clics'),
            'par_jour' => $parJour,
            'par_canal' => $lignes->groupBy('canal')
                ->map(fn ($g, $canal) => ['canal' => $canal, 'label' => LienSuivi::CANAUX[$canal] ?? $canal, 'clics' => (int) $g->sum('clics')])
                ->sortByDesc('clics')->values(),
            'par_lien' => $lignes->groupBy('id')
                ->map(fn ($g) => ['code' => $g->first()->code, 'libelle' => $g->first()->libelle, 'canal' => $g->first()->canal, 'clics' => (int) $g->sum('clics')])
                ->sortByDesc('clics')->values(),
        ]);
    }

    private function regles(?int $ignorer = null): array
    {
        return [
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9-]+$/', Rule::unique('liens_suivi', 'code')->ignore($ignorer)],
            'cible' => ['required', 'string', 'max:2000', 'url:http,https'],
            'libelle' => ['required', 'string', 'max:150'],
            'canal' => ['required', Rule::in(array_keys(LienSuivi::CANAUX))],
        ];
    }

    private function messages(): array
    {
        return [
            'code.regex' => 'Le code ne peut contenir que des lettres, des chiffres et des tirets.',
            'code.unique' => 'Ce code est déjà utilisé par un autre lien.',
            'code.min' => 'Le code doit compter au moins 3 caractères.',
            'cible.url' => "L'adresse de destination doit être une adresse web complète commençant par http:// ou https://.",
            'libelle.required' => 'Donnez un libellé au lien (par exemple « Affiche gala – WhatsApp »).',
            'canal.in' => 'Choisissez un canal dans la liste.',
        ];
    }

    /** Codes en minuscules (le lien public n'est pas sensible à la casse), espaces retirés. */
    private function entree(Request $request): array
    {
        $d = $request->all();
        foreach (['code', 'cible', 'libelle'] as $champ) {
            if (isset($d[$champ]) && is_string($d[$champ])) {
                $d[$champ] = trim($d[$champ]);
            }
        }
        if (isset($d['code']) && is_string($d['code'])) {
            $d['code'] = mb_strtolower($d['code']);
        }

        return $d;
    }

    private function format(LienSuivi $l): array
    {
        return [
            'id' => $l->id,
            'code' => $l->code,
            'cible' => $l->cible,
            'libelle' => $l->libelle,
            'canal' => $l->canal,
            'canal_label' => LienSuivi::CANAUX[$l->canal] ?? $l->canal,
            'actif' => $l->actif,
            'cree_par' => $l->auteur ? trim($l->auteur->prenom.' '.$l->auteur->nom) : null,
            'created_at' => $l->created_at?->toIso8601String(),
        ];
    }
}

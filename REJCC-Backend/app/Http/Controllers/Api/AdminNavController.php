<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Formation;
use App\Models\MarketplaceListing;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\MentorApplication;
use App\Models\Mentorship;
use App\Models\PartnershipRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Barre de navigation de l'administration : éléments « À traiter » (cloche et
 * pastilles du menu) et recherche transversale. Chaque élément porte la
 * section de permission requise : un admin restreint ne voit que les siennes.
 */
class AdminNavController extends Controller
{
    private function autorise(User $u, string $section): bool
    {
        return ! is_array($u->permissions) || in_array($section, $u->permissions, true);
    }

    /** GET /admin/a-traiter */
    public function aTraiter(Request $request)
    {
        $me = $request->user();
        $elements = [
            ['cle' => 'adhesions', 'section' => 'adhesions', 'route' => 'admin.adhesions', 'libelle' => 'Adhésions à examiner',
                'nombre' => MembershipApplication::where('statut', 'en_attente')->count() + Member::where('statut', 'en_attente')->count()],
            ['cle' => 'contacts', 'section' => 'contacts', 'route' => 'admin.contacts', 'libelle' => 'Messages de contact non traités',
                'nombre' => Contact::where('traite', false)->count()],
            ['cle' => 'partenariats', 'section' => 'partenariats', 'route' => 'admin.partenariats', 'libelle' => 'Demandes de partenariat',
                'nombre' => PartnershipRequest::where('statut', 'nouveau')->count()],
            ['cle' => 'marketplace', 'section' => 'communaute', 'route' => 'admin.marketplace', 'libelle' => 'Annonces Marketplace à valider',
                'nombre' => MarketplaceListing::where('statut', 'en_attente')->count()],
            ['cle' => 'annonces_signalees', 'section' => 'communaute', 'route' => 'admin.marketplace', 'libelle' => 'Annonces Marketplace signalées',
                'nombre' => \Illuminate\Support\Facades\DB::table('listing_reports')->where('statut', 'nouveau')->distinct()->count('listing_id')],
            ['cle' => 'projets', 'section' => 'projets', 'route' => 'admin.projets', 'libelle' => 'Projets en évaluation',
                'nombre' => Project::where('statut', 'evaluation')->count()],
            ['cle' => 'mentorat', 'section' => 'mentors', 'route' => 'admin.mentors', 'libelle' => 'Candidatures de mentors',
                'nombre' => MentorApplication::where('statut', 'en_attente')->count()],
            ['cle' => 'mentorat_retard', 'section' => 'mentors', 'route' => 'admin.mentors', 'libelle' => 'Demandes de mentorat sans réponse (> 7 j)',
                'nombre' => Mentorship::where('statut', 'en_attente')->where('created_at', '<', now()->subDays(7))->count()],
            ['cle' => 'signalements', 'section' => 'messagerie', 'route' => 'admin.signalements', 'libelle' => 'Conversations signalées',
                'nombre' => \App\Models\MessageReport::where('statut', 'nouveau')->count()],
            ['cle' => 'avis', 'section' => 'groupes', 'route' => 'admin.groupes', 'libelle' => 'Avis de membres signalés',
                'nombre' => \App\Models\MemberReview::whereNotNull('signale_at')->where('masque', false)->count()],
        ];

        $elements = array_values(array_filter($elements, fn ($e) => $this->autorise($me, $e['section'])));

        return response()->json([
            'ok' => true,
            'elements' => $elements,
            'total' => array_sum(array_column($elements, 'nombre')),
        ]);
    }

    /** GET /admin/recherche?q= — membres, adhésions, formations, événements, projets. */
    public function recherche(Request $request)
    {
        $me = $request->user();
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'groupes' => []]);
        }
        $like = "%{$q}%";
        $groupes = [];

        if ($this->autorise($me, 'membres')) {
            // Chaque mot doit figurer dans le prénom, le nom ou l'e-mail (« Awa Traoré »).
            $comptes = User::query();
            foreach (preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) as $mot) {
                $comptes->where(fn ($w) => $w->where('prenom', 'like', "%{$mot}%")->orWhere('nom', 'like', "%{$mot}%")->orWhere('email', 'like', "%{$mot}%"));
            }
            $groupes['Comptes'] = $comptes->limit(5)->get(['id', 'prenom', 'nom', 'email', 'role'])
                ->map(fn ($u) => ['titre' => trim("{$u->prenom} {$u->nom}") ?: $u->email, 'detail' => $u->email.' · '.$u->roleLabel(), 'route' => 'admin.members', 'params' => ['q' => $u->email], 'icon' => 'user'])->all();
        }
        if ($this->autorise($me, 'adhesions')) {
            $groupes['Adhésions'] = MembershipApplication::where(fn ($w) => $w->where('prenom', 'like', $like)->orWhere('nom', 'like', $like)->orWhere('email', 'like', $like))
                ->latest()->limit(4)->get(['id', 'prenom', 'nom', 'email', 'statut'])
                ->map(fn ($a) => ['titre' => trim("{$a->prenom} {$a->nom}"), 'detail' => $a->email.' · '.str_replace('_', ' ', $a->statut), 'route' => 'admin.adhesions', 'params' => ['q' => $a->email], 'icon' => 'file-text'])->all();
        }
        if ($this->autorise($me, 'formations')) {
            $groupes['Formations'] = Formation::where('title', 'like', $like)->orWhere('category', 'like', $like)->limit(4)->get(['id', 'title', 'category', 'is_published'])
                ->map(fn ($f) => ['titre' => $f->title, 'detail' => $f->category.($f->is_published ? '' : ' · brouillon'), 'route' => 'admin.formations', 'params' => [], 'icon' => 'graduation-cap'])->all();
        }
        if ($this->autorise($me, 'evenements')) {
            $groupes['Événements'] = Event::where('title', 'like', $like)->orWhere('location', 'like', $like)->orderByDesc('starts_at')->limit(4)->get(['id', 'title', 'starts_at', 'location'])
                ->map(fn ($e) => ['titre' => $e->title, 'detail' => trim(($e->starts_at?->translatedFormat('j M Y') ?? '').' · '.($e->location ?? ''), ' ·'), 'route' => 'admin.evenements', 'params' => [], 'icon' => 'calendar'])->all();
        }
        if ($this->autorise($me, 'projets')) {
            $groupes['Projets'] = Project::where('title', 'like', $like)->limit(4)->get(['id', 'title', 'statut'])
                ->map(fn ($p) => ['titre' => $p->title, 'detail' => Project::STATUTS[$p->statut] ?? $p->statut, 'route' => 'admin.projets', 'params' => [], 'icon' => 'nav-projects'])->all();
        }

        return response()->json(['ok' => true, 'groupes' => array_filter($groupes)]);
    }
}

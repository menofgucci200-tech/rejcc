<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enregistre chaque action d'écriture réussie sous /api/admin/* dans le
 * journal d'audit : qui, quoi, quand. Appliqué au groupe admin.
 */
class LogAdminActivity
{
    /** Slug de ressource => libellé humain. */
    private const LABELS = [
        'formations' => 'Formation',
        'events' => 'Événement',
        'news' => 'Actualité',
        'opportunities' => 'Opportunité',
        'resources' => 'Ressource',
        'projects' => 'Projet',
        'members' => 'Membre',
        'membership-applications' => 'Candidature',
        'adhesions' => 'Adhésion',
        'documents' => 'Document',
        'site-content' => 'Contenu du site',
        'notifications' => 'Notification',
        'partenariats' => 'Partenariat',
        'contacts' => 'Contact',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() < 400
            && ($user = $request->user())) {
            $this->record($request, $user);
        }

        return $response;
    }

    private function record(Request $request, $user): void
    {
        $segments = array_values(array_filter(explode('/', $request->path()))); // ['api','admin','formations','5']
        $resource = $segments[2] ?? '';
        $id = $segments[3] ?? null;
        $subAction = $segments[4] ?? null; // accept / reject / traite …

        $label = self::LABELS[$resource] ?? ucfirst($resource);
        $target = $label.($id && is_numeric($id) ? " #{$id}" : '');

        $action = match (true) {
            $subAction === 'accept' => 'Acceptation',
            $subAction === 'reject' => 'Rejet',
            $subAction === 'traite' => 'Marqué traité',
            $subAction === 'broadcast', $resource === 'notifications' => 'Notification diffusée',
            $request->isMethod('POST') => 'Création',
            $request->isMethod('DELETE') => 'Suppression',
            default => 'Modification',
        };

        $nom = trim(($user->prenom ?? '').' '.($user->nom ?? '')) ?: ($user->name ?: 'Administrateur');

        AuditLog::create([
            'user_id' => $user->id,
            'actor' => $nom.' ('.$user->email.')',
            'action' => $action,
            'target' => $target,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'ip' => $request->ip(),
        ]);
    }
}

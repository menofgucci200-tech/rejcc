<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Client HTTP vers REJCC-Backend. REJCC-Frontend n'a pas d'accès direct à la
 * base de données : toutes les données transitent par ces appels /api/*.
 */
class Api
{
    protected static function client(?string $token = null): PendingRequest
    {
        $client = Http::baseUrl(config('services.backend.url'))->acceptJson();

        return $token ? $client->withToken($token) : $client;
    }

    public static function get(string $path, array $query = [], ?string $token = null): array
    {
        $response = static::client($token)->get($path, $query);

        return $response->json() ?? ['ok' => false];
    }

    public static function post(string $path, array $data = [], ?string $token = null): array
    {
        $response = static::client($token)->post($path, $data);

        return $response->json() ?? ['ok' => false];
    }

    public static function put(string $path, array $data = [], ?string $token = null): array
    {
        $response = static::client($token)->put($path, $data);

        return $response->json() ?? ['ok' => false];
    }

    public static function delete(string $path, ?string $token = null, array $data = []): array
    {
        $response = static::client($token)->delete($path, $data);

        return $response->json() ?? ['ok' => false];
    }

    /**
     * Appel public pour un visiteur (vérification des certificats) : l'API
     * reçoit l'empreinte de l'adresse du visiteur pour limiter les essais en
     * série par personne et non pour tout le site. Renvoie aussi le statut HTTP.
     */
    public static function visiteur(string $methode, string $path, array $data = []): array
    {
        $r = static::client()->withHeaders(['X-Visiteur' => hash('sha256', request()->ip().'|'.config('app.key'))])
            ->{$methode}($path, $data);

        return ($r->json() ?? ['ok' => false]) + ['_statut' => $r->status()];
    }

    /** Réponse brute (fichier PDF…) de l'API. */
    public static function brut(string $path, ?string $token = null, array $entetes = []): \Illuminate\Http\Client\Response
    {
        return static::client($token)->withHeaders($entetes)->accept('*/*')->get($path);
    }

    /** Token Bearer de l'utilisateur courant (posé en session au login/register). */
    public static function token(): ?string
    {
        return session('api_token');
    }

    /** Utilisateur courant (posé en session au login/register), en objet pour un accès ->prop. */
    public static function user(): ?object
    {
        $user = session('api_user');

        return $user ? (object) $user : null;
    }
}

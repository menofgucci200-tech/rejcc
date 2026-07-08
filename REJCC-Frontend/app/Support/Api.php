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

    public static function delete(string $path, ?string $token = null): array
    {
        $response = static::client($token)->delete($path);

        return $response->json() ?? ['ok' => false];
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

<?php

namespace App\Support;

/**
 * QCM des formations (quiz de module et examen final). Les bonnes réponses ne
 * quittent jamais le serveur : le membre reçoit les questions sans la clé, et
 * la correction se fait ici.
 *
 * Format : [{question: string, choix: [string, …], bonne: index du bon choix}]
 */
class Quiz
{
    /** Règles de validation pour un quiz saisi par l'admin (clé $champ). */
    public static function rules(string $champ, int $max = 30): array
    {
        return [
            $champ => "nullable|array|max:{$max}",
            "{$champ}.*.question" => 'required|string|min:3|max:400',
            "{$champ}.*.choix" => 'required|array|min:2|max:6',
            "{$champ}.*.choix.*" => 'required|string|max:250',
            "{$champ}.*.bonne" => 'required|integer|min:0',
        ];
    }

    /** Message d'erreur si une bonne réponse ne correspond à aucun choix, sinon null. */
    public static function invalid(?array $questions): ?string
    {
        foreach ($questions ?? [] as $i => $q) {
            if ((int) $q['bonne'] >= count($q['choix'])) {
                return 'Question '.($i + 1).' : la bonne réponse doit être l\'un des choix proposés.';
            }
        }

        return null;
    }

    /** Ne garde que les clés attendues (et des index propres). */
    public static function normalize(?array $questions): ?array
    {
        $out = array_values(array_map(fn (array $q) => [
            'question' => trim($q['question']),
            'choix' => array_values(array_map('trim', $q['choix'])),
            'bonne' => (int) $q['bonne'],
        ], $questions ?? []));

        return $out ?: null;
    }

    /** Questions envoyées au membre : sans la bonne réponse. */
    public static function forMember(?array $questions): array
    {
        return array_map(fn (array $q) => ['question' => $q['question'], 'choix' => $q['choix']], $questions ?? []);
    }

    /**
     * Corrige les réponses (index du choix par question).
     *
     * @return array{correctes: int, total: int, score: int}
     */
    public static function grade(?array $questions, array $reponses): array
    {
        $questions = $questions ?? [];
        $correctes = 0;
        foreach ($questions as $i => $q) {
            if (isset($reponses[$i]) && (int) $reponses[$i] === (int) $q['bonne']) {
                $correctes++;
            }
        }
        $total = count($questions);

        return ['correctes' => $correctes, 'total' => $total, 'score' => $total ? (int) round($correctes * 100 / $total) : 100];
    }
}

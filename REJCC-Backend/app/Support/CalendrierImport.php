<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Import du calendrier Excel de la Communication : CSV (export Excel
 * « CSV UTF-8 », séparateur ; ou ,) ou classeur .xlsx (première feuille, lu
 * avec ZipArchive et SimpleXML, sans dépendance). Colonnes attendues : date,
 * sujet, style, canal, responsable (heure, type et notes facultatives).
 */
class CalendrierImport
{
    /** En-têtes reconnus (sans accents, en minuscules) → champ. */
    private const COLONNES = [
        'date' => 'date', 'jour' => 'date', 'date de publication' => 'date',
        'heure' => 'heure', 'horaire' => 'heure',
        'sujet' => 'titre', 'titre' => 'titre', 'theme' => 'titre', 'sujet / titre' => 'titre', 'contenu' => 'titre',
        'style' => 'format', 'format' => 'format', 'style de video' => 'format', 'type de video' => 'format', 'style video' => 'format',
        'canal' => 'canaux', 'canaux' => 'canaux', 'reseau' => 'canaux', 'reseaux' => 'canaux', 'canal de publication' => 'canaux', 'plateforme' => 'canaux',
        'responsable' => 'responsable', 'personne' => 'responsable', 'qui' => 'responsable', 'membre' => 'responsable', 'en charge' => 'responsable',
        'type' => 'type', "type d'information" => 'type', 'type d information' => 'type', 'categorie' => 'type',
        'notes' => 'notes', 'note' => 'notes', 'brief' => 'notes', 'commentaire' => 'notes', 'commentaires' => 'notes',
    ];

    /** Façons courantes d'écrire un canal → clé. */
    private const SYNONYMES_CANAUX = [
        'youtube' => 'youtube', 'yt' => 'youtube', 'you tube' => 'youtube',
        'tiktok' => 'tiktok', 'tik tok' => 'tiktok',
        'linkedin' => 'linkedin', 'linked in' => 'linkedin',
        'facebook' => 'facebook', 'fb' => 'facebook',
        'instagram' => 'instagram', 'insta' => 'instagram', 'ig' => 'instagram',
        'whatsapp' => 'whatsapp', 'whats app' => 'whatsapp', 'wa' => 'whatsapp', 'statut whatsapp' => 'whatsapp',
        'site' => 'site', 'site web' => 'site', 'site internet' => 'site', 'rejcc.site' => 'site',
        'email' => 'email', 'e-mail' => 'email', 'mail' => 'email', 'courriel' => 'email', 'newsletter' => 'email',
        'annonces' => 'annonces', 'annonce' => 'annonces', 'communaute' => 'annonces', 'plateforme' => 'annonces',
    ];

    public static function normaliser(string $texte): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(Str::ascii(trim($texte)))));
    }

    /**
     * Lit le fichier et renvoie ses lignes brutes (en-tête compris).
     *
     * @return list<list<string>>
     */
    public static function lignesBrutes(string $contenu, string $nom): array
    {
        if (str_ends_with(mb_strtolower($nom), '.xlsx') || str_starts_with($contenu, "PK\x03\x04")) {
            return self::lireXlsx($contenu);
        }

        return self::lireCsv($contenu);
    }

    /** CSV : BOM retiré, Windows-1252 converti en UTF-8, séparateur deviné (; , ou tabulation). */
    public static function lireCsv(string $contenu): array
    {
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);
        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }
        $premiere = strtok($contenu, "\r\n") ?: '';
        $separateur = collect([';', ',', "\t"])->sortByDesc(fn ($s) => substr_count($premiere, $s))->first();

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, $contenu);
        rewind($flux);
        $lignes = [];
        while (($l = fgetcsv($flux, 0, $separateur, '"', '')) !== false) {
            $lignes[] = array_map(fn ($v) => trim((string) $v), $l);
        }
        fclose($flux);

        return $lignes;
    }

    /** Première feuille d'un classeur .xlsx (texte partagé, texte en ligne, nombres). */
    public static function lireXlsx(string $contenu): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $contenu);
        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('Fichier Excel illisible.');
        }
        try {
            $partages = [];
            if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                foreach (simplexml_load_string($xml)->si as $si) {
                    // Texte simple ou enrichi (plusieurs morceaux <r><t>).
                    $partages[] = self::texteXml($si);
                }
            }
            $chemin = 'xl/worksheets/sheet1.xml';
            $classeur = $zip->getFromName('xl/workbook.xml');
            $liens = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($classeur !== false && $liens !== false) {
                $wb = simplexml_load_string($classeur);
                $feuille = $wb->sheets->sheet[0] ?? null;
                $rid = $feuille ? (string) $feuille->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : '';
                foreach (simplexml_load_string($liens)->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $cible = ltrim((string) $rel['Target'], '/');
                        $chemin = str_starts_with($cible, 'xl/') ? $cible : 'xl/'.$cible;
                    }
                }
            }
            $xml = $zip->getFromName($chemin);
            if ($xml === false) {
                throw new \RuntimeException('Feuille introuvable dans le fichier Excel.');
            }
            $lignes = [];
            foreach (simplexml_load_string($xml)->sheetData->row as $row) {
                $ligne = [];
                foreach ($row->c as $c) {
                    $col = self::indexColonne(preg_replace('/\d+/', '', (string) $c['r']));
                    $type = (string) $c['t'];
                    $valeur = match ($type) {
                        's' => $partages[(int) $c->v] ?? '',
                        'inlineStr' => self::texteXml($c->is),
                        default => (string) $c->v,
                    };
                    $ligne[$col] = trim($valeur);
                }
                if ($ligne !== []) {
                    $max = max(array_keys($ligne));
                    $lignes[] = array_map(fn ($i) => $ligne[$i] ?? '', range(0, $max));
                } else {
                    $lignes[] = [];
                }
            }

            return $lignes;
        } finally {
            $zip->close();
            @unlink($tmp);
        }
    }

    /** Texte d'une cellule : <t> simple ou morceaux enrichis <r><t>. */
    private static function texteXml(\SimpleXMLElement $el): string
    {
        if (isset($el->t)) {
            return (string) $el->t;
        }
        $texte = '';
        foreach ($el->r as $r) {
            $texte .= (string) $r->t;
        }

        return $texte;
    }

    private static function indexColonne(string $lettres): int
    {
        $n = 0;
        foreach (str_split(strtoupper($lettres)) as $l) {
            $n = $n * 26 + (ord($l) - 64);
        }

        return max(0, $n - 1);
    }

    /**
     * Analyse les lignes : valeurs reconnues et erreurs, ligne par ligne
     * (numérotées comme dans le tableur, l'en-tête étant la ligne 1).
     *
     * @return array{colonnes: array<string, string>, lignes: list<array>, erreur: ?string}
     */
    public static function analyser(array $brutes, $equipe): array
    {
        // En-tête : première ligne non vide.
        while ($brutes !== [] && implode('', $brutes[0]) === '') {
            array_shift($brutes);
        }
        $entete = array_shift($brutes) ?? [];
        $colonnes = [];
        foreach ($entete as $i => $titre) {
            $champ = self::COLONNES[self::normaliser($titre)] ?? null;
            if ($champ && ! in_array($champ, $colonnes, true)) {
                $colonnes[$i] = $champ;
            }
        }
        if (! in_array('date', $colonnes, true) || ! in_array('titre', $colonnes, true)) {
            return ['colonnes' => [], 'lignes' => [], 'erreur' => 'Colonnes « date » et « sujet » introuvables sur la première ligne. Téléchargez le modèle pour voir la disposition attendue.'];
        }

        // Comptes de l'équipe : « Prénom Nom », « Nom Prénom » ou prénom seul s'il est unique.
        $noms = [];
        foreach ($equipe as $u) {
            foreach (array_filter([trim("{$u->prenom} {$u->nom}"), trim("{$u->nom} {$u->prenom}"), $u->email]) as $n) {
                $noms[self::normaliser($n)] = $u->id;
            }
        }
        $prenoms = collect($equipe)->groupBy(fn ($u) => self::normaliser((string) $u->prenom))->filter(fn ($g) => $g->count() === 1)->map(fn ($g) => $g->first()->id);
        $types = collect(CalendrierEditorial::TYPES)->mapWithKeys(fn ($t, $cle) => [self::normaliser($t['label']) => $cle, $cle => $cle]);

        $lignes = [];
        foreach ($brutes as $i => $l) {
            if (implode('', $l) === '') {
                continue;
            }
            $v = [];
            foreach ($colonnes as $index => $champ) {
                // L'export neutralise les formules par une apostrophe (« '=… ») : retirée ici.
                $v[$champ] = preg_replace("/^'(?=[=+\\-@])/", '', trim((string) ($l[$index] ?? '')));
            }
            $erreurs = [];

            $date = self::date($v['date'] ?? '');
            if (! $date) {
                $erreurs[] = ($v['date'] ?? '') === '' ? 'date manquante' : "date « {$v['date']} » non reconnue (JJ/MM/AAAA attendu)";
            }
            $titre = mb_substr($v['titre'] ?? '', 0, 200);
            if ($titre === '') {
                $erreurs[] = 'sujet manquant';
            }
            $heure = null;
            if (($v['heure'] ?? '') !== '') {
                $heure = self::heure($v['heure']);
                if (! $heure) {
                    $erreurs[] = "heure « {$v['heure']} » non reconnue";
                }
            }
            $type = 'autre';
            if (($v['type'] ?? '') !== '') {
                $type = $types[self::normaliser($v['type'])] ?? null;
                if (! $type) {
                    $erreurs[] = "type « {$v['type']} » inconnu";
                    $type = 'autre';
                }
            }
            [$canaux, $inconnus] = self::canaux($v['canaux'] ?? '');
            foreach ($inconnus as $c) {
                $erreurs[] = "canal « {$c} » inconnu";
            }
            if ($canaux === [] && ($v['canaux'] ?? '') === '') {
                $canaux = CalendrierEditorial::canauxParDefaut($type);
            }
            $responsableId = null;
            $responsable = trim($v['responsable'] ?? '');
            if ($responsable !== '') {
                $cle = self::normaliser($responsable);
                $responsableId = $noms[$cle] ?? $prenoms[$cle] ?? null;
            }

            $lignes[] = [
                'ligne' => $i + 2,
                'date' => $date?->toDateString(),
                'heure' => $heure,
                'titre' => $titre,
                'type' => $type,
                'format' => mb_substr($v['format'] ?? '', 0, 60) ?: null,
                'canaux' => $canaux,
                'responsable_id' => $responsableId,
                'responsable' => $responsableId ? null : (mb_substr($responsable, 0, 150) ?: null),
                'notes' => mb_substr($v['notes'] ?? '', 0, 5000) ?: null,
                'erreurs' => $erreurs,
            ];
        }

        return ['colonnes' => array_values($colonnes), 'lignes' => $lignes, 'erreur' => null];
    }

    /** JJ/MM/AAAA, JJ-MM-AA, AAAA-MM-JJ, JJ.MM.AAAA ou numéro de série Excel. */
    public static function date(string $v): ?Carbon
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{5}(\.\d+)?$/', $v)) {
            // Numéro de série Excel (jours depuis le 30/12/1899).
            return Carbon::create(1899, 12, 30)->addDays((int) $v)->startOfDay();
        }
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? Carbon::create((int) $m[1], (int) $m[2], (int) $m[3]) : null;
        }
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2}|\d{4})$/', $v, $m)) {
            $annee = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];

            return checkdate((int) $m[2], (int) $m[1], $annee) ? Carbon::create($annee, (int) $m[2], (int) $m[1]) : null;
        }

        return null;
    }

    /** 9h, 9h30, 09:30, 18 h 00 ou fraction de jour Excel (0,75). */
    public static function heure(string $v): ?string
    {
        $v = str_replace(' ', '', mb_strtolower(trim($v)));
        if (preg_match('/^0?[.,]\d+$/', $v)) {
            $minutes = (int) round((float) str_replace(',', '.', $v) * 1440);

            return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
        }
        if (preg_match('/^(\d{1,2})(?:[h:](\d{2})?)?(?::\d{2})?$/', $v, $m) && (int) $m[1] < 24 && (int) ($m[2] ?? 0) < 60) {
            return sprintf('%02d:%02d', (int) $m[1], (int) ($m[2] ?? 0));
        }

        return null;
    }

    /**
     * « Facebook + Instagram », « FB, WA », « Tous »… → clés connues et inconnues.
     *
     * @return array{0: list<string>, 1: list<string>}
     */
    public static function canaux(string $v): array
    {
        $v = trim($v);
        if ($v === '') {
            return [[], []];
        }
        if (in_array(self::normaliser($v), ['tous', 'tous les canaux', 'tout'], true)) {
            return [array_keys(CalendrierEditorial::CANAUX), []];
        }
        $connus = $inconnus = [];
        // Libellés de l'export (« Annonces (Communauté) »…) reconnus aussi : l'export se réimporte.
        $libelles = collect(CalendrierEditorial::CANAUX)->mapWithKeys(fn ($l, $cle) => [self::normaliser($l) => $cle])->all();
        foreach (preg_split('/\s*(?:[,;\/+&|]|\bet\b)\s*/u', $v, -1, PREG_SPLIT_NO_EMPTY) as $morceau) {
            $cle = self::normaliser($morceau);
            if ($cle === 'reseaux sociaux' || $cle === 'reseaux') {
                $connus = array_merge($connus, CalendrierEditorial::RESEAUX_SOCIAUX);
            } elseif ($c = self::SYNONYMES_CANAUX[$cle] ?? $libelles[$cle] ?? (isset(CalendrierEditorial::CANAUX[$cle]) ? $cle : null)) {
                $connus[] = $c;
            } else {
                $inconnus[] = trim($morceau);
            }
        }

        return [array_values(array_intersect(array_keys(CalendrierEditorial::CANAUX), $connus)), $inconnus];
    }

    /** Modèle CSV téléchargeable (séparateur ; comme l'Excel français, BOM UTF-8). */
    public static function modele(): string
    {
        $lignes = [
            ['date', 'heure', 'sujet', 'style', 'canal', 'responsable', 'type', 'notes'],
            ['02/11/2026', '18:00', 'Annonce du gala de fin d\'année', 'Vidéo courte', 'Facebook, Instagram, TikTok', 'Prénom Nom', 'Nouvel événement', 'Teaser de 30 secondes'],
            ['03/11/2026', '', 'Portrait CathoImpact', 'Carrousel', 'LinkedIn, Facebook, Site', 'Prénom Nom', 'Portrait CathoImpact', ''],
            ['04/11/2026', '09:00', 'Rappel : formation en ligne de jeudi', 'Visuel', 'WhatsApp, E-mail, Annonces', 'Prénom Nom', 'Formation', ''],
        ];

        return "\xEF\xBB\xBF".collect($lignes)->map(fn ($l) => collect($l)->map(fn ($c) => str_contains($c, ';') || str_contains($c, '"') ? '"'.str_replace('"', '""', $c).'"' : $c)->implode(';'))->implode("\r\n")."\r\n";
    }
}

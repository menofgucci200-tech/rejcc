<?php

namespace App\Support;

/**
 * Habillage HTML des e-mails du REJCC (styles en ligne, compatibles avec
 * les messageries) : bandeau bleu nuit avec le monogramme et le nom complet
 * du réseau, filet rouge, contenu, bouton vers la plateforme, signature et
 * pied de page. Tous les e-mails envoyés par le REJCC passent par ici.
 */
class MailLayout
{
    /** Style des paragraphes du corps (à reprendre dans chaque e-mail). */
    public const P = 'margin:0 0 12px';

    /** Formule d'appel : « Bonjour Awa, » ou « Bonjour, » si le prénom manque. */
    public static function bonjour(?string $prenom): string
    {
        $prenom = trim((string) $prenom);

        return '<p style="'.self::P.'">Bonjour'.($prenom !== '' ? ' '.e($prenom) : '').',</p>';
    }

    public static function html(string $titre, string $corps, ?string $bouton = null, ?string $lien = null, ?string $piedDePage = null, bool $signature = true): string
    {
        $e = fn ($v) => e((string) $v);
        $base = rtrim((string) config('app.frontend_url'), '/');
        $btn = $bouton && $lien
            ? "<p style=\"margin:26px 0 6px\"><a href=\"{$e($lien)}\" style=\"display:inline-block;background:#031D59;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:999px\">{$e($bouton)}</a></p>"
            : '';
        $pied = $piedDePage ?? "Vous recevez cet e-mail en tant que membre du REJCC. Gérez vos e-mails depuis <a href=\"{$base}/espace-membre/profil?onglet=notifications\" style=\"color:#4F6FBF\">Paramètres → Notifications</a>.";
        $signe = $signature
            ? '<p style="margin:24px 0 0;color:#1B2433">Fraternellement,<br><strong style="color:#031D59">L\'équipe du REJCC</strong></p>'
            : '';
        $site = preg_replace('#^https?://#', '', $base);

        return <<<HTML
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#EEF2F8;font-family:Arial,Helvetica,sans-serif;color:#1B2433">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2F8;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border-radius:16px;overflow:hidden">
<tr><td style="background:#031D59;padding:18px 28px;border-bottom:3px solid #AC0100">
<table role="presentation" cellpadding="0" cellspacing="0"><tr>
<td style="padding-right:14px;vertical-align:middle"><img src="{$base}/brand/rejcc-mail-monogramme.png" width="34" height="52" alt="" style="display:block;border:0"></td>
<td style="vertical-align:middle"><span style="color:#ffffff;font-size:18px;font-weight:800;letter-spacing:2px">REJCC</span>
<span style="display:block;color:#8FA3D9;font-size:11px;margin-top:2px">Réseau Entrepreneurial des Jeunes Chrétiens Catholiques</span></td>
</tr></table>
</td></tr>
<tr><td style="padding:28px 28px 30px;font-size:14.5px;line-height:1.6">
<h1 style="margin:0 0 14px;font-size:19px;color:#031D59">{$e($titre)}</h1>
{$corps}
{$btn}
{$signe}
</td></tr>
<tr><td style="padding:16px 28px;background:#F6F8FB;font-size:11.5px;line-height:1.5;color:#7A8699">{$pied}<br><span style="color:#9AA6B8">REJCC · Réseau Entrepreneurial des Jeunes Chrétiens Catholiques · <a href="{$base}" style="color:#9AA6B8">{$site}</a></span></td></tr>
</table></td></tr></table></body></html>
HTML;
    }
}

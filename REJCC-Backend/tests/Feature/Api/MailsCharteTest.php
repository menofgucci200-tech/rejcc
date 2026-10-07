<?php

namespace Tests\Feature\Api;

use App\Mail\NouveauMessageContact;
use App\Models\Contact;
use App\Support\MailLayout;
use Tests\TestCase;

/** Tous les e-mails envoyés par le REJCC portent la charte du réseau (MailLayout). */
class MailsCharteTest extends TestCase
{
    public function test_chaque_e_mail_utilise_l_habillage_du_reseau(): void
    {
        foreach (glob(app_path('Mail/*.php')) as $fichier) {
            $code = file_get_contents($fichier);
            if (basename($fichier) === 'MessageCompte.php') {
                continue; // reçoit un contenu déjà habillé (vérifié ci-dessous)
            }
            $this->assertStringContainsString('MailLayout::html(', $code, basename($fichier).' doit utiliser MailLayout::html().');
        }

        // Chaque « new MessageCompte(...) » reçoit un contenu passé par MailLayout.
        foreach (array_merge(glob(app_path('Http/Controllers/Api/*.php')), glob(app_path('Support/*.php'))) as $fichier) {
            preg_match_all('/new MessageCompte\((.{0,200})/s', file_get_contents($fichier), $m);
            foreach ($m[1] as $appel) {
                $this->assertStringContainsString('MailLayout::html(', $appel, basename($fichier).' : MessageCompte sans MailLayout.');
            }
        }
    }

    public function test_l_habillage_porte_le_nom_les_couleurs_et_la_signature(): void
    {
        $html = MailLayout::html('Titre <test>', '<p>Corps</p>', 'Ouvrir', 'https://rejcc.site/x');

        $this->assertStringContainsString('Réseau Entrepreneurial des Jeunes Chrétiens Catholiques', $html);
        $this->assertStringContainsString('#031D59', $html);
        $this->assertStringContainsString('#AC0100', $html);
        $this->assertStringContainsString('/brand/rejcc-mail-monogramme.png', $html);
        $this->assertStringContainsString("L'équipe du REJCC", $html);
        $this->assertStringContainsString('Titre &lt;test&gt;', $html);
        $this->assertStringNotContainsString("L'équipe du REJCC", MailLayout::html('Interne', '', null, null, 'Interne', signature: false));
        $this->assertSame('<p style="margin:0 0 12px">Bonjour,</p>', MailLayout::bonjour(''));
    }

    public function test_le_message_de_contact_est_echappe_et_permet_de_repondre(): void
    {
        $mail = new NouveauMessageContact(new Contact(['nom' => 'Jean <script>', 'email' => 'jean@example.org', 'sujet' => 'Partenariat', 'message' => '<b>Bonjour</b>']));

        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Bonjour</b>', $html);
        $this->assertSame('jean@example.org', $mail->envelope()->replyTo[0]->address);
        $this->assertStringStartsWith('REJCC — ', $mail->envelope()->subject);
    }
}

import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { ComingSoon } from "@/components/sections/ComingSoon";

export const metadata: Metadata = {
  title: "Espace membre",
  description:
    "Connectez-vous à votre espace membre REJCC : tableau de bord, annuaire, messagerie et ressources.",
};

export default function ConnexionPage() {
  return (
    <>
      <PageHeader
        eyebrow="Espace membre"
        crumb="Connexion"
        title={
          <>
            Votre{" "}
            <span className="font-serif italic normal-case text-azure">espace</span>
          </>
        }
        subtitle="Accédez à votre tableau de bord, à l'annuaire des membres, à la messagerie et à toutes les ressources du réseau."
      />
      <ComingSoon
        intro="L'espace membre sécurisé est en cours de développement. Il sera accessible après votre adhésion."
        features={[
          "Tableau de bord personnalisé",
          "Annuaire & profils des membres",
          "Messagerie et notifications",
          "Documents, galerie & ressources",
        ]}
      />
    </>
  );
}

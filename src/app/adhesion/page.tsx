import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { HowToJoin } from "@/components/sections/HowToJoin";
import { ComingSoon } from "@/components/sections/ComingSoon";

export const metadata: Metadata = {
  title: "Adhésion",
  description:
    "Rejoignez le REJCC : adhésion 100 % en ligne, paiement par Mobile Money ou carte, accès immédiat à la communauté.",
};

export default function AdhesionPage() {
  return (
    <>
      <PageHeader
        eyebrow="Rejoindre le réseau"
        crumb="Adhésion"
        title={
          <>
            Devenez{" "}
            <span className="font-serif italic normal-case text-azure">membre</span>
          </>
        }
        subtitle="Intégrez une communauté de jeunes entrepreneurs catholiques et accédez à toutes ses ressources, partout en Côte d'Ivoire."
      />
      <HowToJoin />
      <ComingSoon
        intro="Le formulaire d'adhésion complet et le paiement sécurisé en ligne sont en cours d'intégration."
        features={[
          "Formulaire d'adhésion détaillé",
          "Paiement Mobile Money & carte bancaire",
          "Validation et reçu automatiques",
          "Accès immédiat à l'espace membre",
        ]}
      />
    </>
  );
}

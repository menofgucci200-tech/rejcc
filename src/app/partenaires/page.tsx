import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { ComingSoon } from "@/components/sections/ComingSoon";
import { CtaBand } from "@/components/sections/CtaBand";

export const metadata: Metadata = {
  title: "Partenaires",
  description:
    "Devenez partenaire du REJCC et soutenez une nouvelle génération d'entrepreneurs catholiques.",
};

export default function PartenairesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Ensemble, plus loin"
        crumb="Partenaires"
        title={
          <>
            Nos{" "}
            <span className="font-serif italic normal-case text-azure">partenaires</span>
          </>
        }
        subtitle="Entreprises, institutions et organisations qui soutiennent l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire."
      />
      <ComingSoon
        intro="L'espace partenaires, avec la présentation de nos soutiens et le formulaire pour nous rejoindre, est en cours de finalisation."
        features={[
          "Mur de logos des partenaires",
          "Présentation des partenariats",
          "Formulaire « Devenir partenaire »",
          "Espace presse & médias",
        ]}
      />
      <CtaBand />
    </>
  );
}

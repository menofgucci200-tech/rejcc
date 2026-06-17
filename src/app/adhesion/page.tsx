import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { AdhesionForm } from "@/components/forms/AdhesionForm";

export const metadata: Metadata = {
  title: "Adhésion",
  description:
    "Rejoignez le REJCC : adhésion 100 % en ligne, choix de la formule, paiement Mobile Money ou carte, accès à la communauté.",
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
        subtitle="Renseignez votre profil, choisissez votre moyen de paiement et finalisez votre adhésion en quelques minutes."
      />

      <section className="bg-cloud py-16 sm:py-24">
        <Container className="max-w-4xl">
          <AdhesionForm />
        </Container>
      </section>
    </>
  );
}

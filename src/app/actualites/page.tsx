import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { NewsExplorer } from "@/components/sections/NewsExplorer";

export const metadata: Metadata = {
  title: "Actualités",
  description:
    "Les dernières actualités du REJCC : réseau, mentorat, partenariats, événements et formations.",
};

export default function ActualitesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Le journal du réseau"
        crumb="Actualités"
        title={
          <>
            Nos{" "}
            <span className="font-serif italic normal-case text-azure">actualités</span>
          </>
        }
        subtitle="Suivez la vie du réseau, ses partenariats, ses événements et les réussites de ses membres."
      />
      <section className="bg-white py-16 sm:py-24">
        <Container>
          <NewsExplorer />
        </Container>
      </section>
    </>
  );
}

import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { News } from "@/components/sections/News";
import { ComingSoon } from "@/components/sections/ComingSoon";

export const metadata: Metadata = {
  title: "Actualités",
  description:
    "Les dernières actualités du REJCC : réseau, mentorat, partenariats et réussites des membres.",
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
      <News />
      <ComingSoon
        intro="Le blog complet, avec catégories et recherche, sera bientôt disponible ici."
        features={[
          "Blog moderne et complet",
          "Filtres par catégories",
          "Recherche d'articles",
          "Abonnement à la newsletter",
        ]}
      />
    </>
  );
}

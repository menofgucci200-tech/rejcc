import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Events } from "@/components/sections/Events";
import { ComingSoon } from "@/components/sections/ComingSoon";

export const metadata: Metadata = {
  title: "Événements",
  description:
    "L'agenda du REJCC : forums, ateliers, conférences, visites d'entreprises et galas.",
};

export default function EvenementsPage() {
  return (
    <>
      <PageHeader
        eyebrow="Agenda"
        crumb="Événements"
        title={
          <>
            Nos{" "}
            <span className="font-serif italic normal-case text-azure">événements</span>
          </>
        }
        subtitle="Participez à la vie du réseau : rencontres, formations et célébrations tout au long de l'année."
      />
      <Events />
      <ComingSoon
        intro="Le calendrier interactif et l'inscription en ligne aux événements arrivent très prochainement."
        features={[
          "Calendrier interactif filtrable",
          "Inscription en ligne sécurisée",
          "Galerie photos & vidéos",
          "Rappels et notifications",
        ]}
      />
    </>
  );
}

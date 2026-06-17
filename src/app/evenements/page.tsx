import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { EventsExplorer } from "@/components/sections/EventsExplorer";

export const metadata: Metadata = {
  title: "Événements",
  description:
    "Le calendrier du REJCC : forums, ateliers, conférences, visites d'entreprises et galas.",
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
        subtitle="Parcourez le calendrier, filtrez par type et inscrivez-vous aux rendez-vous du réseau."
      />
      <section className="bg-cloud py-16 sm:py-24">
        <Container>
          <EventsExplorer />
        </Container>
      </section>
    </>
  );
}

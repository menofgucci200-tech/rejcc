import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { CtaBand } from "@/components/sections/CtaBand";
import { activities } from "@/lib/content/activities";

export const metadata: Metadata = {
  title: "Nos activités",
  description:
    "Formations, mentorat, conférences, networking, visites d'entreprises, projets communautaires, événements et ateliers du REJCC.",
};

export default function ActivitesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Ce que nous faisons"
        crumb="Activités"
        title={
          <>
            Nos{" "}
            <span className="font-serif italic normal-case text-azure">activités</span>
          </>
        }
        subtitle="Un programme riche pour apprendre, entreprendre et grandir ensemble, tout au long de l'année."
      />

      <section className="bg-white py-24 sm:py-28">
        <Container>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {activities.map((a, i) => (
              <Reveal key={a.title} delay={(i % 4) * 0.07} className="h-full">
                <article className="group flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-7 transition-all duration-500 hover:-translate-y-1.5 hover:bg-brand">
                  <span className="inline-flex size-13 items-center justify-center rounded-2xl bg-white text-accent shadow-sm transition-transform duration-500 group-hover:scale-110">
                    <a.icon className="size-6" />
                  </span>
                  <h3 className="mt-5 font-display text-xl uppercase tracking-tight text-brand transition-colors duration-500 group-hover:text-white">
                    {a.title}
                  </h3>
                  <p className="mt-2 text-sm leading-relaxed text-ink/70 transition-colors duration-500 group-hover:text-white/80">
                    {a.text}
                  </p>
                </article>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      <CtaBand />
    </>
  );
}

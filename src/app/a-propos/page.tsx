import type { Metadata } from "next";
import { Target } from "lucide-react";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Values } from "@/components/sections/Values";
import { ComingSoon } from "@/components/sections/ComingSoon";
import { CtaBand } from "@/components/sections/CtaBand";
import { siteConfig } from "@/lib/content/site";

export const metadata: Metadata = {
  title: "À propos",
  description: siteConfig.about,
};

const objectives = [
  "Collaborer et co-créer entre membres",
  "Apprendre et monter en compétences",
  "Entreprendre et innover",
  "Créer de la richesse et de l'emploi",
  "Bâtir des entreprises durables",
  "Servir l'Église et la société",
];

export default function AProposPage() {
  return (
    <>
      <PageHeader
        eyebrow="Le réseau"
        crumb="À propos"
        title={
          <>
            À propos du{" "}
            <span className="font-serif italic normal-case text-azure">REJCC</span>
          </>
        }
        subtitle={siteConfig.about}
      />

      <section className="bg-white py-24 sm:py-28">
        <Container className="grid items-start gap-14 lg:grid-cols-2 lg:gap-20">
          <Reveal>
            <SectionHeading
              align="left"
              eyebrow="Notre histoire"
              title="Né d'une vision partagée"
              subtitle="Le REJCC est né de la volonté de jeunes entrepreneurs catholiques de Côte d'Ivoire de conjuguer leur foi, leur ambition et leur sens du service pour bâtir, ensemble, une nouvelle génération d'entreprises à impact."
            />
            <p className="mt-6 leading-relaxed text-ink/75">
              {siteConfig.positioning}
            </p>
          </Reveal>

          <div className="flex flex-col gap-6">
            <Reveal>
              <article className="rounded-3xl border border-brand/10 bg-cloud p-8">
                <h3 className="font-display text-2xl uppercase tracking-tight text-brand">
                  Notre mission
                </h3>
                <p className="mt-3 leading-relaxed text-ink/75">{siteConfig.mission}</p>
              </article>
            </Reveal>
            <Reveal delay={0.1}>
              <article className="rounded-3xl border border-brand/10 bg-brand p-8 text-white">
                <h3 className="font-display text-2xl uppercase tracking-tight">
                  Notre vision
                </h3>
                <p className="mt-3 leading-relaxed text-white/80">{siteConfig.vision}</p>
              </article>
            </Reveal>
          </div>
        </Container>

        <Container className="mt-20">
          <Reveal>
            <SectionHeading
              align="left"
              eyebrow="Nos objectifs"
              title="Ce que nous poursuivons"
            />
          </Reveal>
          <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {objectives.map((o, i) => (
              <Reveal key={o} delay={(i % 3) * 0.08}>
                <div className="flex items-center gap-4 rounded-2xl border border-brand/10 bg-white p-5">
                  <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/5 text-accent">
                    <Target className="size-5" />
                  </span>
                  <span className="font-medium text-brand">{o}</span>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      <Values />

      <ComingSoon
        intro="Le bureau exécutif, l'organigramme et la gouvernance du réseau seront prochainement présentés sur cette page."
        features={[
          "Présentation du bureau exécutif",
          "Organigramme du réseau",
          "Statuts et gouvernance",
          "Rapport d'activités annuel",
        ]}
      />

      <CtaBand />
    </>
  );
}

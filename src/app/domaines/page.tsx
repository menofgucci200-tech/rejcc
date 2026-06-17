import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { CtaBand } from "@/components/sections/CtaBand";
import { sectors, totalDomains } from "@/lib/content/domains";

export const metadata: Metadata = {
  title: "Domaines d'activité",
  description: `Les ${totalDomains} domaines d'activité fédérés par le REJCC, de l'agriculture à l'intelligence artificielle.`,
};

export default function DomainesPage() {
  return (
    <>
      <PageHeader
        eyebrow={`${totalDomains} domaines · 9 pôles`}
        crumb="Domaines"
        title={
          <>
            Domaines d&apos;
            <span className="font-serif italic normal-case text-azure">activité</span>
          </>
        }
        subtitle="Le réseau rassemble des entrepreneurs de tous les secteurs. Trouvez le vôtre et connectez-vous aux bonnes personnes."
      />

      <section className="bg-white py-24 sm:py-28">
        <Container>
          <div className="grid gap-6 lg:grid-cols-2">
            {sectors.map((s, i) => (
              <Reveal key={s.title} delay={(i % 2) * 0.08} className="h-full">
                <article className="group flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-8 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                  <div className="flex items-center gap-4">
                    <span className="inline-flex size-14 items-center justify-center rounded-2xl bg-brand text-white transition-colors duration-500 group-hover:bg-accent">
                      <s.icon className="size-6" />
                    </span>
                    <div>
                      <h2 className="text-xl font-bold text-brand">{s.title}</h2>
                      <p className="text-sm text-ink/60">{s.blurb}</p>
                    </div>
                  </div>
                  <div className="mt-6 flex flex-wrap gap-2">
                    {s.items.map((it) => (
                      <span
                        key={it}
                        className="rounded-full border border-brand/10 bg-white px-3.5 py-1.5 text-sm font-medium text-brand transition-colors hover:border-accent/40 hover:text-accent"
                      >
                        {it}
                      </span>
                    ))}
                  </div>
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

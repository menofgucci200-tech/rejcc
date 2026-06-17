import { ArrowUpRight } from "lucide-react";
import Link from "next/link";
import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";
import { sectors, totalDomains } from "@/lib/content/domains";

export function DomainsPreview() {
  return (
    <section className="relative overflow-hidden bg-brand py-24 sm:py-32">
      <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.25] [mask-image:radial-gradient(ellipse_at_center,black,transparent_80%)]" />
      <div className="pointer-events-none absolute -right-[5%] top-1/4 size-[40vmax] rounded-full bg-azure/15 blur-[130px]" />

      <Container className="relative">
        <div className="flex flex-col items-end justify-between gap-8 md:flex-row">
          <SectionHeading
            align="left"
            tone="dark"
            eyebrow="Nos domaines d'activité"
            title={
              <>
                {totalDomains} domaines,
                <br />
                un seul réseau
              </>
            }
            subtitle="De l'agriculture à l'intelligence artificielle, le REJCC fédère les talents de tous les secteurs de l'économie ivoirienne."
            className="max-w-2xl"
          />
          <Button href="/domaines" variant="white" withArrow className="shrink-0">
            Explorer les domaines
          </Button>
        </div>

        <div className="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {sectors.map((s, i) => (
            <Reveal key={s.title} delay={(i % 3) * 0.07}>
              <Link
                href="/domaines"
                className="group relative block h-full overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] p-7 transition-all duration-500 hover:border-white/25 hover:bg-white/[0.08]"
              >
                <div className="flex items-start justify-between">
                  <span className="inline-flex size-13 items-center justify-center rounded-2xl bg-white/10 text-white transition-all duration-500 group-hover:bg-accent">
                    <s.icon className="size-6" />
                  </span>
                  <ArrowUpRight className="size-5 text-white/30 transition-all duration-500 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-white" />
                </div>

                <h3 className="mt-5 text-lg font-bold text-white">{s.title}</h3>
                <p className="mt-1.5 text-sm text-white/55">{s.blurb}</p>

                {/* Sous-domaines révélés au survol */}
                <div className="grid grid-rows-[0fr] opacity-0 transition-all duration-500 group-hover:grid-rows-[1fr] group-hover:opacity-100">
                  <div className="overflow-hidden">
                    <div className="mt-4 flex flex-wrap gap-1.5 pt-3">
                      {s.items.map((it) => (
                        <span
                          key={it}
                          className="rounded-full border border-white/15 bg-white/5 px-2.5 py-1 text-xs text-white/75"
                        >
                          {it}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>

                <span className="mt-4 inline-block text-xs font-semibold uppercase tracking-[0.14em] text-white/40">
                  {s.items.length} domaines
                </span>
              </Link>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

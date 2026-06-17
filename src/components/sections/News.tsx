import { Clock, ArrowUpRight } from "lucide-react";
import Link from "next/link";
import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";
import { LogoMark } from "@/components/ui/LogoMark";
import { articles } from "@/lib/content/news";

export function News() {
  return (
    <section className="bg-white py-24 sm:py-32">
      <Container>
        <div className="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
          <SectionHeading
            align="left"
            eyebrow="Actualités"
            title="Les dernières nouvelles"
            subtitle="Suivez la vie du réseau, ses partenariats et ses réussites."
            className="max-w-2xl"
          />
          <Button href="/actualites" variant="outline" withArrow className="shrink-0">
            Toutes les actualités
          </Button>
        </div>

        <div className="mt-14 grid gap-6 lg:grid-cols-3">
          {articles.slice(0, 3).map((a, i) => (
            <Reveal key={a.slug} delay={i * 0.1} className="h-full">
              <Link
                href={`/actualites/${a.slug}`}
                className="group flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-white transition-all duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]"
              >
                {/* Média placeholder (à remplacer par une vraie photo) */}
                <div className="relative aspect-[16/10] overflow-hidden bg-brand">
                  <div className="absolute inset-0 bg-grid opacity-20" />
                  <div className="absolute inset-0 bg-linear-to-br from-brand via-brand to-brand-900" />
                  <div className="absolute -right-6 -top-6 size-32 rounded-full bg-azure/20 blur-2xl" />
                  <div className="absolute inset-0 flex items-center justify-center opacity-90 transition-transform duration-700 group-hover:scale-105">
                    <LogoMark kind="mono-white" className="h-16 opacity-80" />
                  </div>
                  <span className="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand">
                    {a.category}
                  </span>
                </div>

                <div className="flex flex-1 flex-col p-6">
                  <h3 className="text-lg font-bold leading-snug text-brand transition-colors group-hover:text-accent">
                    {a.title}
                  </h3>
                  <p className="mt-2 flex-1 text-pretty text-sm leading-relaxed text-ink/65">
                    {a.excerpt}
                  </p>
                  <div className="mt-5 flex items-center justify-between border-t border-brand/10 pt-4 text-xs text-ink/55">
                    <span>{a.date}</span>
                    <span className="flex items-center gap-1.5">
                      <Clock className="size-3.5" /> {a.readingTime}
                      <ArrowUpRight className="ml-1 size-4 text-accent transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                    </span>
                  </div>
                </div>
              </Link>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

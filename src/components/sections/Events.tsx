import { MapPin, ArrowRight } from "lucide-react";
import Link from "next/link";
import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";
import { events } from "@/lib/content/events";

export function Events() {
  return (
    <section className="bg-cloud py-24 sm:py-32">
      <Container>
        <div className="flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
          <SectionHeading
            align="left"
            eyebrow="Agenda"
            title="Prochains événements"
            subtitle="Forums, ateliers, visites et galas : la vie du réseau tout au long de l'année."
            className="max-w-2xl"
          />
          <Button href="/evenements" variant="outline" withArrow className="shrink-0">
            Voir l&apos;agenda complet
          </Button>
        </div>

        <div className="mt-14 grid gap-4 sm:grid-cols-2">
          {events.slice(0, 4).map((e, i) => (
            <Reveal key={e.slug} delay={(i % 2) * 0.08}>
              <Link
                href={`/evenements/${e.slug}`}
                className="group flex items-stretch gap-5 overflow-hidden rounded-3xl border border-brand/10 bg-white p-5 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-35px_rgba(3,29,89,0.4)]"
              >
                <div className="flex w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-brand py-4 text-white">
                  <span className="font-display text-3xl leading-none">{e.day}</span>
                  <span className="mt-1 text-xs uppercase tracking-wider text-white/70">
                    {e.month}
                  </span>
                </div>
                <div className="flex min-w-0 flex-1 flex-col justify-center">
                  <span className="inline-flex w-fit rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">
                    {e.type}
                  </span>
                  <h3 className="mt-2 truncate text-lg font-bold text-brand">
                    {e.title}
                  </h3>
                  <p className="mt-1 line-clamp-1 text-sm text-ink/65">{e.excerpt}</p>
                  <p className="mt-2 flex items-center gap-1.5 text-xs text-ink/55">
                    <MapPin className="size-3.5" /> {e.location} · {e.year}
                  </p>
                </div>
                <ArrowRight className="size-5 self-center text-brand/20 transition-all duration-500 group-hover:translate-x-1 group-hover:text-accent" />
              </Link>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

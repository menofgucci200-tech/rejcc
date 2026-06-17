import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, MapPin, Clock, Calendar, Tag } from "lucide-react";
import { Container } from "@/components/ui/Container";
import { Button } from "@/components/ui/Button";
import { events, getEvent } from "@/lib/content/events";

export function generateStaticParams() {
  return events.map((e) => ({ slug: e.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const ev = getEvent(slug);
  if (!ev) return { title: "Événement introuvable" };
  return { title: ev.title, description: ev.excerpt };
}

export default async function EventPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const ev = getEvent(slug);
  if (!ev) notFound();

  const infos = [
    { icon: Calendar, label: "Date", value: `${ev.day} ${ev.month} ${ev.year}` },
    { icon: Clock, label: "Horaire", value: ev.time },
    { icon: MapPin, label: "Lieu", value: ev.location },
    { icon: Tag, label: "Type", value: ev.type },
  ];

  return (
    <article>
      <header className="relative overflow-hidden bg-brand pb-14 pt-36 sm:pt-44">
        <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.22] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]" />
        <div className="pointer-events-none absolute -right-[8%] -top-[10%] size-[40vmax] rounded-full bg-azure/20 blur-[120px]" />
        <Container className="relative max-w-3xl">
          <Link
            href="/evenements"
            className="inline-flex items-center gap-2 text-sm text-white/60 transition-colors hover:text-white"
          >
            <ArrowLeft className="size-4" /> Tous les événements
          </Link>
          <span className="mt-6 inline-block rounded-full bg-accent px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">
            {ev.type}
          </span>
          <h1 className="mt-4 font-display uppercase leading-[0.95] tracking-tight text-white text-[clamp(1.9rem,5vw,3.25rem)]">
            {ev.title}
          </h1>
        </Container>
      </header>

      <div className="bg-white py-16 sm:py-20">
        <Container className="grid max-w-5xl gap-10 lg:grid-cols-[1.6fr_1fr]">
          <div>
            <div className="space-y-5 text-lg leading-relaxed text-ink/80">
              {ev.description.map((p, i) => (
                <p key={i}>{p}</p>
              ))}
            </div>
            <div className="mt-10">
              <Button href="/adhesion" variant="primary" withArrow>
                S&apos;inscrire à l&apos;événement
              </Button>
              <p className="mt-3 text-xs text-ink/55">
                L&apos;inscription en ligne dédiée sera bientôt disponible. En
                attendant, adhérez ou contactez-nous pour réserver votre place.
              </p>
            </div>
          </div>

          <aside className="h-fit rounded-3xl border border-brand/10 bg-cloud p-7">
            <h2 className="font-display text-lg uppercase tracking-tight text-brand">
              Informations
            </h2>
            <ul className="mt-5 space-y-4">
              {infos.map((info) => (
                <li key={info.label} className="flex items-start gap-3">
                  <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand text-white">
                    <info.icon className="size-4.5" />
                  </span>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.14em] text-ink/50">
                      {info.label}
                    </p>
                    <p className="mt-0.5 font-medium text-brand">{info.value}</p>
                  </div>
                </li>
              ))}
            </ul>
          </aside>
        </Container>
      </div>
    </article>
  );
}

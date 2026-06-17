import { Compass, Telescope } from "lucide-react";
import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";
import { siteConfig } from "@/lib/content/site";

const pillars = [
  {
    icon: Compass,
    label: "Notre mission",
    text: siteConfig.mission,
  },
  {
    icon: Telescope,
    label: "Notre vision",
    text: siteConfig.vision,
  },
];

export function About() {
  return (
    <section id="a-propos" className="relative bg-white py-24 sm:py-32">
      <Container className="grid items-start gap-14 lg:grid-cols-[1fr_1fr] lg:gap-20">
        <div className="lg:sticky lg:top-28">
          <SectionHeading
            align="left"
            eyebrow="Qui sommes-nous"
            title={
              <>
                Une communauté qui{" "}
                <span className="text-gradient">entreprend</span>, unie par la foi
              </>
            }
            subtitle={siteConfig.about}
          />
          <figure className="mt-8 border-l-2 border-accent pl-6">
            <blockquote className="font-serif text-xl italic leading-relaxed text-brand">
              « {siteConfig.positioning} »
            </blockquote>
          </figure>
          <div className="mt-9">
            <Button href="/a-propos" variant="outline" withArrow>
              Découvrir notre histoire
            </Button>
          </div>
        </div>

        <div className="flex flex-col gap-6">
          {pillars.map((p, i) => (
            <Reveal key={p.label} delay={i * 0.1}>
              <article className="group relative overflow-hidden rounded-3xl border border-brand/10 bg-cloud p-8 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_30px_60px_-30px_rgba(3,29,89,0.35)]">
                <div className="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-azure/10 blur-2xl transition-opacity duration-500 group-hover:opacity-100" />
                <span className="inline-flex size-14 items-center justify-center rounded-2xl bg-brand text-white shadow-lg">
                  <p.icon className="size-6" />
                </span>
                <h3 className="mt-6 font-display text-2xl uppercase tracking-tight text-brand">
                  {p.label}
                </h3>
                <p className="mt-3 text-pretty leading-relaxed text-ink/75">
                  {p.text}
                </p>
              </article>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

import { Quote } from "lucide-react";
import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { testimonials } from "@/lib/content/home";

export function Testimonials() {
  return (
    <section className="bg-white py-24 sm:py-32">
      <Container>
        <SectionHeading
          eyebrow="Témoignages"
          title="Des membres qui réussissent"
          subtitle="Ils ont rejoint le réseau et font grandir leurs projets, ensemble."
        />

        <div className="mt-16 grid gap-6 lg:grid-cols-3">
          {testimonials.map((t, i) => (
            <Reveal key={t.name} delay={i * 0.1} className="h-full">
              <figure className="group relative flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-8 transition-all duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                <Quote className="size-9 text-accent/30 transition-colors duration-500 group-hover:text-accent/60" />
                <blockquote className="mt-5 flex-1 font-serif text-lg italic leading-relaxed text-brand">
                  {t.quote}
                </blockquote>
                <figcaption className="mt-7 flex items-center gap-3.5 border-t border-brand/10 pt-6">
                  <span className="inline-flex size-12 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">
                    {t.initials}
                  </span>
                  <div>
                    <p className="font-semibold text-brand">{t.name}</p>
                    <p className="text-sm text-ink/60">{t.role}</p>
                  </div>
                </figcaption>
              </figure>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

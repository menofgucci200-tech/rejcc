import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";
import { steps } from "@/lib/content/home";
import { ctaPrimary } from "@/lib/content/site";

export function HowToJoin() {
  return (
    <section className="bg-cloud py-24 sm:py-32">
      <Container>
        <SectionHeading
          eyebrow="Comment adhérer"
          title="Rejoignez-nous en 4 étapes"
          subtitle="Un parcours simple et 100 % en ligne pour intégrer le réseau et accéder à tous ses avantages."
        />

        <div className="relative mt-16">
          {/* Ligne de liaison (desktop) */}
          <div className="absolute left-0 right-0 top-9 hidden h-px bg-linear-to-r from-transparent via-brand/20 to-transparent lg:block" />

          <ol className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
            {steps.map((s, i) => (
              <Reveal key={s.title} delay={i * 0.1} className="h-full">
                <li className="relative flex h-full flex-col">
                  <div className="flex items-center gap-4 lg:flex-col lg:items-start">
                    <span className="relative z-10 inline-flex size-18 shrink-0 items-center justify-center rounded-2xl bg-white text-brand shadow-[0_18px_40px_-20px_rgba(3,29,89,0.5)] ring-1 ring-brand/10">
                      <s.icon className="size-7" />
                      <span className="absolute -right-2 -top-2 inline-flex size-7 items-center justify-center rounded-full bg-accent text-xs font-bold text-white">
                        {i + 1}
                      </span>
                    </span>
                  </div>
                  <h3 className="mt-6 text-lg font-bold text-brand">{s.title}</h3>
                  <p className="mt-2 text-pretty text-sm leading-relaxed text-ink/70">
                    {s.text}
                  </p>
                </li>
              </Reveal>
            ))}
          </ol>
        </div>

        <div className="mt-14 flex justify-center">
          <Button href={ctaPrimary.href} size="lg" variant="primary" withArrow>
            Commencer mon adhésion
          </Button>
        </div>
      </Container>
    </section>
  );
}

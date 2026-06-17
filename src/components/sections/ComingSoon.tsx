import { Check, Sparkles } from "lucide-react";
import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";

export function ComingSoon({
  intro,
  features,
}: {
  intro: string;
  features: string[];
}) {
  return (
    <section className="bg-white py-24 sm:py-28">
      <Container>
        <Reveal className="mx-auto max-w-3xl">
          <div className="relative overflow-hidden rounded-3xl border border-brand/10 bg-cloud p-8 sm:p-12">
            <div className="pointer-events-none absolute -right-12 -top-12 size-48 rounded-full bg-azure/10 blur-3xl" />
            <span className="inline-flex items-center gap-2 rounded-full bg-brand px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-white">
              <Sparkles className="size-3.5" /> Module en cours de déploiement
            </span>
            <p className="mt-6 text-pretty text-lg leading-relaxed text-ink/75">
              {intro}
            </p>

            <ul className="mt-8 grid gap-3 sm:grid-cols-2">
              {features.map((f) => (
                <li key={f} className="flex items-start gap-3 text-ink/80">
                  <span className="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent">
                    <Check className="size-3.5" />
                  </span>
                  {f}
                </li>
              ))}
            </ul>

            <div className="mt-10 flex flex-wrap gap-3">
              <Button href="/adhesion" variant="primary" withArrow>
                Adhérer au réseau
              </Button>
              <Button href="/contact" variant="outline">
                Nous contacter
              </Button>
            </div>
          </div>
        </Reveal>
      </Container>
    </section>
  );
}

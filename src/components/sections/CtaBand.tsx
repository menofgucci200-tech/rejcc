import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { Button } from "@/components/ui/Button";
import { siteConfig, ctaPrimary } from "@/lib/content/site";

export function CtaBand() {
  return (
    <section className="relative overflow-hidden bg-brand py-24 sm:py-28">
      <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.2] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]" />
      <div className="pointer-events-none absolute -left-[10%] top-0 size-[40vmax] rounded-full bg-azure/20 blur-[120px]" />
      <div className="pointer-events-none absolute -right-[10%] bottom-0 size-[35vmax] rounded-full bg-accent/20 blur-[120px]" />

      <Container className="relative">
        <Reveal className="mx-auto flex max-w-3xl flex-col items-center text-center">
          <span className="font-serif text-lg italic text-azure">
            {siteConfig.slogan}
          </span>
          <h2 className="mt-4 font-display uppercase leading-[0.95] tracking-tight text-white text-[clamp(2.25rem,6vw,4.25rem)]">
            Prêt à écrire votre réussite&nbsp;?
          </h2>
          <p className="mt-6 max-w-xl text-pretty text-lg leading-relaxed text-white/75">
            Rejoignez une communauté de jeunes entrepreneurs catholiques
            déterminés à grandir, collaborer et réussir — ensemble.
          </p>
          <div className="mt-10 flex flex-wrap items-center justify-center gap-3">
            <Button href={ctaPrimary.href} size="lg" variant="primary" withArrow>
              Adhérer maintenant
            </Button>
            <Button
              href="/partenaires"
              size="lg"
              variant="ghost"
              className="text-white hover:bg-white/10"
            >
              Devenir partenaire
            </Button>
          </div>
        </Reveal>
      </Container>
    </section>
  );
}

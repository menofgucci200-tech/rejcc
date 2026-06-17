import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { benefits } from "@/lib/content/home";

export function WhyJoin() {
  return (
    <section className="relative bg-cloud py-24 sm:py-32">
      <div className="pointer-events-none absolute inset-0 bg-grid opacity-40 [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]" />
      <Container className="relative">
        <SectionHeading
          eyebrow="Pourquoi nous rejoindre"
          title={
            <>
              Tout ce dont vous avez besoin pour{" "}
              <span className="text-gradient">réussir</span>
            </>
          }
          subtitle="Le REJCC met à votre disposition un environnement complet pour faire grandir vos projets et vos compétences."
        />

        <div className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {benefits.map((b, i) => (
            <Reveal key={b.title} delay={(i % 3) * 0.08}>
              <article className="group relative h-full overflow-hidden rounded-3xl border border-brand/10 bg-white p-8 transition-all duration-500 hover:-translate-y-1.5 hover:border-brand/20 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]">
                <span className="inline-flex size-14 items-center justify-center rounded-2xl bg-brand/5 text-brand transition-all duration-500 group-hover:bg-brand group-hover:text-white">
                  <b.icon className="size-6" />
                </span>
                <h3 className="mt-6 text-xl font-bold tracking-tight text-brand">
                  {b.title}
                </h3>
                <p className="mt-2.5 text-pretty leading-relaxed text-ink/70">
                  {b.text}
                </p>
                <span className="absolute bottom-0 left-0 h-1 w-0 bg-accent transition-all duration-500 group-hover:w-full" />
              </article>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

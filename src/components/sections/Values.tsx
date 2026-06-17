import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { values } from "@/lib/content/home";

export function Values() {
  return (
    <section className="bg-white py-24 sm:py-32">
      <Container>
        <SectionHeading
          eyebrow="Nos valeurs"
          title="Ce qui nous fait avancer"
          subtitle="Cinq valeurs cardinales guident chaque action du réseau et de ses membres."
        />

        <div className="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
          {values.map((v, i) => (
            <Reveal key={v.title} delay={i * 0.07} className="h-full">
              <article
                className={`group relative flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-cloud p-7 transition-all duration-500 hover:-translate-y-1.5 hover:bg-brand ${
                  i === 0 ? "sm:col-span-2 lg:col-span-1" : ""
                }`}
              >
                <span className="inline-flex size-12 items-center justify-center rounded-xl bg-white text-accent shadow-sm transition-transform duration-500 group-hover:scale-110">
                  <v.icon className="size-5.5" />
                </span>
                <h3 className="mt-5 font-display text-xl uppercase tracking-tight text-brand transition-colors duration-500 group-hover:text-white">
                  {v.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-ink/70 transition-colors duration-500 group-hover:text-white/80">
                  {v.text}
                </p>
              </article>
            </Reveal>
          ))}
        </div>
      </Container>
    </section>
  );
}

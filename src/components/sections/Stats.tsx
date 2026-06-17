import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { Counter } from "@/components/ui/Counter";
import { stats } from "@/lib/content/home";

export function Stats() {
  return (
    <section className="relative overflow-hidden bg-brand py-20">
      <div className="pointer-events-none absolute inset-0 bg-dots opacity-[0.07]" />
      <div className="pointer-events-none absolute left-1/2 top-0 h-px w-2/3 -translate-x-1/2 bg-gradient-to-r from-transparent via-white/30 to-transparent" />
      <Container>
        <dl className="grid grid-cols-2 gap-y-12 lg:grid-cols-4">
          {stats.map((s, i) => (
            <Reveal key={s.label} delay={i * 0.08} className="text-center">
              <dt className="font-display text-[clamp(2.75rem,6vw,4.5rem)] leading-none text-white">
                <Counter value={s.value} suffix={s.suffix} />
              </dt>
              <dd className="mt-3 text-sm font-medium uppercase tracking-[0.12em] text-white/60">
                {s.label}
              </dd>
            </Reveal>
          ))}
        </dl>
      </Container>
    </section>
  );
}

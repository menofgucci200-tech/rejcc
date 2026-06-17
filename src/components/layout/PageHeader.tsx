import Link from "next/link";
import { ChevronRight } from "lucide-react";
import { Container } from "@/components/ui/Container";
import { Eyebrow } from "@/components/ui/Eyebrow";

export function PageHeader({
  eyebrow,
  title,
  subtitle,
  crumb,
}: {
  eyebrow?: string;
  title: React.ReactNode;
  subtitle?: React.ReactNode;
  crumb: string;
}) {
  return (
    <header className="relative overflow-hidden bg-brand pb-16 pt-36 sm:pb-20 sm:pt-44">
      <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.25] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]" />
      <div className="pointer-events-none absolute -right-[8%] -top-[10%] size-[40vmax] rounded-full bg-azure/20 blur-[120px]" />
      <div className="pointer-events-none absolute -left-[8%] bottom-[-20%] size-[30vmax] rounded-full bg-accent/15 blur-[120px]" />

      <Container className="relative">
        <nav className="flex items-center gap-1.5 text-sm text-white/55">
          <Link href="/" className="transition-colors hover:text-white">
            Accueil
          </Link>
          <ChevronRight className="size-3.5" />
          <span className="text-white/80">{crumb}</span>
        </nav>

        <div className="mt-7 max-w-3xl">
          {eyebrow && <Eyebrow tone="dark">{eyebrow}</Eyebrow>}
          <h1 className="mt-5 font-display uppercase leading-[0.92] tracking-[-0.01em] text-white text-[clamp(2.5rem,7vw,5rem)]">
            {title}
          </h1>
          {subtitle && (
            <p className="mt-6 max-w-2xl text-pretty text-lg leading-relaxed text-white/75">
              {subtitle}
            </p>
          )}
        </div>
      </Container>
    </header>
  );
}

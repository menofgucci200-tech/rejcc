"use client";

import { useRef } from "react";
import { motion, useScroll, useTransform } from "framer-motion";
import { Network, GraduationCap, Rocket, ArrowDown } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Eyebrow } from "@/components/ui/Eyebrow";
import { LogoMark } from "@/components/ui/LogoMark";
import { ctaPrimary } from "@/lib/content/site";

const floatingCards = [
  { icon: Network, label: "Networking", sub: "Connexions ciblées", x: "-12%", y: "8%", delay: 0 },
  { icon: GraduationCap, label: "Mentorat", sub: "Experts confirmés", x: "76%", y: "-4%", delay: 0.4 },
  { icon: Rocket, label: "Accélération", sub: "Projets à impact", x: "80%", y: "70%", delay: 0.8 },
];

export function Hero() {
  const ref = useRef<HTMLElement>(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start start", "end start"],
  });
  const yText = useTransform(scrollYProgress, [0, 1], [0, 120]);
  const yVisual = useTransform(scrollYProgress, [0, 1], [0, -60]);
  const opacity = useTransform(scrollYProgress, [0, 0.8], [1, 0]);

  return (
    <section
      ref={ref}
      className="relative flex min-h-[100svh] items-center overflow-hidden bg-brand pb-20 pt-32 lg:pt-28"
    >
      {/* Background layers */}
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute inset-0 bg-grid opacity-[0.5] [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]" />
        <motion.div
          className="absolute -left-[10%] top-[-10%] size-[55vmax] rounded-full bg-azure/25 blur-[120px]"
          style={{ animationDuration: "20s" }}
          animate={{ scale: [1, 1.12, 1], opacity: [0.6, 0.9, 0.6] }}
          transition={{ duration: 16, repeat: Infinity, ease: "easeInOut" }}
        />
        <motion.div
          className="absolute -right-[10%] bottom-[-15%] size-[50vmax] rounded-full bg-accent/20 blur-[120px]"
          animate={{ scale: [1.1, 1, 1.1], opacity: [0.5, 0.75, 0.5] }}
          transition={{ duration: 18, repeat: Infinity, ease: "easeInOut" }}
        />
        <div className="absolute inset-0 bg-linear-to-b from-brand-900/40 via-transparent to-brand" />
      </div>

      <div className="relative mx-auto grid w-full max-w-7xl items-center gap-16 container-px lg:grid-cols-[1.05fr_0.95fr]">
        {/* Left — copy */}
        <motion.div style={{ y: yText, opacity }}>
          <motion.div
            initial={{ opacity: 0, y: 18 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
          >
            <Eyebrow tone="dark">Réseau entrepreneurial catholique · Côte d&apos;Ivoire</Eyebrow>
          </motion.div>

          <h1 className="mt-6 font-display uppercase leading-[0.9] tracking-[-0.01em] text-white text-[clamp(2.75rem,8vw,5.75rem)]">
            <motion.span
              className="block"
              initial={{ opacity: 0, y: 24 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.7, delay: 0.08, ease: [0.22, 1, 0.36, 1] }}
            >
              Ensemble
            </motion.span>
            <motion.span
              className="block"
              initial={{ opacity: 0, y: 24 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.7, delay: 0.16, ease: [0.22, 1, 0.36, 1] }}
            >
              pour{" "}
              <span className="font-serif italic normal-case text-azure">
                l&apos;excellence
              </span>
            </motion.span>
          </h1>

          <motion.p
            className="mt-7 max-w-xl text-pretty text-lg leading-relaxed text-white/75"
            initial={{ opacity: 0, y: 18 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.7, delay: 0.28 }}
          >
            Le réseau de référence des jeunes entrepreneurs et porteurs de projets
            catholiques. Collaborer, innover et bâtir des entreprises à impact
            durable — au service de l&apos;Église et de la société.
          </motion.p>

          <motion.div
            className="mt-9 flex flex-wrap items-center gap-3"
            initial={{ opacity: 0, y: 18 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.7, delay: 0.38 }}
          >
            <Button href={ctaPrimary.href} size="lg" variant="primary" withArrow>
              Rejoindre le réseau
            </Button>
            <Button href="/a-propos" size="lg" variant="ghost" className="text-white hover:bg-white/10">
              Découvrir le REJCC
            </Button>
          </motion.div>

          <motion.div
            className="mt-10 flex items-center gap-5 text-sm text-white/60"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.8, delay: 0.5 }}
          >
            <div className="flex -space-x-2">
              {["JK", "AB", "GA", "MT"].map((i) => (
                <span
                  key={i}
                  className="inline-flex size-9 items-center justify-center rounded-full border-2 border-brand bg-azure/30 text-[0.7rem] font-bold text-white"
                >
                  {i}
                </span>
              ))}
            </div>
            <span>
              <span className="font-semibold text-white">350+ membres</span> déjà
              engagés dans 33 domaines.
            </span>
          </motion.div>
        </motion.div>

        {/* Right — brand visual */}
        <motion.div
          style={{ y: yVisual }}
          className="relative mx-auto hidden aspect-square w-full max-w-md items-center justify-center lg:flex"
        >
          {/* rotating rings (decoratif — pas le logo) */}
          <motion.div
            aria-hidden
            className="absolute inset-0 rounded-full border border-white/10"
            animate={{ rotate: 360 }}
            transition={{ duration: 60, repeat: Infinity, ease: "linear" }}
          >
            <span className="absolute left-1/2 top-0 size-2 -translate-x-1/2 rounded-full bg-accent" />
          </motion.div>
          <motion.div
            aria-hidden
            className="absolute inset-[12%] rounded-full border border-dashed border-white/15"
            animate={{ rotate: -360 }}
            transition={{ duration: 45, repeat: Infinity, ease: "linear" }}
          />
          <div className="absolute inset-[22%] rounded-full bg-white/[0.03] backdrop-blur-sm" />
          <div className="absolute inset-0 rounded-full bg-azure/20 blur-3xl" />

          {/* Monogram */}
          <motion.div
            initial={{ opacity: 0, scale: 0.85 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.9, delay: 0.3, ease: [0.22, 1, 0.36, 1] }}
            className="relative"
          >
            <LogoMark kind="mono-white" priority className="h-44 drop-shadow-[0_20px_40px_rgba(0,0,0,0.3)]" />
          </motion.div>

          {/* Floating cards */}
          {floatingCards.map(({ icon: Icon, label, sub, x, y, delay }) => (
            <motion.div
              key={label}
              className="absolute"
              style={{ left: x, top: y }}
              initial={{ opacity: 0, scale: 0.8 }}
              animate={{ opacity: 1, scale: 1, y: [0, -12, 0] }}
              transition={{
                opacity: { duration: 0.6, delay: 0.6 + delay },
                scale: { duration: 0.6, delay: 0.6 + delay },
                y: { duration: 5, repeat: Infinity, ease: "easeInOut", delay },
              }}
            >
              <div className="glass-dark flex items-center gap-3 rounded-2xl px-4 py-3 shadow-xl">
                <span className="inline-flex size-9 items-center justify-center rounded-xl bg-white/10 text-white">
                  <Icon className="size-4.5" />
                </span>
                <div className="leading-tight">
                  <p className="text-sm font-semibold text-white">{label}</p>
                  <p className="text-xs text-white/60">{sub}</p>
                </div>
              </div>
            </motion.div>
          ))}
        </motion.div>
      </div>

      {/* Scroll cue */}
      <motion.div
        className="absolute bottom-6 left-1/2 flex -translate-x-1/2 flex-col items-center gap-2 text-white/50"
        animate={{ y: [0, 8, 0] }}
        transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }}
      >
        <span className="text-[0.7rem] uppercase tracking-[0.2em]">Explorer</span>
        <ArrowDown className="size-4" />
      </motion.div>
    </section>
  );
}

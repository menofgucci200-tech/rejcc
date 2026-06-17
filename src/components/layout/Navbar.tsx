"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { AnimatePresence, motion } from "framer-motion";
import { Menu, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { nav, ctaPrimary } from "@/lib/content/site";
import { LogoMark } from "@/components/ui/LogoMark";
import { Button } from "@/components/ui/Button";

export function Navbar() {
  const pathname = usePathname();
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => setOpen(false), [pathname]);

  const isHome = pathname === "/";
  const solid = scrolled || !isHome || open;

  return (
    <>
      <header
        className={cn(
          "fixed inset-x-0 top-0 z-[80] transition-all duration-500",
          solid
            ? "glass border-b border-brand/10 py-2.5 shadow-[0_8px_30px_-18px_rgba(3,29,89,0.35)]"
            : "border-b border-transparent py-4",
        )}
      >
        <nav className="mx-auto flex w-full max-w-7xl items-center justify-between container-px">
          {/* Logo */}
          <Link
            href="/"
            aria-label="REJCC — Accueil"
            className="relative z-10 flex items-center"
          >
            <LogoMark
              kind={solid ? "lockup-color" : "lockup-white"}
              priority
              className="h-9 sm:h-10"
            />
          </Link>

          {/* Desktop nav */}
          <ul className="hidden items-center gap-1 lg:flex">
            {nav.map((item) => {
              const active =
                item.href === "/"
                  ? pathname === "/"
                  : pathname.startsWith(item.href);
              return (
                <li key={item.href}>
                  <Link
                    href={item.href}
                    className={cn(
                      "relative rounded-full px-3.5 py-2 text-sm font-semibold transition-colors",
                      solid
                        ? active
                          ? "text-brand"
                          : "text-ink/70 hover:text-brand"
                        : active
                          ? "text-white"
                          : "text-white/80 hover:text-white",
                    )}
                  >
                    {item.label}
                    {active && (
                      <motion.span
                        layoutId="nav-active"
                        className="absolute inset-x-3 -bottom-0.5 h-0.5 rounded-full bg-accent"
                      />
                    )}
                  </Link>
                </li>
              );
            })}
          </ul>

          <div className="flex items-center gap-2">
            <Button
              href={ctaPrimary.href}
              size="sm"
              variant={solid ? "primary" : "white"}
              withArrow
              className="hidden sm:inline-flex"
            >
              {ctaPrimary.label}
            </Button>

            {/* Mobile toggle */}
            <button
              onClick={() => setOpen((v) => !v)}
              aria-label={open ? "Fermer le menu" : "Ouvrir le menu"}
              aria-expanded={open}
              className={cn(
                "relative z-10 inline-flex size-11 items-center justify-center rounded-full border transition-colors lg:hidden",
                solid
                  ? "border-brand/15 text-brand"
                  : "border-white/25 text-white",
              )}
            >
              {open ? <X className="size-5" /> : <Menu className="size-5" />}
            </button>
          </div>
        </nav>
      </header>

      {/* Mobile menu */}
      <AnimatePresence>
        {open && (
          <motion.div
            className="fixed inset-0 z-[70] flex flex-col bg-brand lg:hidden"
            initial={{ opacity: 0, clipPath: "circle(0% at 90% 6%)" }}
            animate={{ opacity: 1, clipPath: "circle(150% at 90% 6%)" }}
            exit={{ opacity: 0, clipPath: "circle(0% at 90% 6%)" }}
            transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
          >
            <div className="mt-24 flex flex-1 flex-col gap-1 container-px">
              {nav.map((item, i) => (
                <motion.div
                  key={item.href}
                  initial={{ opacity: 0, x: 24 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: 0.12 + i * 0.05 }}
                >
                  <Link
                    href={item.href}
                    className="block border-b border-white/10 py-4 font-display text-3xl uppercase tracking-tight text-white"
                  >
                    {item.label}
                  </Link>
                </motion.div>
              ))}
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.5 }}
                className="mt-8"
              >
                <Button href={ctaPrimary.href} variant="white" size="lg" withArrow className="w-full">
                  {ctaPrimary.label}
                </Button>
              </motion.div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}

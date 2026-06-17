"use client";

import { useEffect, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { LogoMark } from "@/components/ui/LogoMark";

export function Loader() {
  const [done, setDone] = useState(false);

  useEffect(() => {
    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const seen =
      typeof sessionStorage !== "undefined" &&
      sessionStorage.getItem("rejcc-intro") === "1";

    if (reduce || seen) {
      setDone(true);
      return;
    }
    const t = setTimeout(() => {
      setDone(true);
      try {
        sessionStorage.setItem("rejcc-intro", "1");
      } catch {}
    }, 1750);
    return () => clearTimeout(t);
  }, []);

  // Empêche le scroll pendant l'intro
  useEffect(() => {
    document.documentElement.style.overflow = done ? "" : "hidden";
    return () => {
      document.documentElement.style.overflow = "";
    };
  }, [done]);

  return (
    <AnimatePresence>
      {!done && (
        <motion.div
          className="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-brand"
          initial={{ opacity: 1 }}
          exit={{ opacity: 0, transition: { duration: 0.6, ease: "easeInOut" } }}
        >
          {/* halo */}
          <div className="pointer-events-none absolute inset-0 opacity-40">
            <div className="absolute left-1/2 top-1/2 size-[60vmin] -translate-x-1/2 -translate-y-1/2 rounded-full bg-azure/30 blur-[90px]" />
          </div>

          <motion.div
            initial={{ opacity: 0, y: 12, scale: 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
          >
            <LogoMark kind="mono-white" priority className="h-24 sm:h-28" />
          </motion.div>

          <div className="mt-8 h-[3px] w-40 overflow-hidden rounded-full bg-white/15">
            <motion.div
              className="h-full bg-white"
              initial={{ x: "-100%" }}
              animate={{ x: "0%" }}
              transition={{ duration: 1.5, ease: [0.22, 1, 0.36, 1] }}
            />
          </div>

          <motion.p
            className="mt-5 font-serif text-sm italic text-white/70"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.5, duration: 0.6 }}
          >
            Ensemble pour l&apos;excellence.
          </motion.p>
        </motion.div>
      )}
    </AnimatePresence>
  );
}

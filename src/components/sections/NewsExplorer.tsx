"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { Clock, ArrowUpRight, Search } from "lucide-react";
import { LogoMark } from "@/components/ui/LogoMark";
import { cn } from "@/lib/utils";
import { articles, newsCategories, type Article } from "@/lib/content/news";

export function NewsExplorer() {
  const [category, setCategory] = useState<string>("Toutes");
  const [query, setQuery] = useState("");

  const filtered = useMemo<Article[]>(() => {
    const q = query.trim().toLowerCase();
    return articles
      .filter((a) => category === "Toutes" || a.category === category)
      .filter(
        (a) =>
          q === "" ||
          a.title.toLowerCase().includes(q) ||
          a.excerpt.toLowerCase().includes(q),
      )
      .sort((a, b) => (a.iso < b.iso ? 1 : -1));
  }, [category, query]);

  const cats = ["Toutes", ...newsCategories];

  return (
    <div>
      {/* Contrôles */}
      <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div className="flex flex-wrap gap-2">
          {cats.map((c) => (
            <button
              key={c}
              onClick={() => setCategory(c)}
              className={cn(
                "rounded-full px-4 py-2 text-sm font-semibold transition-colors",
                category === c
                  ? "bg-brand text-white"
                  : "border border-brand/15 text-ink/70 hover:border-brand/30 hover:text-brand",
              )}
            >
              {c}
            </button>
          ))}
        </div>
        <div className="relative lg:w-72">
          <Search className="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Rechercher un article…"
            aria-label="Rechercher un article"
            className="w-full rounded-full border border-brand/15 bg-white py-2.5 pl-11 pr-4 text-sm text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20"
          />
        </div>
      </div>

      {/* Résultats */}
      {filtered.length === 0 ? (
        <p className="mt-16 text-center text-ink/60">
          Aucun article ne correspond à votre recherche.
        </p>
      ) : (
        <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {filtered.map((a) => (
            <Link
              key={a.slug}
              href={`/actualites/${a.slug}`}
              className="group flex h-full flex-col overflow-hidden rounded-3xl border border-brand/10 bg-white transition-all duration-500 hover:-translate-y-1.5 hover:shadow-[0_30px_70px_-35px_rgba(3,29,89,0.4)]"
            >
              <div className="relative aspect-[16/10] overflow-hidden bg-brand">
                <div className="absolute inset-0 bg-grid opacity-20" />
                <div className="absolute inset-0 bg-linear-to-br from-brand via-brand to-brand-900" />
                <div className="absolute -right-6 -top-6 size-32 rounded-full bg-azure/20 blur-2xl" />
                <div className="absolute inset-0 flex items-center justify-center transition-transform duration-700 group-hover:scale-105">
                  <LogoMark kind="mono-white" className="h-16 opacity-80" />
                </div>
                <span className="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand">
                  {a.category}
                </span>
              </div>
              <div className="flex flex-1 flex-col p-6">
                <h3 className="text-lg font-bold leading-snug text-brand transition-colors group-hover:text-accent">
                  {a.title}
                </h3>
                <p className="mt-2 flex-1 text-pretty text-sm leading-relaxed text-ink/65">
                  {a.excerpt}
                </p>
                <div className="mt-5 flex items-center justify-between border-t border-brand/10 pt-4 text-xs text-ink/55">
                  <span>{a.date}</span>
                  <span className="flex items-center gap-1.5">
                    <Clock className="size-3.5" /> {a.readingTime}
                    <ArrowUpRight className="ml-1 size-4 text-accent transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                  </span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

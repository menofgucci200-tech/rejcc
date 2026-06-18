"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, Download, FileText, Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { memberApi, type DocItem } from "@/lib/api/client";
import { Container } from "@/components/ui/Container";

export function DocumentsView() {
  const { token } = useAuth();
  const [docs, setDocs] = useState<DocItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    memberApi
      .documents(token)
      .then((r) => setDocs(r.documents))
      .finally(() => setLoading(false));
  }, [token]);

  const categories = [...new Set(docs.map((d) => d.category))];

  return (
    <>
      <header className="relative overflow-hidden bg-brand pb-12 pt-36 sm:pt-44">
        <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.22] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]" />
        <Container className="relative">
          <Link
            href="/espace-membre"
            className="inline-flex items-center gap-2 text-sm text-white/60 transition-colors hover:text-white"
          >
            <ArrowLeft className="size-4" /> Tableau de bord
          </Link>
          <h1 className="mt-5 font-display text-[clamp(2rem,5vw,3.25rem)] uppercase leading-none tracking-tight text-white">
            Documents & ressources
          </h1>
        </Container>
      </header>

      <section className="bg-cloud py-12 sm:py-16">
        <Container className="max-w-4xl">
          {loading ? (
            <div className="flex justify-center py-16">
              <Loader2 className="size-7 animate-spin text-brand" />
            </div>
          ) : docs.length === 0 ? (
            <p className="py-16 text-center text-ink/55">Aucun document disponible.</p>
          ) : (
            <div className="flex flex-col gap-10">
              {categories.map((cat) => (
                <div key={cat}>
                  <h2 className="mb-4 text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">
                    {cat}
                  </h2>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {docs
                      .filter((d) => d.category === cat)
                      .map((d) => (
                        <a
                          key={d.id}
                          href={d.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="group flex items-center gap-4 rounded-3xl border border-brand/10 bg-white p-5 transition-all hover:-translate-y-0.5 hover:shadow-[0_22px_45px_-30px_rgba(3,29,89,0.4)]"
                        >
                          <span className="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand/5 text-brand">
                            <FileText className="size-5" />
                          </span>
                          <span className="min-w-0 flex-1">
                            <span className="block font-semibold text-brand">{d.title}</span>
                            {d.description && (
                              <span className="block truncate text-sm text-ink/60">
                                {d.description}
                              </span>
                            )}
                          </span>
                          <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-accent">
                            {d.size}
                            <Download className="size-4" />
                          </span>
                        </a>
                      ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </Container>
      </section>
    </>
  );
}

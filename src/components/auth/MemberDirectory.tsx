"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, Loader2, MapPin, Search } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { authApi, type Member } from "@/lib/api/client";
import { Container } from "@/components/ui/Container";

export function MemberDirectory() {
  const { token } = useAuth();
  const [members, setMembers] = useState<Member[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [query, setQuery] = useState("");

  useEffect(() => {
    if (!token) return;
    authApi
      .members(token)
      .then((r) => setMembers(r.members))
      .catch((e) => setError(e instanceof Error ? e.message : "Erreur"))
      .finally(() => setLoading(false));
  }, [token]);

  const q = query.trim().toLowerCase();
  const filtered = members.filter(
    (m) =>
      q === "" ||
      `${m.prenom} ${m.nom}`.toLowerCase().includes(q) ||
      (m.secteur ?? "").toLowerCase().includes(q) ||
      (m.ville ?? "").toLowerCase().includes(q),
  );

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
            Annuaire des membres
          </h1>
          <p className="mt-3 text-white/70">
            Retrouvez et contactez les membres du réseau.
          </p>
        </Container>
      </header>

      <section className="bg-cloud py-14 sm:py-20">
        <Container>
          <div className="relative mb-8 max-w-md">
            <Search className="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
            <input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Rechercher (nom, domaine, ville)…"
              className="w-full rounded-full border border-brand/15 bg-white py-2.5 pl-11 pr-4 text-sm text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20"
            />
          </div>

          {loading ? (
            <div className="flex justify-center py-16">
              <Loader2 className="size-7 animate-spin text-brand" />
            </div>
          ) : error ? (
            <p className="text-accent">{error}</p>
          ) : filtered.length === 0 ? (
            <p className="py-10 text-center text-ink/60">Aucun membre trouvé.</p>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {filtered.map((m) => (
                <article
                  key={m.id}
                  className="rounded-3xl border border-brand/10 bg-white p-6"
                >
                  <div className="flex items-center gap-3.5">
                    <span className="inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">
                      {m.prenom?.[0]}
                      {m.nom?.[0]}
                    </span>
                    <div className="min-w-0">
                      <p className="truncate font-bold text-brand">
                        {m.prenom} {m.nom}
                      </p>
                      {m.secteur && (
                        <p className="truncate text-sm text-ink/60">{m.secteur}</p>
                      )}
                    </div>
                  </div>
                  {(m.ville || m.organisation) && (
                    <p className="mt-4 flex items-center gap-1.5 text-xs text-ink/55">
                      <MapPin className="size-3.5" />
                      {[m.ville, m.organisation].filter(Boolean).join(" · ")}
                    </p>
                  )}
                </article>
              ))}
            </div>
          )}
        </Container>
      </section>
    </>
  );
}

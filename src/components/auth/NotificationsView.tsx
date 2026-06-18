"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, Bell, Loader2, MessageCircle, Info } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { memberApi, type Notice } from "@/lib/api/client";
import { Container } from "@/components/ui/Container";

function time(s: string) {
  return new Date(s).toLocaleString("fr-FR", {
    day: "2-digit",
    month: "long",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function NotificationsView() {
  const { token } = useAuth();
  const [items, setItems] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    memberApi
      .notifications(token)
      .then((r) => setItems(r.notifications))
      .finally(() => setLoading(false));
    // Marque comme lues à l'ouverture (efface le compteur).
    memberApi.markAllNotificationsRead(token).catch(() => {});
  }, [token]);

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
            Notifications
          </h1>
        </Container>
      </header>

      <section className="bg-cloud py-12 sm:py-16">
        <Container className="max-w-2xl">
          {loading ? (
            <div className="flex justify-center py-16">
              <Loader2 className="size-7 animate-spin text-brand" />
            </div>
          ) : items.length === 0 ? (
            <p className="py-16 text-center text-ink/55">Aucune notification.</p>
          ) : (
            <ul className="flex flex-col gap-3">
              {items.map((n) => {
                const Icon = n.type === "message" ? MessageCircle : Info;
                const inner = (
                  <div className="flex items-start gap-4 rounded-3xl border border-brand/10 bg-white p-5">
                    <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand/5 text-brand">
                      <Icon className="size-5" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="font-semibold text-brand">{n.title}</p>
                      {n.body && <p className="mt-0.5 text-sm text-ink/70">{n.body}</p>}
                      <p className="mt-1.5 text-xs text-ink/40">{time(n.created_at)}</p>
                    </div>
                    {!n.read_at && (
                      <span className="mt-1 size-2.5 shrink-0 rounded-full bg-accent" />
                    )}
                  </div>
                );
                return (
                  <li key={n.id}>
                    {n.link ? <Link href={n.link}>{inner}</Link> : inner}
                  </li>
                );
              })}
            </ul>
          )}
        </Container>
      </section>
    </>
  );
}

"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import {
  LogOut,
  Loader2,
  User,
  Users,
  MessageCircle,
  FolderClosed,
  CalendarDays,
} from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { Container } from "@/components/ui/Container";
import { MemberAreaUnavailable } from "./forms";

const profilLabels: Record<string, string> = {
  etudiant: "Étudiant & jeune diplômé",
  porteur: "Porteur de projet",
  entrepreneur: "Entrepreneur confirmé",
};

const quickLinks = [
  { icon: Users, label: "Annuaire des membres", href: "/espace-membre/annuaire", ready: true },
  { icon: User, label: "Mon profil", href: "/espace-membre/profil", ready: true },
  { icon: MessageCircle, label: "Messagerie", href: "#", ready: false },
  { icon: CalendarDays, label: "Mes événements", href: "#", ready: false },
  { icon: FolderClosed, label: "Documents & ressources", href: "#", ready: false },
];

export function MemberDashboard() {
  const { user, loading, configured, logout } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && configured && !user) router.replace("/connexion");
  }, [loading, configured, user, router]);

  if (!configured) {
    return (
      <section className="bg-cloud pb-24 pt-36 sm:pt-44">
        <Container className="max-w-2xl">
          <MemberAreaUnavailable />
        </Container>
      </section>
    );
  }

  if (loading || !user) {
    return (
      <section className="flex min-h-[60vh] items-center justify-center bg-cloud pt-24">
        <Loader2 className="size-8 animate-spin text-brand" />
      </section>
    );
  }

  return (
    <>
      {/* En-tête */}
      <header className="relative overflow-hidden bg-brand pb-14 pt-36 sm:pt-44">
        <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.22] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]" />
        <div className="pointer-events-none absolute -right-[8%] -top-[10%] size-[40vmax] rounded-full bg-azure/20 blur-[120px]" />
        <Container className="relative flex flex-wrap items-center justify-between gap-4">
          <div>
            <span className="text-sm font-semibold uppercase tracking-[0.16em] text-white/55">
              Espace membre
            </span>
            <h1 className="mt-2 font-display text-[clamp(2rem,5vw,3.25rem)] uppercase leading-none tracking-tight text-white">
              Bonjour, {user.prenom}
            </h1>
          </div>
          <button
            onClick={async () => {
              await logout();
              router.push("/");
            }}
            className="inline-flex items-center gap-2 rounded-full border border-white/25 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10"
          >
            <LogOut className="size-4" /> Se déconnecter
          </button>
        </Container>
      </header>

      {/* Contenu */}
      <section className="bg-cloud py-16 sm:py-20">
        <Container className="grid gap-8 lg:grid-cols-[1fr_2fr]">
          {/* Carte profil */}
          <div className="h-fit rounded-3xl border border-brand/10 bg-white p-7">
            <div className="flex items-center gap-4">
              <span className="inline-flex size-14 items-center justify-center rounded-full bg-brand text-lg font-bold text-white">
                {user.prenom?.[0]}
                {user.nom?.[0]}
              </span>
              <div className="min-w-0">
                <p className="truncate font-bold text-brand">
                  {user.prenom} {user.nom}
                </p>
                <p className="truncate text-sm text-ink/60">{user.email}</p>
              </div>
            </div>
            <dl className="mt-6 space-y-3 text-sm">
              {user.profil && (
                <div className="flex justify-between gap-3">
                  <dt className="text-ink/55">Profil</dt>
                  <dd className="text-right font-medium text-brand">
                    {profilLabels[user.profil] ?? user.profil}
                  </dd>
                </div>
              )}
              {user.ville && (
                <div className="flex justify-between gap-3">
                  <dt className="text-ink/55">Ville</dt>
                  <dd className="font-medium text-brand">{user.ville}</dd>
                </div>
              )}
              {user.secteur && (
                <div className="flex justify-between gap-3">
                  <dt className="text-ink/55">Domaine</dt>
                  <dd className="text-right font-medium text-brand">{user.secteur}</dd>
                </div>
              )}
            </dl>
            <Link
              href="/espace-membre/profil"
              className="mt-6 inline-flex w-full items-center justify-center rounded-full border border-brand/15 py-2.5 text-sm font-semibold text-brand transition-colors hover:bg-brand hover:text-white"
            >
              Modifier mon profil
            </Link>
          </div>

          {/* Accès rapides */}
          <div className="grid gap-4 sm:grid-cols-2">
            {quickLinks.map((q) => {
              const inner = (
                <div
                  className={`group flex h-full items-center gap-4 rounded-3xl border border-brand/10 bg-white p-6 transition-all ${
                    q.ready
                      ? "hover:-translate-y-1 hover:shadow-[0_24px_50px_-30px_rgba(3,29,89,0.4)]"
                      : "opacity-60"
                  }`}
                >
                  <span className="inline-flex size-12 items-center justify-center rounded-2xl bg-brand/5 text-brand transition-colors group-hover:bg-brand group-hover:text-white">
                    <q.icon className="size-5" />
                  </span>
                  <div>
                    <p className="font-semibold text-brand">{q.label}</p>
                    {!q.ready && <p className="text-xs text-ink/50">Bientôt disponible</p>}
                  </div>
                </div>
              );
              return q.ready ? (
                <Link key={q.label} href={q.href}>
                  {inner}
                </Link>
              ) : (
                <div key={q.label}>{inner}</div>
              );
            })}
          </div>
        </Container>
      </section>
    </>
  );
}

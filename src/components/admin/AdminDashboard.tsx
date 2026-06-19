"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Users, FileText, MessageSquare, FolderOpen, AlertCircle, ShieldCheck, Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { adminApi, type AdminStats } from "@/lib/api/client";

const statCards = (s: AdminStats) => [
  { icon: Users,         label: "Membres actifs",       value: s.membres,     href: "/admin/membres",   color: "bg-brand" },
  { icon: FileText,      label: "Demandes d'adhésion",  value: s.adhesions,   href: "/admin/adhesions", color: "bg-azure" },
  { icon: MessageSquare, label: "Messages contact",      value: s.contacts,    href: "/admin/contacts",  color: "bg-accent" },
  { icon: AlertCircle,   label: "Contacts non traités",  value: s.non_traites, href: "/admin/contacts",  color: "bg-orange-500" },
  { icon: FolderOpen,    label: "Documents",            value: s.documents,   href: "/admin/documents", color: "bg-emerald-600" },
  { icon: ShieldCheck,   label: "Administrateurs",      value: s.admins,      href: "/admin/membres",   color: "bg-purple-600" },
];

export function AdminDashboard() {
  const { token, user } = useAuth();
  const [stats, setStats] = useState<AdminStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    adminApi.stats(token)
      .then((r) => setStats(r.stats))
      .finally(() => setLoading(false));
  }, [token]);

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-brand">
          Bonjour, {user?.prenom} 👋
        </h1>
        <p className="mt-1 text-sm text-ink/60">Vue d&apos;ensemble de la plateforme REJCC.</p>
      </div>

      {loading ? (
        <div className="flex justify-center py-16">
          <Loader2 className="size-7 animate-spin text-brand" />
        </div>
      ) : stats ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {statCards(stats).map((c) => (
            <Link
              key={c.label}
              href={c.href}
              className="group flex items-center gap-4 rounded-2xl border border-brand/10 bg-white p-5 transition-all hover:-translate-y-0.5 hover:shadow-[0_20px_50px_-30px_rgba(3,29,89,0.3)]"
            >
              <span className={`inline-flex size-12 shrink-0 items-center justify-center rounded-xl ${c.color} text-white`}>
                <c.icon className="size-5" />
              </span>
              <div>
                <p className="text-2xl font-bold text-brand">{c.value}</p>
                <p className="text-sm text-ink/60">{c.label}</p>
              </div>
            </Link>
          ))}
        </div>
      ) : (
        <p className="text-ink/60">Impossible de charger les statistiques.</p>
      )}
    </div>
  );
}

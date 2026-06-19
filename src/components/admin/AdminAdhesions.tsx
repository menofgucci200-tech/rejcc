"use client";

import { useEffect, useState } from "react";
import { Loader2, CheckCircle, XCircle, Clock } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { adminApi, type AdminAdhesion } from "@/lib/api/client";

const statutBadge: Record<string, { label: string; cls: string; icon: React.ReactNode }> = {
  en_attente: { label: "En attente", cls: "bg-amber-100 text-amber-700",  icon: <Clock className="size-3" /> },
  valide:     { label: "Validé",     cls: "bg-emerald-100 text-emerald-700", icon: <CheckCircle className="size-3" /> },
  rejete:     { label: "Rejeté",    cls: "bg-red-100 text-red-700",      icon: <XCircle className="size-3" /> },
};

const payLabel: Record<string, string> = { wave: "Wave", orange: "Orange Money", djamo: "Djamo" };

export function AdminAdhesions() {
  const { token } = useAuth();
  const [adhesions, setAdhesions] = useState<AdminAdhesion[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<number | null>(null);

  useEffect(() => {
    if (!token) return;
    adminApi.adhesions(token).then((r) => setAdhesions(r.adhesions)).finally(() => setLoading(false));
  }, [token]);

  async function updateStatut(a: AdminAdhesion, statut: string) {
    if (!token) return;
    setBusy(a.id);
    await adminApi.updateAdhesion(token, a.id, statut);
    setAdhesions((prev) => prev.map((x) => x.id === a.id ? { ...x, statut } : x));
    setBusy(null);
  }

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-brand">Demandes d&apos;adhésion</h1>
        <p className="text-sm text-ink/60">{adhesions.length} demande{adhesions.length > 1 ? "s" : ""}</p>
      </div>

      {loading ? (
        <div className="flex justify-center py-16"><Loader2 className="size-7 animate-spin text-brand" /></div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-brand/10 bg-white">
          <table className="w-full text-sm">
            <thead className="border-b border-brand/10 bg-cloud/60">
              <tr>
                {["Réf.", "Demandeur", "Profil", "Paiement", "Statut", "Date", "Actions"].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink/50">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-brand/5">
              {adhesions.map((a) => {
                const badge = statutBadge[a.statut] ?? statutBadge.en_attente;
                return (
                  <tr key={a.id} className="hover:bg-cloud/50">
                    <td className="px-4 py-3 font-mono text-xs text-ink/60">{a.reference}</td>
                    <td className="px-4 py-3">
                      <p className="font-semibold text-brand">{a.prenom} {a.nom}</p>
                      <p className="text-xs text-ink/50">{a.email}</p>
                    </td>
                    <td className="px-4 py-3 text-ink/60">{a.profil ?? "—"}</td>
                    <td className="px-4 py-3 text-ink/60">{payLabel[a.paiement] ?? a.paiement}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${badge.cls}`}>
                        {badge.icon} {badge.label}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-ink/50">{new Date(a.created_at).toLocaleDateString("fr-FR")}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1.5">
                        {a.statut !== "valide" && (
                          <button
                            onClick={() => updateStatut(a, "valide")}
                            disabled={busy === a.id}
                            className="rounded-lg px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 disabled:opacity-40"
                          >
                            Valider
                          </button>
                        )}
                        {a.statut !== "rejete" && (
                          <button
                            onClick={() => updateStatut(a, "rejete")}
                            disabled={busy === a.id}
                            className="rounded-lg px-2.5 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-40"
                          >
                            Rejeter
                          </button>
                        )}
                        {busy === a.id && <Loader2 className="size-3.5 animate-spin text-brand" />}
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
          {adhesions.length === 0 && (
            <p className="py-12 text-center text-sm text-ink/50">Aucune demande.</p>
          )}
        </div>
      )}
    </div>
  );
}

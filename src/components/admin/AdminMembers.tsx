"use client";

import { useEffect, useState } from "react";
import { Search, Shield, Trash2, Loader2, UserCheck } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { adminApi, type AdminMember } from "@/lib/api/client";

export function AdminMembers() {
  const { token } = useAuth();
  const [members, setMembers] = useState<AdminMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState("");
  const [busy, setBusy] = useState<number | null>(null);

  function load(q = "") {
    if (!token) return;
    setLoading(true);
    adminApi.members(token, q).then((r) => setMembers(r.members)).finally(() => setLoading(false));
  }

  useEffect(() => { load(); }, [token]); // eslint-disable-line

  async function toggleRole(m: AdminMember) {
    if (!token) return;
    setBusy(m.id);
    const newRole = m.role === "admin" ? "member" : "admin";
    await adminApi.updateMember(token, m.id, { role: newRole });
    setMembers((prev) => prev.map((x) => x.id === m.id ? { ...x, role: newRole } : x));
    setBusy(null);
  }

  async function deleteMember(m: AdminMember) {
    if (!token || !confirm(`Supprimer ${m.prenom} ${m.nom} ?`)) return;
    setBusy(m.id);
    await adminApi.deleteMember(token, m.id);
    setMembers((prev) => prev.filter((x) => x.id !== m.id));
    setBusy(null);
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-brand">Membres</h1>
          <p className="text-sm text-ink/60">{members.length} compte{members.length > 1 ? "s" : ""}</p>
        </div>
        <div className="relative max-w-xs flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onKeyDown={(e) => e.key === "Enter" && load(query)}
            placeholder="Rechercher…"
            className="w-full rounded-full border border-brand/15 bg-white py-2 pl-10 pr-4 text-sm outline-none focus:border-brand"
          />
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-16"><Loader2 className="size-7 animate-spin text-brand" /></div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-brand/10 bg-white">
          <table className="w-full text-sm">
            <thead className="border-b border-brand/10 bg-cloud/60">
              <tr>
                {["Nom", "E-mail", "Profil", "Ville", "Rôle", "Depuis", "Actions"].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-ink/50">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-brand/5">
              {members.map((m) => (
                <tr key={m.id} className="hover:bg-cloud/50">
                  <td className="px-4 py-3 font-semibold text-brand">{m.prenom} {m.nom}</td>
                  <td className="px-4 py-3 text-ink/70">{m.email}</td>
                  <td className="px-4 py-3 text-ink/60">{m.profil ?? "—"}</td>
                  <td className="px-4 py-3 text-ink/60">{m.ville ?? "—"}</td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${m.role === "admin" ? "bg-purple-100 text-purple-700" : "bg-brand/10 text-brand"}`}>
                      {m.role === "admin" && <Shield className="size-3" />}
                      {m.role}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-ink/50">{new Date(m.created_at).toLocaleDateString("fr-FR")}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => toggleRole(m)}
                        disabled={busy === m.id}
                        title={m.role === "admin" ? "Rétrograder en membre" : "Promouvoir admin"}
                        className="rounded-lg p-1.5 text-ink/40 transition hover:bg-brand/10 hover:text-brand disabled:opacity-40"
                      >
                        {busy === m.id ? <Loader2 className="size-4 animate-spin" /> : <UserCheck className="size-4" />}
                      </button>
                      <button
                        onClick={() => deleteMember(m)}
                        disabled={busy === m.id || m.role === "admin"}
                        title="Supprimer"
                        className="rounded-lg p-1.5 text-ink/40 transition hover:bg-accent/10 hover:text-accent disabled:opacity-30"
                      >
                        <Trash2 className="size-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {members.length === 0 && (
            <p className="py-12 text-center text-sm text-ink/50">Aucun membre trouvé.</p>
          )}
        </div>
      )}
    </div>
  );
}

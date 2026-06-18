"use client";

import { useEffect, useState } from "react";
import { CheckCircle, Loader2, MessageSquare } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { adminApi, type AdminContact } from "@/lib/api/client";

export function AdminContacts() {
  const { token } = useAuth();
  const [contacts, setContacts] = useState<AdminContact[]>([]);
  const [loading, setLoading] = useState(true);
  const [expanded, setExpanded] = useState<number | null>(null);
  const [busy, setBusy] = useState<number | null>(null);

  useEffect(() => {
    if (!token) return;
    adminApi.contacts(token).then((r) => setContacts(r.contacts)).finally(() => setLoading(false));
  }, [token]);

  async function markTraite(c: AdminContact) {
    if (!token) return;
    setBusy(c.id);
    await adminApi.markContactTraite(token, c.id);
    setContacts((prev) => prev.map((x) => x.id === c.id ? { ...x, traite: true } : x));
    setBusy(null);
  }

  const pending = contacts.filter((c) => !c.traite);
  const done = contacts.filter((c) => c.traite);

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-brand">Messages de contact</h1>
        <p className="text-sm text-ink/60">{pending.length} non traité{pending.length > 1 ? "s" : ""} · {done.length} traité{done.length > 1 ? "s" : ""}</p>
      </div>

      {loading ? (
        <div className="flex justify-center py-16"><Loader2 className="size-7 animate-spin text-brand" /></div>
      ) : (
        <div className="flex flex-col gap-3">
          {[...pending, ...done].map((c) => (
            <div
              key={c.id}
              className={`rounded-2xl border bg-white transition-all ${c.traite ? "border-brand/8 opacity-70" : "border-brand/15"}`}
            >
              <button
                className="flex w-full items-start gap-4 px-5 py-4 text-left"
                onClick={() => setExpanded((v) => v === c.id ? null : c.id)}
              >
                <span className={`mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-xl ${c.traite ? "bg-emerald-100 text-emerald-600" : "bg-brand/10 text-brand"}`}>
                  <MessageSquare className="size-4" />
                </span>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-3">
                    <p className="font-semibold text-brand truncate">{c.nom}</p>
                    <span className="text-xs text-ink/40">{new Date(c.created_at).toLocaleDateString("fr-FR")}</span>
                    {!c.traite && <span className="rounded-full bg-accent/15 px-2 py-0.5 text-[0.65rem] font-bold text-accent">NOUVEAU</span>}
                  </div>
                  <p className="text-sm text-ink/60 truncate">{c.sujet}</p>
                </div>
              </button>

              {expanded === c.id && (
                <div className="border-t border-brand/8 px-5 pb-4 pt-3">
                  <p className="text-xs text-ink/50 mb-1">E-mail : <span className="text-brand">{c.email}</span></p>
                  <p className="text-sm text-ink/80 whitespace-pre-wrap">{c.message}</p>
                  {!c.traite && (
                    <button
                      onClick={() => markTraite(c)}
                      disabled={busy === c.id}
                      className="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                    >
                      {busy === c.id ? <Loader2 className="size-3.5 animate-spin" /> : <CheckCircle className="size-3.5" />}
                      Marquer comme traité
                    </button>
                  )}
                </div>
              )}
            </div>
          ))}
          {contacts.length === 0 && <p className="py-12 text-center text-sm text-ink/50">Aucun message.</p>}
        </div>
      )}
    </div>
  );
}

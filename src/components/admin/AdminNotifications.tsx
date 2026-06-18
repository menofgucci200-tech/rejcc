"use client";

import { useState } from "react";
import { Bell, Loader2, Send } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { adminApi } from "@/lib/api/client";

export function AdminNotifications() {
  const { token } = useAuth();
  const [form, setForm] = useState({ title: "", body: "", link: "", type: "info" });
  const [saving, setSaving] = useState(false);
  const [result, setResult] = useState<string | null>(null);
  const [error, setError] = useState("");

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
    setForm((f) => ({ ...f, [k]: e.target.value }));

  async function onSend(e: React.FormEvent) {
    e.preventDefault();
    if (!token || !form.title) { setError("Le titre est obligatoire."); return; }
    setSaving(true);
    setError("");
    setResult(null);
    try {
      const r = await adminApi.broadcastNotification(token, form);
      setResult(`Notification envoyée à ${r.sent_to} membre${r.sent_to > 1 ? "s" : ""}.`);
      setForm({ title: "", body: "", link: "", type: "info" });
    } catch (e) {
      setError(e instanceof Error ? e.message : "Erreur");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="max-w-2xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-brand">Envoyer une notification</h1>
        <p className="text-sm text-ink/60">Diffusez un message à tous les membres en un clic.</p>
      </div>

      <form onSubmit={onSend} className="rounded-2xl border border-brand/10 bg-white p-6">
        <div className="flex flex-col gap-4">
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand">Type</label>
            <select value={form.type} onChange={set("type")} className="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand">
              <option value="info">ℹ️ Information</option>
              <option value="alert">⚠️ Alerte</option>
              <option value="message">💬 Message</option>
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand">Titre <span className="text-accent">*</span></label>
            <input value={form.title} onChange={set("title")} required className="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" placeholder="Ex : Événement de networking — Juin 2026" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand">Corps du message</label>
            <textarea value={form.body} onChange={set("body")} rows={3} className="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand resize-none" placeholder="Détails supplémentaires…" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-semibold text-brand">Lien (optionnel)</label>
            <input value={form.link} onChange={set("link")} className="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand" placeholder="/espace-membre/evenements" />
          </div>
        </div>

        {error && <p className="mt-3 text-sm text-accent">{error}</p>}
        {result && (
          <div className="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            <Bell className="size-4" /> {result}
          </div>
        )}

        <button
          type="submit"
          disabled={saving}
          className="mt-5 inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 font-semibold text-white transition hover:bg-accent-600 disabled:opacity-60"
        >
          {saving ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
          Diffuser à tous les membres
        </button>
      </form>
    </div>
  );
}

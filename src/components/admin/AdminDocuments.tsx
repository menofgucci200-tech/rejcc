"use client";

import { useEffect, useState } from "react";
import { FileText, Plus, Trash2, Pencil, Loader2, X, Check } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { adminApi, type DocItem } from "@/lib/api/client";

const emptyForm = { title: "", description: "", category: "", url: "", size: "" };

export function AdminDocuments() {
  const { token } = useAuth();
  const [docs, setDocs] = useState<DocItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<DocItem | null>(null);
  const [form, setForm] = useState(emptyForm);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState<number | null>(null);

  useEffect(() => {
    if (!token) return;
    adminApi.documents(token).then((r) => setDocs(r.documents)).finally(() => setLoading(false));
  }, [token]);

  function openCreate() { setEditing(null); setForm(emptyForm); setError(""); setShowForm(true); }
  function openEdit(d: DocItem) {
    setEditing(d);
    setForm({ title: d.title, description: d.description ?? "", category: d.category, url: d.url, size: d.size ?? "" });
    setError("");
    setShowForm(true);
  }
  function closeForm() { setShowForm(false); setEditing(null); }

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement>) => setForm((f) => ({ ...f, [k]: e.target.value }));

  async function saveDoc() {
    if (!token) return;
    if (!form.title || !form.category || !form.url) { setError("Titre, catégorie et URL sont obligatoires."); return; }
    setSaving(true);
    setError("");
    try {
      if (editing) {
        const r = await adminApi.updateDocument(token, editing.id, form);
        setDocs((prev) => prev.map((d) => d.id === editing.id ? r.document : d));
      } else {
        const r = await adminApi.createDocument(token, form as Omit<DocItem, "id">);
        setDocs((prev) => [...prev, r.document]);
      }
      closeForm();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Erreur");
    } finally {
      setSaving(false);
    }
  }

  async function deleteDoc(d: DocItem) {
    if (!token || !confirm(`Supprimer « ${d.title} » ?`)) return;
    setBusy(d.id);
    await adminApi.deleteDocument(token, d.id);
    setDocs((prev) => prev.filter((x) => x.id !== d.id));
    setBusy(null);
  }

  const categories = [...new Set(docs.map((d) => d.category))];

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-brand">Documents</h1>
          <p className="text-sm text-ink/60">{docs.length} fichier{docs.length > 1 ? "s" : ""}</p>
        </div>
        <button
          onClick={openCreate}
          className="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand/90"
        >
          <Plus className="size-4" /> Ajouter
        </button>
      </div>

      {/* Formulaire */}
      {showForm && (
        <div className="mb-6 rounded-2xl border border-brand/15 bg-white p-6">
          <div className="mb-4 flex items-center justify-between">
            <p className="font-semibold text-brand">{editing ? "Modifier le document" : "Nouveau document"}</p>
            <button onClick={closeForm}><X className="size-4 text-ink/50" /></button>
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            {(["title:Titre", "category:Catégorie", "url:URL du fichier", "size:Taille (ex: 2.4 Mo)", "description:Description"] as const).map((f) => {
              const [k, label] = f.split(":");
              return (
                <div key={k} className={k === "url" || k === "description" ? "sm:col-span-2" : ""}>
                  <label className="mb-1 block text-xs font-semibold text-brand">{label}</label>
                  <input
                    value={(form as Record<string, string>)[k]}
                    onChange={set(k)}
                    className="w-full rounded-xl border border-brand/15 px-3 py-2 text-sm outline-none focus:border-brand"
                  />
                </div>
              );
            })}
          </div>
          {error && <p className="mt-2 text-sm text-accent">{error}</p>}
          <button
            onClick={saveDoc}
            disabled={saving}
            className="mt-4 inline-flex items-center gap-2 rounded-full bg-accent px-5 py-2 text-sm font-semibold text-white transition hover:bg-accent-600 disabled:opacity-60"
          >
            {saving ? <Loader2 className="size-3.5 animate-spin" /> : <Check className="size-3.5" />}
            Enregistrer
          </button>
        </div>
      )}

      {loading ? (
        <div className="flex justify-center py-16"><Loader2 className="size-7 animate-spin text-brand" /></div>
      ) : (
        <div className="flex flex-col gap-8">
          {categories.map((cat) => (
            <div key={cat}>
              <p className="mb-3 text-xs font-semibold uppercase tracking-widest text-ink/50">{cat}</p>
              <div className="grid gap-3 sm:grid-cols-2">
                {docs.filter((d) => d.category === cat).map((d) => (
                  <div key={d.id} className="flex items-center gap-3 rounded-2xl border border-brand/10 bg-white p-4">
                    <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand/5 text-brand">
                      <FileText className="size-4" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-semibold text-brand">{d.title}</p>
                      {d.description && <p className="truncate text-xs text-ink/55">{d.description}</p>}
                    </div>
                    <div className="flex items-center gap-1.5 shrink-0">
                      <button onClick={() => openEdit(d)} className="rounded-lg p-1.5 text-ink/40 hover:bg-brand/10 hover:text-brand">
                        <Pencil className="size-3.5" />
                      </button>
                      <button
                        onClick={() => deleteDoc(d)}
                        disabled={busy === d.id}
                        className="rounded-lg p-1.5 text-ink/40 hover:bg-accent/10 hover:text-accent disabled:opacity-40"
                      >
                        {busy === d.id ? <Loader2 className="size-3.5 animate-spin" /> : <Trash2 className="size-3.5" />}
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
          {docs.length === 0 && <p className="py-12 text-center text-sm text-ink/50">Aucun document.</p>}
        </div>
      )}
    </div>
  );
}

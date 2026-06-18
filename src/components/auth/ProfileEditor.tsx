"use client";

import { useState } from "react";
import Link from "next/link";
import { ArrowLeft, Check, Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { authApi } from "@/lib/api/client";
import { profiles } from "@/lib/content/membership";
import { Container } from "@/components/ui/Container";
import { TextField, TextareaField, SelectField } from "@/components/forms/fields";

export function ProfileEditor() {
  const { user, token, setUser } = useAuth();
  const [form, setForm] = useState({
    prenom: user?.prenom ?? "",
    nom: user?.nom ?? "",
    telephone: user?.telephone ?? "",
    genre: user?.genre ?? "",
    ville: user?.ville ?? "",
    secteur: user?.secteur ?? "",
    profil: user?.profil ?? "",
    organisation: user?.organisation ?? "",
    bio: user?.bio ?? "",
  });
  const [status, setStatus] = useState<"idle" | "saving" | "saved" | "error">("idle");
  const [error, setError] = useState("");

  const set =
    (k: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) =>
      setForm((f) => ({ ...f, [k]: e.target.value }));

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!token) return;
    setStatus("saving");
    setError("");
    try {
      const r = await authApi.updateProfile(token, form);
      setUser(r.user);
      setStatus("saved");
      setTimeout(() => setStatus("idle"), 3000);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur");
      setStatus("error");
    }
  }

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
            Mon profil
          </h1>
        </Container>
      </header>

      <section className="bg-cloud py-14 sm:py-20">
        <Container className="max-w-3xl">
          <form
            onSubmit={onSubmit}
            className="grid gap-4 rounded-3xl border border-brand/10 bg-white p-6 sm:grid-cols-2 sm:p-9"
          >
            <TextField label="Prénom" id="p-prenom" value={form.prenom} onChange={set("prenom")} />
            <TextField label="Nom" id="p-nom" value={form.nom} onChange={set("nom")} />
            <TextField label="Téléphone" id="p-tel" inputMode="numeric" value={form.telephone} onChange={set("telephone")} />
            <SelectField label="Genre" id="p-genre" value={form.genre ?? ""} onChange={set("genre")}>
              <option value="">—</option>
              <option value="Homme">Homme</option>
              <option value="Femme">Femme</option>
            </SelectField>
            <TextField label="Ville" id="p-ville" value={form.ville ?? ""} onChange={set("ville")} />
            <TextField label="Domaine d'activité" id="p-secteur" value={form.secteur ?? ""} onChange={set("secteur")} />
            <SelectField label="Profil" id="p-profil" value={form.profil ?? ""} onChange={set("profil")}>
              <option value="">—</option>
              {profiles.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.label}
                </option>
              ))}
            </SelectField>
            <TextField label="Entreprise / projet" id="p-org" value={form.organisation ?? ""} onChange={set("organisation")} />
            <div className="sm:col-span-2">
              <TextareaField label="Bio" id="p-bio" value={form.bio ?? ""} onChange={set("bio")} />
            </div>

            {error && <p className="text-sm font-medium text-accent sm:col-span-2">{error}</p>}

            <div className="flex items-center gap-4 sm:col-span-2">
              <button
                type="submit"
                disabled={status === "saving"}
                className="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-7 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70"
              >
                {status === "saving" ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : status === "saved" ? (
                  <Check className="size-4" />
                ) : null}
                {status === "saved" ? "Enregistré" : "Enregistrer"}
              </button>
            </div>
          </form>
        </Container>
      </section>
    </>
  );
}

"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { Loader2, Lock, Sparkles } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { TextField, SelectField } from "@/components/forms/fields";
import { profiles } from "@/lib/content/membership";

/** Affiché quand le backend n'est pas encore relié (production sans hébergement). */
export function MemberAreaUnavailable() {
  return (
    <div className="rounded-3xl border border-brand/10 bg-cloud p-8 text-center sm:p-12">
      <span className="mx-auto inline-flex size-14 items-center justify-center rounded-full bg-brand text-white">
        <Sparkles className="size-6" />
      </span>
      <h3 className="mt-5 text-xl font-bold text-brand">Bientôt disponible</h3>
      <p className="mx-auto mt-2 max-w-md text-pretty text-ink/70">
        L&apos;espace membre est en cours de mise en ligne. Il sera accessible
        très prochainement. En attendant, vous pouvez déjà adhérer au réseau.
      </p>
      <Link
        href="/adhesion"
        className="mt-6 inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 font-semibold text-white transition-colors hover:bg-accent-600"
      >
        Adhérer au REJCC
      </Link>
    </div>
  );
}

function AuthShell({ children }: { children: React.ReactNode }) {
  return (
    <div className="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
      {children}
    </div>
  );
}

export function LoginForm() {
  const { login, configured } = useAuth();
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  if (!configured) return <MemberAreaUnavailable />;

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError("");
    try {
      await login(email, password);
      router.push("/espace-membre");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur de connexion.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthShell>
      <form onSubmit={onSubmit} className="flex flex-col gap-4">
        <TextField
          label="E-mail"
          id="login-email"
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
        <TextField
          label="Mot de passe"
          id="login-password"
          type="password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
        {error && <p className="text-sm font-medium text-accent">{error}</p>}
        <button
          type="submit"
          disabled={loading}
          className="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70"
        >
          {loading ? <Loader2 className="size-4 animate-spin" /> : <Lock className="size-4" />}
          Se connecter
        </button>
      </form>
      <p className="mt-6 text-center text-sm text-ink/65">
        Pas encore de compte ?{" "}
        <Link href="/inscription" className="font-semibold text-brand hover:text-accent">
          Créer un compte
        </Link>
      </p>
    </AuthShell>
  );
}

export function RegisterForm() {
  const { register, configured } = useAuth();
  const router = useRouter();
  const [form, setForm] = useState({
    prenom: "",
    nom: "",
    email: "",
    telephone: "",
    password: "",
    profil: "",
    ville: "",
  });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  if (!configured) return <MemberAreaUnavailable />;

  const set = (k: string) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) =>
    setForm((f) => ({ ...f, [k]: e.target.value }));

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError("");
    try {
      await register(form);
      router.push("/espace-membre");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur lors de l'inscription.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthShell>
      <form onSubmit={onSubmit} className="grid gap-4 sm:grid-cols-2">
        <TextField label="Prénom" id="r-prenom" required value={form.prenom} onChange={set("prenom")} />
        <TextField label="Nom" id="r-nom" required value={form.nom} onChange={set("nom")} />
        <TextField label="E-mail" id="r-email" type="email" required value={form.email} onChange={set("email")} />
        <TextField label="Téléphone" id="r-tel" inputMode="numeric" placeholder="0700000000" required value={form.telephone} onChange={set("telephone")} />
        <SelectField label="Profil" id="r-profil" value={form.profil} onChange={set("profil")}>
          <option value="">Sélectionnez…</option>
          {profiles.map((p) => (
            <option key={p.id} value={p.id}>
              {p.label}
            </option>
          ))}
        </SelectField>
        <TextField label="Ville" id="r-ville" placeholder="Abidjan" value={form.ville} onChange={set("ville")} />
        <div className="sm:col-span-2">
          <TextField label="Mot de passe (8 caractères min.)" id="r-password" type="password" required value={form.password} onChange={set("password")} />
        </div>
        {error && <p className="text-sm font-medium text-accent sm:col-span-2">{error}</p>}
        <button
          type="submit"
          disabled={loading}
          className="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70 sm:col-span-2"
        >
          {loading ? <Loader2 className="size-4 animate-spin" /> : null}
          Créer mon compte
        </button>
      </form>
      <p className="mt-6 text-center text-sm text-ink/65">
        Déjà membre ?{" "}
        <Link href="/connexion" className="font-semibold text-brand hover:text-accent">
          Se connecter
        </Link>
      </p>
    </AuthShell>
  );
}

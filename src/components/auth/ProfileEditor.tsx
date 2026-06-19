"use client";

import { useState } from "react";
import { Check, Loader2, User } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { authApi } from "@/lib/api/client";
import { profiles } from "@/lib/content/membership";
import { DarkPage } from "@/components/member/DarkPage";

const SURF   = "rgba(8,28,80,0.72)";
const SURF2  = "rgba(12,38,100,0.80)";
const BORDER = "rgba(255,255,255,0.12)";
const TEXT   = "#F4F6F8";
const MUTED  = "rgba(244,246,248,0.60)";
const DIM    = "rgba(244,246,248,0.38)";
const RED    = "#AC0100";
const RED2   = "#E84A43";
const GREEN  = "#34D399";

/* ── Dark form field ──────────────────────────── */
function Field({
  label,
  id,
  children,
}: {
  label: string;
  id: string;
  children: React.ReactNode;
}) {
  return (
    <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
      <label
        htmlFor={id}
        style={{ fontSize: 12.5, fontWeight: 600, color: MUTED, letterSpacing: "0.02em" }}
      >
        {label}
      </label>
      {children}
    </div>
  );
}

const inputStyle: React.CSSProperties = {
  width: "100%",
  background: SURF,
  border: `1px solid ${BORDER}`,
  borderRadius: 10,
  padding: "10px 14px",
  fontSize: 13.5,
  color: TEXT,
  outline: "none",
  fontFamily: "var(--ff-sans)",
  boxSizing: "border-box",
  transition: "border-color 0.18s",
};

export function ProfileEditor() {
  const { user, token, setUser } = useAuth();
  const [form, setForm] = useState({
    prenom:       user?.prenom       ?? "",
    nom:          user?.nom          ?? "",
    telephone:    user?.telephone    ?? "",
    genre:        user?.genre        ?? "",
    ville:        user?.ville        ?? "",
    secteur:      user?.secteur      ?? "",
    profil:       user?.profil       ?? "",
    organisation: user?.organisation ?? "",
    bio:          user?.bio          ?? "",
  });
  const [status, setStatus] = useState<"idle" | "saving" | "saved" | "error">("idle");
  const [error,  setError]  = useState("");

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
    <DarkPage
      title="Mon profil"
      subtitle="Gérez vos informations personnelles et votre visibilité dans le réseau."
      icon={<User size={20} />}
    >
      <div style={{ maxWidth: 680 }}>
        {/* Avatar preview */}
        <div
          style={{
            display: "flex",
            alignItems: "center",
            gap: 18,
            marginBottom: 32,
            padding: "20px 24px",
            background: SURF,
            border: `1px solid ${BORDER}`,
            borderRadius: 16,
          }}
        >
          <div
            style={{
              width: 64,
              height: 64,
              borderRadius: "50%",
              background: "linear-gradient(135deg, #4F6FBF, #AC0100)",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: 22,
              fontWeight: 700,
              color: "#fff",
              flexShrink: 0,
              letterSpacing: "0.04em",
            }}
          >
            {form.prenom?.[0]}{form.nom?.[0]}
          </div>
          <div>
            <p style={{ fontSize: 16, fontWeight: 700, color: TEXT, margin: 0 }}>
              {form.prenom || "Prénom"} {form.nom || "Nom"}
            </p>
            <p style={{ fontSize: 13, color: MUTED, margin: "4px 0 0" }}>{user?.email}</p>
          </div>
        </div>

        <form onSubmit={onSubmit}>
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "1fr 1fr",
              gap: 16,
              background: SURF,
              border: `1px solid ${BORDER}`,
              borderRadius: 18,
              padding: "28px 28px",
            }}
            className="grid-cols-1 sm:grid-cols-2"
          >
            <Field label="Prénom" id="p-prenom">
              <input id="p-prenom" style={inputStyle} value={form.prenom} onChange={set("prenom")} />
            </Field>
            <Field label="Nom" id="p-nom">
              <input id="p-nom" style={inputStyle} value={form.nom} onChange={set("nom")} />
            </Field>
            <Field label="Téléphone" id="p-tel">
              <input id="p-tel" inputMode="numeric" style={inputStyle} value={form.telephone} onChange={set("telephone")} />
            </Field>
            <Field label="Genre" id="p-genre">
              <select
                id="p-genre"
                style={{ ...inputStyle, appearance: "none" }}
                value={form.genre}
                onChange={set("genre")}
              >
                <option value="">—</option>
                <option value="Homme">Homme</option>
                <option value="Femme">Femme</option>
              </select>
            </Field>
            <Field label="Ville" id="p-ville">
              <input id="p-ville" style={inputStyle} value={form.ville} onChange={set("ville")} placeholder="Abidjan" />
            </Field>
            <Field label="Domaine d'activité" id="p-secteur">
              <input id="p-secteur" style={inputStyle} value={form.secteur} onChange={set("secteur")} />
            </Field>
            <Field label="Profil" id="p-profil">
              <select
                id="p-profil"
                style={{ ...inputStyle, appearance: "none" }}
                value={form.profil}
                onChange={set("profil")}
              >
                <option value="">—</option>
                {profiles.map((p) => (
                  <option key={p.id} value={p.id}>{p.label}</option>
                ))}
              </select>
            </Field>
            <Field label="Entreprise / projet" id="p-org">
              <input id="p-org" style={inputStyle} value={form.organisation} onChange={set("organisation")} />
            </Field>

            {/* Bio — full width */}
            <div style={{ gridColumn: "1 / -1" }}>
              <Field label="Bio" id="p-bio">
                <textarea
                  id="p-bio"
                  style={{ ...inputStyle, minHeight: 110, resize: "vertical" }}
                  value={form.bio}
                  onChange={set("bio")}
                  placeholder="Décrivez-vous en quelques mots…"
                />
              </Field>
            </div>

            {error && (
              <p style={{ gridColumn: "1 / -1", fontSize: 13, color: RED2, margin: 0 }}>{error}</p>
            )}

            {/* Submit */}
            <div style={{ gridColumn: "1 / -1", display: "flex", alignItems: "center", gap: 12, paddingTop: 4 }}>
              <button
                type="submit"
                disabled={status === "saving"}
                style={{
                  display: "inline-flex",
                  alignItems: "center",
                  gap: 8,
                  padding: "11px 28px",
                  borderRadius: 11,
                  background: status === "saved" ? "rgba(52,211,153,0.18)" : RED,
                  border: status === "saved" ? `1px solid rgba(52,211,153,0.35)` : "none",
                  color: status === "saved" ? GREEN : "#fff",
                  fontSize: 13.5,
                  fontWeight: 700,
                  cursor: status === "saving" ? "default" : "pointer",
                  opacity: status === "saving" ? 0.7 : 1,
                  transition: "all 0.2s",
                  letterSpacing: "-0.01em",
                }}
              >
                {status === "saving" ? (
                  <Loader2 size={15} className="animate-spin" />
                ) : status === "saved" ? (
                  <Check size={15} />
                ) : null}
                {status === "saved" ? "Enregistré !" : "Enregistrer les modifications"}
              </button>
            </div>
          </div>
        </form>
      </div>
    </DarkPage>
  );
}

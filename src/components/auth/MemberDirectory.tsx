"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Loader2, MapPin, MessageCircle, Search, Users } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { authApi, type Member } from "@/lib/api/client";
import { DarkPage } from "@/components/member/DarkPage";

export function MemberDirectory() {
  const { token } = useAuth();
  const [members, setMembers] = useState<Member[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [query, setQuery] = useState("");

  useEffect(() => {
    if (!token) return;
    authApi
      .members(token)
      .then((r) => setMembers(r.members))
      .catch((e) => setError(e instanceof Error ? e.message : "Erreur"))
      .finally(() => setLoading(false));
  }, [token]);

  const q = query.trim().toLowerCase();
  const filtered = members.filter(
    (m) =>
      q === "" ||
      `${m.prenom} ${m.nom}`.toLowerCase().includes(q) ||
      (m.secteur ?? "").toLowerCase().includes(q) ||
      (m.ville ?? "").toLowerCase().includes(q),
  );

  return (
    <DarkPage
      title="Annuaire des membres"
      subtitle="Retrouvez et contactez les membres du réseau."
      icon={<Users size={20} />}
    >
      {/* Search */}
      <div style={{ position: "relative", maxWidth: 420, marginBottom: 28 }}>
        <Search
          size={15}
          style={{
            position: "absolute",
            left: 14,
            top: "50%",
            transform: "translateY(-50%)",
            color: "rgba(244,246,248,0.38)",
            pointerEvents: "none",
          }}
        />
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Rechercher (nom, domaine, ville)…"
          style={{
            width: "100%",
            background: "rgba(255,255,255,0.05)",
            border: "1px solid rgba(255,255,255,0.12)",
            borderRadius: 12,
            padding: "10px 16px 10px 40px",
            fontSize: 13.5,
            color: "#F4F6F8",
            outline: "none",
            fontFamily: "var(--ff-sans)",
            boxSizing: "border-box",
          }}
        />
      </div>

      {loading ? (
        <div style={{ display: "flex", justifyContent: "center", padding: "64px 0" }}>
          <Loader2 size={28} className="animate-spin" style={{ color: "rgba(244,246,248,0.45)" }} />
        </div>
      ) : error ? (
        <p style={{ color: "#E84A43" }}>{error}</p>
      ) : filtered.length === 0 ? (
        <p style={{ textAlign: "center", padding: "40px 0", color: "rgba(244,246,248,0.45)", fontSize: 14 }}>
          Aucun membre trouvé.
        </p>
      ) : (
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "repeat(auto-fill, minmax(260px, 1fr))",
            gap: 16,
          }}
        >
          {filtered.map((m) => (
            <article
              key={m.id}
              style={{
                background: "rgba(255,255,255,0.05)",
                border: "1px solid rgba(255,255,255,0.09)",
                borderRadius: 18,
                padding: "20px 22px",
                backdropFilter: "blur(16px)",
              }}
            >
              <div style={{ display: "flex", alignItems: "center", gap: 14 }}>
                <span
                  style={{
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: 46,
                    height: 46,
                    borderRadius: "50%",
                    background: "linear-gradient(135deg, #4F6FBF, #AC0100)",
                    fontSize: 14,
                    fontWeight: 700,
                    color: "#fff",
                    flexShrink: 0,
                    letterSpacing: "0.04em",
                  }}
                >
                  {m.prenom?.[0]}{m.nom?.[0]}
                </span>
                <div style={{ minWidth: 0 }}>
                  <p
                    style={{
                      fontSize: 14,
                      fontWeight: 700,
                      color: "#F4F6F8",
                      margin: 0,
                      overflow: "hidden",
                      textOverflow: "ellipsis",
                      whiteSpace: "nowrap",
                    }}
                  >
                    {m.prenom} {m.nom}
                  </p>
                  {m.secteur && (
                    <p style={{ fontSize: 12.5, color: "rgba(244,246,248,0.55)", margin: "3px 0 0", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                      {m.secteur}
                    </p>
                  )}
                </div>
              </div>

              {(m.ville || m.organisation) && (
                <p
                  style={{
                    marginTop: 14,
                    display: "flex",
                    alignItems: "center",
                    gap: 5,
                    fontSize: 12,
                    color: "rgba(244,246,248,0.40)",
                  }}
                >
                  <MapPin size={12} /> {[m.ville, m.organisation].filter(Boolean).join(" · ")}
                </p>
              )}

              <Link
                href={`/espace-membre/messagerie?to=${m.id}`}
                style={{
                  marginTop: 16,
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  gap: 7,
                  padding: "9px 0",
                  borderRadius: 10,
                  background: "rgba(79,111,191,0.14)",
                  border: "1px solid rgba(79,111,191,0.24)",
                  fontSize: 13,
                  fontWeight: 600,
                  color: "#9DB2EE",
                  textDecoration: "none",
                  transition: "all 0.18s",
                }}
              >
                <MessageCircle size={13} /> Envoyer un message
              </Link>
            </article>
          ))}
        </div>
      )}
    </DarkPage>
  );
}

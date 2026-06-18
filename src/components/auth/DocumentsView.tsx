"use client";

import { useEffect, useState } from "react";
import { Download, FileText, FolderOpen, Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { memberApi, type DocItem } from "@/lib/api/client";
import { DarkPage } from "@/components/member/DarkPage";

const SURF   = "rgba(255,255,255,0.05)";
const SURF2  = "rgba(255,255,255,0.085)";
const BORDER = "rgba(255,255,255,0.09)";
const TEXT   = "#F4F6F8";
const MUTED  = "rgba(244,246,248,0.60)";
const DIM    = "rgba(244,246,248,0.38)";
const BLUE2  = "#9DB2EE";
const RED2   = "#E84A43";

export function DocumentsView() {
  const { token } = useAuth();
  const [docs, setDocs] = useState<DocItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    memberApi
      .documents(token)
      .then((r) => setDocs(r.documents))
      .finally(() => setLoading(false));
  }, [token]);

  const categories = [...new Set(docs.map((d) => d.category))];

  return (
    <DarkPage
      title="Documents & ressources"
      subtitle="Guides, chartes et ressources mis à disposition par le réseau."
      icon={<FolderOpen size={20} />}
    >
      {loading ? (
        <div style={{ display: "flex", justifyContent: "center", padding: "64px 0" }}>
          <Loader2 size={28} className="animate-spin" style={{ color: "rgba(244,246,248,0.45)" }} />
        </div>
      ) : docs.length === 0 ? (
        <div style={{ textAlign: "center", padding: "48px 0" }}>
          <FolderOpen size={36} style={{ color: DIM, margin: "0 auto 14px" }} />
          <p style={{ color: MUTED, fontSize: 14 }}>Aucun document disponible.</p>
        </div>
      ) : (
        <div style={{ display: "flex", flexDirection: "column", gap: 36, maxWidth: 820 }}>
          {categories.map((cat) => (
            <div key={cat}>
              <p
                style={{
                  fontSize: 10.5,
                  fontWeight: 700,
                  letterSpacing: "0.14em",
                  textTransform: "uppercase",
                  color: DIM,
                  marginBottom: 14,
                }}
              >
                {cat}
              </p>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(300px, 1fr))", gap: 12 }}>
                {docs
                  .filter((d) => d.category === cat)
                  .map((d) => (
                    <a
                      key={d.id}
                      href={d.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: 14,
                        background: SURF,
                        border: `1px solid ${BORDER}`,
                        borderRadius: 16,
                        padding: "16px 18px",
                        textDecoration: "none",
                        transition: "transform 0.18s, box-shadow 0.18s",
                        backdropFilter: "blur(16px)",
                      }}
                      onMouseEnter={(e) => {
                        (e.currentTarget as HTMLAnchorElement).style.transform = "translateY(-2px)";
                        (e.currentTarget as HTMLAnchorElement).style.boxShadow = "0 18px 40px -18px rgba(0,0,0,0.5)";
                      }}
                      onMouseLeave={(e) => {
                        (e.currentTarget as HTMLAnchorElement).style.transform = "translateY(0)";
                        (e.currentTarget as HTMLAnchorElement).style.boxShadow = "none";
                      }}
                    >
                      <span
                        style={{
                          display: "inline-flex",
                          alignItems: "center",
                          justifyContent: "center",
                          width: 42,
                          height: 42,
                          borderRadius: 11,
                          background: "rgba(232,74,67,0.13)",
                          flexShrink: 0,
                        }}
                      >
                        <FileText size={18} style={{ color: RED2 }} />
                      </span>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <p style={{ fontSize: 13.5, fontWeight: 600, color: TEXT, margin: 0, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                          {d.title}
                        </p>
                        {d.description && (
                          <p style={{ fontSize: 12, color: MUTED, margin: "3px 0 0", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                            {d.description}
                          </p>
                        )}
                      </div>
                      <span
                        style={{
                          display: "inline-flex",
                          alignItems: "center",
                          gap: 5,
                          fontSize: 12,
                          fontWeight: 600,
                          color: BLUE2,
                          flexShrink: 0,
                        }}
                      >
                        {d.size} <Download size={13} />
                      </span>
                    </a>
                  ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </DarkPage>
  );
}

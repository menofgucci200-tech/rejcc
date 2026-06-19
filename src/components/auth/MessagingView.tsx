"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, MessageCircle, Send, Loader2, Users } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { memberApi, type Conversation, type ChatMessage } from "@/lib/api/client";
import { DarkPage } from "@/components/member/DarkPage";

const SURF   = "rgba(8,28,80,0.72)";
const SURF2  = "rgba(12,38,100,0.80)";
const BORDER = "rgba(255,255,255,0.09)";
const TEXT   = "#F4F6F8";
const MUTED  = "rgba(244,246,248,0.60)";
const DIM    = "rgba(244,246,248,0.38)";
const BLUE   = "#4F6FBF";
const BLUE2  = "#9DB2EE";
const RED    = "#AC0100";
const RED2   = "#E84A43";

function time(s: string) {
  return new Date(s).toLocaleString("fr-FR", {
    day: "2-digit", month: "2-digit", hour: "2-digit", minute: "2-digit",
  });
}

export function MessagingView() {
  const { token, user } = useAuth();
  const [convos, setConvos]     = useState<Conversation[]>([]);
  const [activeId, setActiveId] = useState<number | null>(null);
  const [partner, setPartner]   = useState<{ prenom: string; nom: string } | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [body, setBody]         = useState("");
  const [loadingConvos, setLoadingConvos] = useState(true);
  const [loadingThread, setLoadingThread] = useState(false);
  const me = user?.id;

  function loadConvos() {
    if (!token) return;
    memberApi.conversations(token)
      .then((r) => setConvos(r.conversations))
      .finally(() => setLoadingConvos(false));
  }

  useEffect(loadConvos, [token]);

  useEffect(() => {
    if (!token) return;
    const to = new URLSearchParams(window.location.search).get("to");
    if (to) openThread(Number(to));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token]);

  async function openThread(uid: number) {
    if (!token) return;
    setActiveId(uid);
    setLoadingThread(true);
    try {
      const r = await memberApi.thread(token, uid);
      setPartner(r.partner);
      setMessages(r.messages);
    } finally {
      setLoadingThread(false);
    }
  }

  async function onSend(e: React.FormEvent) {
    e.preventDefault();
    if (!token || !activeId || !body.trim()) return;
    const text = body.trim();
    setBody("");
    await memberApi.sendMessage(token, activeId, text);
    await openThread(activeId);
    loadConvos();
  }

  const cardStyle: React.CSSProperties = {
    background: SURF,
    border: `1px solid ${BORDER}`,
    borderRadius: 18,
    overflow: "hidden",
  };

  return (
    <DarkPage
      title="Messagerie"
      subtitle="Échangez en privé avec les membres du réseau."
      icon={<MessageCircle size={20} />}
    >
      <div
        className="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-4"
        style={{ height: "calc(100vh - 280px)", minHeight: 420 }}
      >
        {/* Conversations list */}
        <aside
          style={{
            ...cardStyle,
            display: activeId ? undefined : "block",
          }}
          className={activeId ? "hidden lg:block" : "block"}
        >
          {loadingConvos ? (
            <div style={{ display: "flex", justifyContent: "center", padding: "48px 0" }}>
              <Loader2 size={24} className="animate-spin" style={{ color: "rgba(244,246,248,0.45)" }} />
            </div>
          ) : convos.length === 0 ? (
            <div style={{ padding: "40px 24px", textAlign: "center" }}>
              <MessageCircle size={32} style={{ color: DIM, margin: "0 auto 12px" }} />
              <p style={{ color: MUTED, fontSize: 13.5, marginBottom: 16 }}>
                Aucune conversation. Démarrez-en une depuis l'annuaire.
              </p>
              <Link
                href="/espace-membre/annuaire"
                style={{
                  display: "inline-flex",
                  alignItems: "center",
                  gap: 6,
                  padding: "8px 16px",
                  borderRadius: 10,
                  background: "rgba(79,111,191,0.14)",
                  border: `1px solid rgba(79,111,191,0.24)`,
                  color: BLUE2,
                  fontSize: 12.5,
                  fontWeight: 600,
                  textDecoration: "none",
                }}
              >
                <Users size={13} /> Voir l'annuaire
              </Link>
            </div>
          ) : (
            <ul style={{ listStyle: "none", margin: 0, padding: "8px 0" }}>
              {convos.map((c) => (
                <li key={c.user_id}>
                  <button
                    onClick={() => openThread(c.user_id)}
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: 12,
                      width: "100%",
                      padding: "12px 16px",
                      background: activeId === c.user_id ? SURF2 : "transparent",
                      border: "none",
                      cursor: "pointer",
                      textAlign: "left",
                      transition: "background 0.18s",
                    }}
                  >
                    <span
                      style={{
                        display: "inline-flex",
                        alignItems: "center",
                        justifyContent: "center",
                        width: 42,
                        height: 42,
                        borderRadius: "50%",
                        background: `linear-gradient(135deg, ${BLUE}, ${RED})`,
                        fontSize: 13,
                        fontWeight: 700,
                        color: "#fff",
                        flexShrink: 0,
                        letterSpacing: "0.04em",
                      }}
                    >
                      {c.prenom?.[0]}{c.nom?.[0]}
                    </span>
                    <span style={{ flex: 1, minWidth: 0 }}>
                      <span style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 8 }}>
                        <span style={{ fontSize: 13.5, fontWeight: 600, color: TEXT, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                          {c.prenom} {c.nom}
                        </span>
                        {c.unread > 0 && (
                          <span
                            style={{
                              display: "inline-flex",
                              alignItems: "center",
                              justifyContent: "center",
                              minWidth: 20,
                              height: 20,
                              borderRadius: "50%",
                              background: RED2,
                              fontSize: 10.5,
                              fontWeight: 700,
                              color: "#fff",
                              flexShrink: 0,
                              padding: "0 4px",
                            }}
                          >
                            {c.unread}
                          </span>
                        )}
                      </span>
                      <span style={{ display: "block", fontSize: 12, color: MUTED, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", marginTop: 2 }}>
                        {c.last}
                      </span>
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </aside>

        {/* Message thread */}
        <section
          style={{
            ...cardStyle,
            display: "flex",
            flexDirection: "column",
          }}
          className={activeId ? "flex" : "hidden lg:flex"}
        >
          {!activeId ? (
            <div style={{ flex: 1, display: "flex", alignItems: "center", justifyContent: "center", padding: 40, textAlign: "center", color: MUTED, fontSize: 14 }}>
              Sélectionnez une conversation pour afficher les messages.
            </div>
          ) : (
            <>
              {/* Thread header */}
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 12,
                  borderBottom: `1px solid ${BORDER}`,
                  padding: "14px 18px",
                  flexShrink: 0,
                }}
              >
                <button
                  onClick={() => setActiveId(null)}
                  className="lg:hidden"
                  style={{ background: "none", border: "none", color: MUTED, cursor: "pointer", padding: 4 }}
                  aria-label="Retour"
                >
                  <ArrowLeft size={18} />
                </button>
                <span
                  style={{
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: 38,
                    height: 38,
                    borderRadius: "50%",
                    background: `linear-gradient(135deg, ${BLUE}, ${RED})`,
                    fontSize: 12,
                    fontWeight: 700,
                    color: "#fff",
                    flexShrink: 0,
                  }}
                >
                  {partner?.prenom?.[0]}{partner?.nom?.[0]}
                </span>
                <p style={{ fontSize: 14, fontWeight: 700, color: TEXT, margin: 0 }}>
                  {partner ? `${partner.prenom} ${partner.nom}` : ""}
                </p>
              </div>

              {/* Messages */}
              <div style={{ flex: 1, overflowY: "auto", display: "flex", flexDirection: "column", gap: 10, padding: "16px 18px" }}>
                {loadingThread ? (
                  <div style={{ flex: 1, display: "flex", alignItems: "center", justifyContent: "center" }}>
                    <Loader2 size={24} className="animate-spin" style={{ color: "rgba(244,246,248,0.45)" }} />
                  </div>
                ) : (
                  messages.map((m) => {
                    const mine = m.sender_id === me;
                    return (
                      <div key={m.id} style={{ display: "flex", flexDirection: "column", alignItems: mine ? "flex-end" : "flex-start" }}>
                        <div
                          style={{
                            maxWidth: "78%",
                            borderRadius: 14,
                            padding: "10px 14px",
                            fontSize: 13.5,
                            lineHeight: 1.5,
                            background: mine ? `linear-gradient(135deg, ${BLUE}, #031D59)` : SURF2,
                            color: mine ? "#fff" : TEXT,
                            border: mine ? "none" : `1px solid ${BORDER}`,
                          }}
                        >
                          {m.body}
                        </div>
                        <span style={{ marginTop: 4, padding: "0 4px", fontSize: 11, color: DIM }}>
                          {time(m.created_at)}
                        </span>
                      </div>
                    );
                  })
                )}
              </div>

              {/* Send form */}
              <form
                onSubmit={onSend}
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: 10,
                  borderTop: `1px solid ${BORDER}`,
                  padding: "12px 14px",
                  flexShrink: 0,
                }}
              >
                <input
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                  placeholder="Votre message…"
                  style={{
                    flex: 1,
                    background: SURF2,
                    border: `1px solid ${BORDER}`,
                    borderRadius: 10,
                    padding: "10px 16px",
                    fontSize: 13.5,
                    color: TEXT,
                    outline: "none",
                    fontFamily: "var(--ff-sans)",
                    minWidth: 0,
                  }}
                />
                <button
                  type="submit"
                  aria-label="Envoyer"
                  style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: 42,
                    height: 42,
                    borderRadius: 11,
                    background: RED,
                    border: "none",
                    color: "#fff",
                    cursor: "pointer",
                    flexShrink: 0,
                    transition: "background 0.18s",
                  }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "#E84A43"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = RED; }}
                >
                  <Send size={15} />
                </button>
              </form>
            </>
          )}
        </section>
      </div>
    </DarkPage>
  );
}

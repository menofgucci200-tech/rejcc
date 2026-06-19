"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import {
  Users, MessageCircle, FolderOpen, ArrowRight, Download, ExternalLink,
  MapPin, TrendingUp, Globe,
} from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { memberApi, authApi, type Conversation, type DocItem, type Member } from "@/lib/api/client";

/* ── Design tokens ────────────────────────────── */
const SURF    = "rgba(8,28,80,0.72)";
const SURF2   = "rgba(12,38,100,0.80)";
const BORDER  = "rgba(255,255,255,0.09)";
const TEXT    = "#F4F6F8";
const MUTED   = "rgba(244,246,248,0.62)";
const DIM     = "rgba(244,246,248,0.38)";
const RED     = "#AC0100";
const RED2    = "#E84A43";
const BLUE    = "#4F6FBF";
const BLUE2   = "#9DB2EE";
const GREEN   = "#34D399";
const GOLD    = "#F2A33C";

/* ── Card shell ───────────────────────────────── */
function Card({
  children, style, className,
}: {
  children: React.ReactNode;
  style?: React.CSSProperties;
  className?: string;
}) {
  return (
    <div
      className={className}
      style={{
        background: SURF,
        border: `1px solid ${BORDER}`,
        borderRadius: 20,
        overflow: "hidden",
        ...style,
      }}
    >
      {children}
    </div>
  );
}

/* ── Section header ───────────────────────────── */
function SectionTitle({
  title, subtitle, action, href,
}: {
  title: string; subtitle?: string;
  action?: string; href?: string;
}) {
  return (
    <div style={{ display: "flex", alignItems: "flex-end", justifyContent: "space-between", marginBottom: 18 }}>
      <div>
        <h2 style={{ fontSize: 17, fontWeight: 700, color: TEXT, margin: 0, letterSpacing: "-0.02em" }}>
          {title}
        </h2>
        {subtitle && (
          <p style={{ fontSize: 13, color: MUTED, marginTop: 3 }}>{subtitle}</p>
        )}
      </div>
      {action && href && (
        <Link
          href={href}
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: 5,
            fontSize: 12.5,
            fontWeight: 600,
            color: BLUE2,
            textDecoration: "none",
            padding: "5px 12px",
            borderRadius: 8,
            background: "rgba(79,111,191,0.12)",
            border: `1px solid rgba(79,111,191,0.22)`,
            transition: "all 0.18s",
          }}
        >
          {action} <ArrowRight size={12} />
        </Link>
      )}
    </div>
  );
}

/* ── Animated counter ─────────────────────────── */
function Counter({ to, suffix = "" }: { to: number; suffix?: string }) {
  const [val, setVal] = useState(0);
  const ref = useRef<HTMLSpanElement>(null);
  const started = useRef(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([e]) => {
        if (e.isIntersecting && !started.current) {
          started.current = true;
          const dur = 1200;
          const start = performance.now();
          function tick(now: number) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            setVal(Math.round(eased * to));
            if (p < 1) requestAnimationFrame(tick);
          }
          requestAnimationFrame(tick);
        }
      },
      { threshold: 0.4 },
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, [to]);

  return <span ref={ref}>{val}{suffix}</span>;
}

/* ── Reveal wrapper ───────────────────────────── */
function Reveal({
  children, delay = 0,
}: {
  children: React.ReactNode;
  delay?: number;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const [vis, setVis] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([e]) => { if (e.isIntersecting) setVis(true); },
      { threshold: 0.1 },
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);

  return (
    <div
      ref={ref}
      style={{
        opacity: vis ? 1 : 0,
        transform: vis ? "translateY(0)" : "translateY(22px)",
        transition: `opacity 0.55s ease ${delay}ms, transform 0.55s ease ${delay}ms`,
      }}
    >
      {children}
    </div>
  );
}

/* ── Progress ring ────────────────────────────── */
function Ring({ pct }: { pct: number }) {
  const r = 48;
  const circ = 2 * Math.PI * r;
  const dash = (pct / 100) * circ;
  return (
    <svg width={120} height={120} viewBox="0 0 120 120" style={{ transform: "rotate(-90deg)" }}>
      <circle cx={60} cy={60} r={r} fill="none" stroke={SURF2} strokeWidth={9} />
      <circle
        cx={60} cy={60} r={r} fill="none"
        stroke="url(#ringGrad)"
        strokeWidth={9}
        strokeLinecap="round"
        strokeDasharray={`${dash} ${circ - dash}`}
        style={{ transition: "stroke-dasharray 1.2s cubic-bezier(0.22,1,0.36,1) 0.4s" }}
      />
      <defs>
        <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stopColor={BLUE} />
          <stop offset="100%" stopColor={RED2} />
        </linearGradient>
      </defs>
    </svg>
  );
}

/* ── Upcoming events (static) ─────────────────── */
const events = [
  {
    date: "28 Juin",
    title: "Atelier Entrepreneuriat",
    type: "Atelier",
    color: BLUE,
    location: "Abidjan, Plateau",
  },
  {
    date: "5 Juil.",
    title: "Masterclass Leadership",
    type: "Formation",
    color: GOLD,
    location: "Présentiel + Zoom",
  },
  {
    date: "12 Juil.",
    title: "Conférence Startup CI 2026",
    type: "Conférence",
    color: GREEN,
    location: "CCIAD, Abidjan",
  },
  {
    date: "19 Juil.",
    title: "Networking Catholiques Actifs",
    type: "Networking",
    color: "#A78BFA",
    location: "Hôtel Ivoire",
  },
];

/* ── Network stats (static) ──────────────────── */
const networkStats = [
  { icon: Users,     value: 250, suffix: "+", label: "Membres actifs",        color: BLUE2  },
  { icon: Globe,     value: 12,  suffix: "",  label: "Secteurs représentés",  color: GREEN  },
  { icon: MapPin,    value: 8,   suffix: "",  label: "Régions couvertes",      color: GOLD   },
  { icon: TrendingUp,value: 36,  suffix: "+", label: "Événements organisés",  color: "#A78BFA" },
];

/* ── Profile completion ───────────────────────── */
function profileCompletion(user: {
  prenom?: string | null; nom?: string | null; email?: string | null;
  telephone?: string | null; ville?: string | null; secteur?: string | null;
  profil?: string | null; bio?: string | null;
}) {
  const fields = [user.prenom, user.nom, user.email, user.telephone, user.ville, user.secteur, user.profil, user.bio];
  const filled = fields.filter(Boolean).length;
  return Math.round((filled / fields.length) * 100);
}

/* ── Main component ───────────────────────────── */
export function MemberDashboard() {
  const { user, token } = useAuth();
  const [convos, setConvos]   = useState<Conversation[]>([]);
  const [docs, setDocs]       = useState<DocItem[]>([]);
  const [members, setMembers] = useState<Member[]>([]);

  useEffect(() => {
    if (!token) return;
    memberApi.conversations(token).then((r) => setConvos(r.conversations)).catch(() => {});
    memberApi.documents(token).then((r) => setDocs(r.documents.slice(0, 4))).catch(() => {});
    authApi.members(token).then((r) => setMembers(r.members.slice(0, 4))).catch(() => {});
  }, [token]);

  if (!user) return null;

  const completion = profileCompletion(user);
  const unreadMsgs = convos.reduce((s, c) => s + (c.unread || 0), 0);

  const pad = { padding: "30px 32px 60px" };

  return (
    <div style={pad}>

      {/* ── Stats strip ─────────────────────────── */}
      <Reveal>
        <div
          className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8"
        >
          {networkStats.map((s, i) => (
            <Card key={i} style={{ padding: "20px 22px" }}>
              <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 12 }}>
                <span
                  style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: 34,
                    height: 34,
                    borderRadius: 10,
                    background: `color-mix(in srgb, ${s.color} 18%, transparent)`,
                  }}
                >
                  <s.icon size={16} style={{ color: s.color }} />
                </span>
                <p style={{ fontSize: 11.5, color: MUTED, margin: 0, lineHeight: 1.3 }}>{s.label}</p>
              </div>
              <p
                style={{
                  fontFamily: "var(--ff-display)",
                  fontSize: 28,
                  color: TEXT,
                  margin: 0,
                  letterSpacing: "-0.02em",
                  lineHeight: 1,
                }}
              >
                <Counter to={s.value} suffix={s.suffix} />
              </p>
            </Card>
          ))}
        </div>
      </Reveal>

      {/* ── Hero ────────────────────────────────── */}
      <Reveal delay={80}>
        <div
          className="grid grid-cols-1 lg:grid-cols-[1.08fr_1fr] gap-5 mb-8"
        >
          {/* Progress card */}
          <Card style={{ padding: "28px 30px" }}>
            <div style={{ display: "flex", alignItems: "flex-start", gap: 24 }}>
              {/* Ring */}
              <div style={{ position: "relative", flexShrink: 0 }}>
                <Ring pct={completion} />
                <div
                  style={{
                    position: "absolute",
                    inset: 0,
                    display: "flex",
                    flexDirection: "column",
                    alignItems: "center",
                    justifyContent: "center",
                  }}
                >
                  <p
                    style={{
                      fontFamily: "var(--ff-display)",
                      fontSize: 24,
                      color: TEXT,
                      margin: 0,
                      lineHeight: 1,
                    }}
                  >
                    {completion}%
                  </p>
                  <p style={{ fontSize: 9.5, color: DIM, margin: "3px 0 0", letterSpacing: "0.05em" }}>
                    PROFIL
                  </p>
                </div>
              </div>

              {/* Details */}
              <div style={{ flex: 1, minWidth: 0 }}>
                <h3
                  style={{
                    fontFamily: "var(--ff-serif)",
                    fontSize: 20,
                    fontStyle: "italic",
                    color: TEXT,
                    margin: "0 0 6px",
                  }}
                >
                  Votre réseau REJCC
                </h3>
                <p style={{ fontSize: 13, color: MUTED, margin: "0 0 18px", lineHeight: 1.5 }}>
                  Complétez votre profil pour maximiser vos opportunités de networking.
                </p>
                {/* Mini stats */}
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
                  {[
                    { label: "Profil complété", val: `${completion}%`,   color: BLUE2  },
                    { label: "Messages",         val: unreadMsgs > 0 ? `${unreadMsgs} non lus` : "À jour", color: unreadMsgs > 0 ? GOLD : GREEN },
                    { label: "Documents",        val: `${docs.length}`,   color: RED2   },
                    { label: "Membres réseau",   val: "250+",             color: GREEN  },
                  ].map((stat) => (
                    <div
                      key={stat.label}
                      style={{
                        background: SURF2,
                        border: `1px solid ${BORDER}`,
                        borderRadius: 12,
                        padding: "10px 14px",
                      }}
                    >
                      <p style={{ fontSize: 16, fontWeight: 700, color: stat.color, margin: 0, fontFamily: "var(--ff-display)" }}>
                        {stat.val}
                      </p>
                      <p style={{ fontSize: 11, color: DIM, margin: "2px 0 0" }}>{stat.label}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </Card>

          {/* Continue card */}
          <Card
            style={{
              background: `linear-gradient(145deg, rgba(79,111,191,0.25), rgba(172,1,0,0.12)), ${SURF}`,
              padding: "28px 28px",
              display: "flex",
              flexDirection: "column",
            }}
          >
            <span
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: 6,
                background: "rgba(172,1,0,0.18)",
                border: "1px solid rgba(172,1,0,0.3)",
                borderRadius: 20,
                padding: "5px 12px",
                fontSize: 11,
                fontWeight: 600,
                color: RED2,
                marginBottom: 16,
                letterSpacing: "0.04em",
                textTransform: "uppercase",
                width: "fit-content",
              }}
            >
              <span
                style={{
                  width: 6,
                  height: 6,
                  borderRadius: "50%",
                  background: RED2,
                  animation: "aurora 1.5s ease-in-out infinite",
                }}
              />
              Prochain événement
            </span>
            <h3
              style={{
                fontFamily: "var(--ff-serif)",
                fontSize: 22,
                fontStyle: "italic",
                color: TEXT,
                margin: "0 0 10px",
                lineHeight: 1.3,
              }}
            >
              Atelier Entrepreneuriat
            </h3>
            <p style={{ fontSize: 13, color: MUTED, margin: "0 0 6px" }}>
              📍 Abidjan, Plateau · 28 Juin 2026
            </p>
            <p style={{ fontSize: 13, color: MUTED, flex: 1, lineHeight: 1.5 }}>
              Rejoignez les membres du REJCC pour un atelier pratique sur le lancement d'activité et le financement entrepreneurial.
            </p>
            <div style={{ marginTop: 20, display: "flex", gap: 10 }}>
              <button
                style={{
                  flex: 1,
                  padding: "10px 18px",
                  borderRadius: 10,
                  background: "#fff",
                  border: "none",
                  color: "#031D59",
                  fontSize: 13,
                  fontWeight: 700,
                  cursor: "pointer",
                  letterSpacing: "-0.01em",
                }}
              >
                S'inscrire
              </button>
              <button
                style={{
                  padding: "10px 14px",
                  borderRadius: 10,
                  background: SURF2,
                  border: `1px solid ${BORDER}`,
                  color: MUTED,
                  fontSize: 13,
                  cursor: "pointer",
                }}
              >
                <ExternalLink size={14} />
              </button>
            </div>
          </Card>
        </div>
      </Reveal>

      {/* ── Membres + Événements ────────────────── */}
      <Reveal delay={160}>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-10">
          {/* Members preview */}
          <section>
            <SectionTitle
              title="Membres du réseau"
              subtitle="Connectez-vous avec vos pairs"
              action="Voir l'annuaire"
              href="/espace-membre/annuaire"
            />
            <Card>
              {members.length === 0 ? (
                <div style={{ padding: "32px 24px", textAlign: "center" }}>
                  <Users size={32} style={{ color: DIM, margin: "0 auto 12px" }} />
                  <p style={{ color: MUTED, fontSize: 13 }}>Aucun membre trouvé</p>
                </div>
              ) : (
                <ul style={{ listStyle: "none", margin: 0, padding: 0 }}>
                  {members.map((m, i) => (
                    <li
                      key={m.id}
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: 12,
                        padding: "14px 18px",
                        borderBottom: i < members.length - 1 ? `1px solid ${BORDER}` : "none",
                      }}
                    >
                      <div
                        style={{
                          width: 40,
                          height: 40,
                          borderRadius: "50%",
                          background: `linear-gradient(135deg, ${BLUE}, ${RED})`,
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          fontSize: 13,
                          fontWeight: 700,
                          color: "#fff",
                          flexShrink: 0,
                          letterSpacing: "0.04em",
                        }}
                      >
                        {m.prenom?.[0]}{m.nom?.[0]}
                      </div>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <p style={{ fontSize: 13.5, fontWeight: 600, color: TEXT, margin: 0, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                          {m.prenom} {m.nom}
                        </p>
                        {(m.secteur || m.ville) && (
                          <p style={{ fontSize: 12, color: DIM, margin: "2px 0 0", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                            {[m.secteur, m.ville].filter(Boolean).join(" · ")}
                          </p>
                        )}
                      </div>
                      <Link
                        href={`/espace-membre/messagerie?to=${m.id}`}
                        style={{
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          width: 30,
                          height: 30,
                          borderRadius: 8,
                          background: SURF2,
                          border: `1px solid ${BORDER}`,
                          color: MUTED,
                          textDecoration: "none",
                          flexShrink: 0,
                          transition: "all 0.18s",
                        }}
                        title={`Envoyer un message à ${m.prenom}`}
                      >
                        <MessageCircle size={13} />
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </Card>
          </section>

          {/* Events timeline */}
          <section>
            <SectionTitle
              title="Événements à venir"
              subtitle="Restez connecté à l'agenda du réseau"
              action="Tous les événements"
              href="#"
            />
            <Card>
              <ul style={{ listStyle: "none", margin: 0, padding: "8px 0" }}>
                {events.map((ev, i) => (
                  <li
                    key={i}
                    style={{
                      display: "flex",
                      alignItems: "flex-start",
                      gap: 14,
                      padding: "12px 18px",
                    }}
                  >
                    {/* Date badge */}
                    <div
                      style={{
                        display: "flex",
                        flexDirection: "column",
                        alignItems: "center",
                        width: 40,
                        flexShrink: 0,
                      }}
                    >
                      <div
                        style={{
                          width: 8,
                          height: 8,
                          borderRadius: "50%",
                          background: ev.color,
                          marginBottom: 6,
                          boxShadow: `0 0 10px ${ev.color}80`,
                        }}
                      />
                      {i < events.length - 1 && (
                        <div
                          style={{
                            width: 1,
                            flex: 1,
                            minHeight: 30,
                            background: `linear-gradient(to bottom, ${ev.color}40, transparent)`,
                          }}
                        />
                      )}
                    </div>

                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 4 }}>
                        <span
                          style={{
                            fontSize: 10.5,
                            fontWeight: 600,
                            color: ev.color,
                            background: `color-mix(in srgb, ${ev.color} 15%, transparent)`,
                            borderRadius: 20,
                            padding: "2px 8px",
                            letterSpacing: "0.04em",
                          }}
                        >
                          {ev.type}
                        </span>
                        <span style={{ fontSize: 11, color: DIM }}>{ev.date}</span>
                      </div>
                      <p style={{ fontSize: 13.5, fontWeight: 600, color: TEXT, margin: "0 0 3px" }}>
                        {ev.title}
                      </p>
                      <p style={{ fontSize: 12, color: DIM, margin: 0 }}>
                        📍 {ev.location}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            </Card>
          </section>
        </div>
      </Reveal>

      {/* ── Documents récents ───────────────────── */}
      {docs.length > 0 && (
        <Reveal delay={200}>
          <section style={{ marginBottom: 40 }}>
            <SectionTitle
              title="Documents récents"
              subtitle="Dernières ressources partagées"
              action="Tous les documents"
              href="/espace-membre/documents"
            />
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fill, minmax(220px, 1fr))",
                gap: 14,
              }}
            >
              {docs.map((doc) => (
                <Card key={doc.id} style={{ padding: "18px 20px" }}>
                  <div
                    style={{
                      width: 36,
                      height: 36,
                      borderRadius: 10,
                      background: "rgba(232,74,67,0.14)",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      marginBottom: 12,
                    }}
                  >
                    <FolderOpen size={16} style={{ color: RED2 }} />
                  </div>
                  <p style={{ fontSize: 13.5, fontWeight: 600, color: TEXT, margin: "0 0 4px", lineHeight: 1.3 }}>
                    {doc.title}
                  </p>
                  <p style={{ fontSize: 11.5, color: DIM, margin: "0 0 14px" }}>{doc.category}</p>
                  <a
                    href={doc.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: "inline-flex",
                      alignItems: "center",
                      gap: 6,
                      fontSize: 12,
                      fontWeight: 600,
                      color: BLUE2,
                      textDecoration: "none",
                      padding: "6px 12px",
                      borderRadius: 8,
                      background: "rgba(79,111,191,0.12)",
                      border: "1px solid rgba(79,111,191,0.2)",
                    }}
                  >
                    <Download size={12} /> Télécharger
                  </a>
                </Card>
              ))}
            </div>
          </section>
        </Reveal>
      )}

      {/* ── Profil à compléter (CTA) ─────────────── */}
      {completion < 80 && (
        <Reveal delay={240}>
          <Card
            style={{
              background: `linear-gradient(135deg, rgba(79,111,191,0.18), rgba(172,1,0,0.1)), ${SURF}`,
              padding: "28px 32px",
              display: "flex",
              alignItems: "center",
              justifyContent: "space-between",
              gap: 20,
              flexWrap: "wrap",
            }}
          >
            <div>
              <h3 style={{ fontFamily: "var(--ff-serif)", fontStyle: "italic", fontSize: 20, color: TEXT, margin: "0 0 8px" }}>
                Complétez votre profil
              </h3>
              <p style={{ fontSize: 13.5, color: MUTED, margin: 0 }}>
                Votre profil est complété à {completion}%. Ajoutez vos informations pour améliorer votre visibilité dans le réseau.
              </p>
            </div>
            <Link
              href="/espace-membre/profil"
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: 8,
                padding: "11px 22px",
                borderRadius: 12,
                background: "#fff",
                color: "#031D59",
                fontSize: 13.5,
                fontWeight: 700,
                textDecoration: "none",
                flexShrink: 0,
                letterSpacing: "-0.01em",
              }}
            >
              Compléter mon profil <ArrowRight size={14} />
            </Link>
          </Card>
        </Reveal>
      )}
    </div>
  );
}

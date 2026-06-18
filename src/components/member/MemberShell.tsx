"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";
import Link from "next/link";
import {
  LayoutDashboard, Users, MessageCircle, Bell,
  FolderOpen, Calendar, User, Settings, LogOut,
  Menu, X, Search, ChevronRight, Loader2, AlertCircle,
} from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";

/* ── Design tokens ────────────────────────────── */
const BG       = "radial-gradient(165deg, #02163f 0%, #031D59 48%, #040f2e 100%)";
const SURF     = "rgba(255,255,255,0.05)";
const SURF2    = "rgba(255,255,255,0.085)";
const BORDER   = "1px solid rgba(255,255,255,0.09)";
const BORDER2  = "1px solid rgba(255,255,255,0.16)";
const TEXT     = "#F4F6F8";
const MUTED    = "rgba(244,246,248,0.60)";
const DIM      = "rgba(244,246,248,0.38)";
const RED      = "#AC0100";
const RED2     = "#E84A43";
const BLUE     = "#4F6FBF";
const GREEN    = "#34D399";

/* ── Nav items ────────────────────────────────── */
const navItems = [
  { icon: LayoutDashboard, label: "Accueil",       href: "/espace-membre",              exact: true  },
  { icon: Users,           label: "Annuaire",      href: "/espace-membre/annuaire",     exact: false },
  { icon: MessageCircle,   label: "Messagerie",    href: "/espace-membre/messagerie",   exact: false },
  { icon: Bell,            label: "Notifications", href: "/espace-membre/notifications",exact: false },
  { icon: FolderOpen,      label: "Documents",     href: "/espace-membre/documents",    exact: false },
  { icon: Calendar,        label: "Événements",    href: "#",                           exact: true, disabled: true },
];

/* ── Single nav item ──────────────────────────── */
function NavItem({
  icon: Icon, label, href, active, rail, disabled = false, onClick,
}: {
  icon: React.ComponentType<{ size?: number; style?: React.CSSProperties }>;
  label: string; href: string; active: boolean; rail: boolean;
  disabled?: boolean; onClick?: () => void;
}) {
  const inner = (
    <span
      style={{
        display: "flex",
        alignItems: "center",
        gap: rail ? 0 : 12,
        justifyContent: rail ? "center" : "flex-start",
        padding: rail ? "10px 0" : "9px 12px",
        borderRadius: 12,
        background: active ? SURF2 : "transparent",
        borderLeft: active ? `3px solid ${RED}` : "3px solid transparent",
        transition: "all 0.18s",
        cursor: disabled ? "default" : "pointer",
        opacity: disabled ? 0.38 : 1,
        position: "relative" as const,
        overflow: "hidden",
      }}
    >
      <span
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          width: 34,
          height: 34,
          borderRadius: 9,
          background: active ? "rgba(172,1,0,0.18)" : "rgba(255,255,255,0.04)",
          flexShrink: 0,
          transition: "background 0.18s",
        }}
      >
        <Icon size={16} style={{ color: active ? TEXT : MUTED }} />
      </span>
      {!rail && (
        <span
          style={{
            fontSize: 13.5,
            fontWeight: active ? 600 : 500,
            color: active ? TEXT : MUTED,
            flex: 1,
            overflow: "hidden",
            textOverflow: "ellipsis",
            whiteSpace: "nowrap",
          }}
        >
          {label}
        </span>
      )}
      {!rail && active && (
        <ChevronRight size={12} style={{ color: DIM, flexShrink: 0 }} />
      )}
    </span>
  );

  if (disabled) return <div title={rail ? label : undefined}>{inner}</div>;

  return (
    <Link
      href={href}
      onClick={onClick}
      title={rail ? label : undefined}
      style={{ display: "block", textDecoration: "none" }}
    >
      {inner}
    </Link>
  );
}

/* ── Sidebar content ──────────────────────────── */
function Sidebar({
  rail, pathname, user, onLogout, onClose,
}: {
  rail: boolean;
  pathname: string;
  user: { prenom: string; nom: string; email: string };
  onLogout: () => void;
  onClose: () => void;
}) {
  return (
    <div
      style={{
        display: "flex",
        flexDirection: "column",
        height: "100%",
        background: "rgba(255,255,255,0.028)",
        borderRight: BORDER,
      }}
    >
      {/* Logo ─────────────────────────────────── */}
      <div
        style={{
          height: 72,
          display: "flex",
          alignItems: "center",
          justifyContent: rail ? "center" : "flex-start",
          gap: 12,
          padding: rail ? "0" : "0 20px",
          borderBottom: BORDER,
          flexShrink: 0,
        }}
      >
        <span
          style={{
            display: "inline-flex",
            alignItems: "center",
            justifyContent: "center",
            width: 40,
            height: 40,
            borderRadius: 12,
            background: `linear-gradient(135deg, #031D59, ${BLUE})`,
            fontFamily: "var(--ff-display)",
            fontSize: 18,
            color: "#fff",
            flexShrink: 0,
            boxShadow: `0 4px 18px rgba(79,111,191,0.35)`,
            position: "relative",
          }}
        >
          R
          <span
            style={{
              position: "absolute",
              top: -2,
              right: -2,
              width: 9,
              height: 9,
              borderRadius: "50%",
              background: RED,
              border: "1.5px solid #021541",
            }}
          />
        </span>
        {!rail && (
          <div>
            <p style={{ fontSize: 11, fontWeight: 700, letterSpacing: "0.14em", color: TEXT, textTransform: "uppercase" }}>
              REJCC
            </p>
            <p style={{ fontSize: 10, color: DIM, letterSpacing: "0.06em", marginTop: 1 }}>
              Espace Membre
            </p>
          </div>
        )}
      </div>

      {/* Nav ────────────────────────────────────── */}
      <nav style={{ flex: 1, overflowY: "auto", padding: "14px 10px" }}>
        <ul style={{ display: "flex", flexDirection: "column", gap: 2, listStyle: "none", margin: 0, padding: 0 }}>
          {navItems.map((item) => {
            const active = item.exact
              ? pathname === item.href
              : pathname.startsWith(item.href);
            return (
              <li key={item.href}>
                <NavItem
                  icon={item.icon}
                  label={item.label}
                  href={item.href}
                  active={active}
                  rail={rail}
                  disabled={item.disabled}
                  onClick={onClose}
                />
              </li>
            );
          })}
        </ul>
      </nav>

      {/* Paramètres + Profil ────────────────────── */}
      <div style={{ borderTop: BORDER, padding: "12px 10px" }}>
        <NavItem
          icon={Settings}
          label="Paramètres"
          href="/espace-membre/profil"
          active={pathname === "/espace-membre/profil"}
          rail={rail}
          onClick={onClose}
        />

        {!rail && (
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: 10,
              marginTop: 10,
              padding: "10px 12px",
              borderRadius: 12,
              background: SURF,
              border: BORDER,
            }}
          >
            {/* Avatar */}
            <div
              style={{
                width: 36,
                height: 36,
                borderRadius: "50%",
                background: `linear-gradient(135deg, ${BLUE}, ${RED})`,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                fontSize: 12,
                fontWeight: 700,
                color: "#fff",
                flexShrink: 0,
                letterSpacing: "0.04em",
              }}
            >
              {user.prenom?.[0]}{user.nom?.[0]}
            </div>

            <div style={{ minWidth: 0, flex: 1 }}>
              <p style={{ fontSize: 12, fontWeight: 600, color: TEXT, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                {user.prenom} {user.nom}
              </p>
              <p style={{ fontSize: 10, color: DIM, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", marginTop: 1 }}>
                {user.email}
              </p>
            </div>

            <button
              onClick={onLogout}
              title="Se déconnecter"
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                width: 28,
                height: 28,
                borderRadius: 8,
                background: "transparent",
                border: "none",
                color: DIM,
                cursor: "pointer",
                flexShrink: 0,
                transition: "color 0.18s, background 0.18s",
              }}
              onMouseEnter={(e) => {
                (e.currentTarget as HTMLButtonElement).style.color = RED2;
                (e.currentTarget as HTMLButtonElement).style.background = "rgba(172,1,0,0.14)";
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLButtonElement).style.color = DIM;
                (e.currentTarget as HTMLButtonElement).style.background = "transparent";
              }}
            >
              <LogOut size={13} />
            </button>
          </div>
        )}

        {rail && (
          <button
            onClick={onLogout}
            title="Se déconnecter"
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              width: "100%",
              padding: "9px 0",
              borderRadius: 10,
              background: "transparent",
              border: "none",
              color: DIM,
              cursor: "pointer",
              marginTop: 4,
              transition: "color 0.18s",
            }}
          >
            <LogOut size={16} />
          </button>
        )}
      </div>
    </div>
  );
}

/* ── MemberShell ──────────────────────────────── */
export function MemberShell({ children }: { children: React.ReactNode }) {
  const { user, loading, configured, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [sidebarMode, setSidebarMode] = useState<"full" | "rail" | "off">("full");

  /* ── Responsive sidebar ─── */
  useEffect(() => {
    function update() {
      const w = window.innerWidth;
      if (w >= 1180)      setSidebarMode("full");
      else if (w >= 720)  setSidebarMode("rail");
      else                setSidebarMode("off");
    }
    update();
    window.addEventListener("resize", update);
    return () => window.removeEventListener("resize", update);
  }, []);

  /* ── Auth guard ──────────── */
  useEffect(() => {
    if (!loading && configured && !user) router.replace("/connexion");
  }, [loading, configured, user, router]);

  /* ── Loading / splash ───── */
  if (loading || !user) {
    return (
      <div
        style={{
          position: "fixed",
          inset: 0,
          zIndex: 100,
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: BG,
        }}
      >
        {loading
          ? <Loader2 size={40} className="animate-spin" style={{ color: "rgba(244,246,248,0.5)" }} />
          : (
            <div style={{ textAlign: "center" }}>
              <AlertCircle size={48} style={{ color: "rgba(244,246,248,0.3)", margin: "0 auto 16px" }} />
              <p style={{ color: MUTED, fontSize: 14 }}>Redirection…</p>
            </div>
          )
        }
      </div>
    );
  }

  async function handleLogout() {
    await logout();
    router.push("/");
  }

  const sidebarWidth = sidebarMode === "full" ? 268 : sidebarMode === "rail" ? 76 : 0;

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        zIndex: 100,
        display: "flex",
        overflow: "hidden",
        background: BG,
      }}
    >
      {/* ── Ambient orbs ───────────────────────── */}
      <div
        style={{
          position: "absolute",
          inset: 0,
          pointerEvents: "none",
          zIndex: 0,
          overflow: "hidden",
        }}
      >
        <div
          style={{
            position: "absolute",
            top: "-25%",
            left: "35%",
            width: "55vw",
            height: "55vw",
            borderRadius: "50%",
            background: "radial-gradient(circle, rgba(79,111,191,0.13) 0%, transparent 68%)",
            animation: "aurora 20s ease-in-out infinite",
          }}
        />
        <div
          style={{
            position: "absolute",
            bottom: "5%",
            right: "-8%",
            width: "38vw",
            height: "38vw",
            borderRadius: "50%",
            background: "radial-gradient(circle, rgba(172,1,0,0.07) 0%, transparent 68%)",
            animation: "aurora 25s ease-in-out infinite reverse",
          }}
        />
      </div>

      {/* ── Sidebar (desktop/tablet) ────────────── */}
      {sidebarMode !== "off" && (
        <div
          style={{
            width: sidebarWidth,
            height: "100%",
            flexShrink: 0,
            zIndex: 1,
            transition: "width 0.25s var(--ease-smooth, ease)",
          }}
        >
          <Sidebar
            rail={sidebarMode === "rail"}
            pathname={pathname}
            user={user}
            onLogout={handleLogout}
            onClose={() => {}}
          />
        </div>
      )}

      {/* ── Mobile sidebar overlay ──────────────── */}
      {mobileOpen && sidebarMode === "off" && (
        <div
          style={{
            position: "absolute",
            inset: 0,
            zIndex: 50,
            display: "flex",
          }}
        >
          <div
            style={{ position: "absolute", inset: 0, background: "rgba(0,0,0,0.55)" }}
            onClick={() => setMobileOpen(false)}
          />
          <div style={{ width: 268, height: "100%", position: "relative", zIndex: 1 }}>
            <Sidebar
              rail={false}
              pathname={pathname}
              user={user}
              onLogout={handleLogout}
              onClose={() => setMobileOpen(false)}
            />
          </div>
          <button
            onClick={() => setMobileOpen(false)}
            style={{
              position: "absolute",
              top: 20,
              right: 20,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              width: 36,
              height: 36,
              borderRadius: 10,
              background: SURF2,
              border: BORDER,
              color: TEXT,
              cursor: "pointer",
            }}
          >
            <X size={16} />
          </button>
        </div>
      )}

      {/* ── Main area ───────────────────────────── */}
      <div
        style={{
          flex: 1,
          display: "flex",
          flexDirection: "column",
          overflow: "hidden",
          zIndex: 1,
          minWidth: 0,
        }}
      >
        {/* ── Sticky header ───────────────────────── */}
        <header
          style={{
            height: 72,
            display: "flex",
            alignItems: "center",
            padding: "0 28px",
            gap: 14,
            background: "rgba(255,255,255,0.03)",
            backdropFilter: "blur(16px) saturate(150%)",
            WebkitBackdropFilter: "blur(16px) saturate(150%)",
            borderBottom: BORDER,
            flexShrink: 0,
          }}
        >
          {/* Hamburger (mobile only) */}
          {sidebarMode === "off" && (
            <button
              onClick={() => setMobileOpen(true)}
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                width: 38,
                height: 38,
                borderRadius: 10,
                background: SURF,
                border: BORDER,
                color: TEXT,
                cursor: "pointer",
                flexShrink: 0,
              }}
            >
              <Menu size={17} />
            </button>
          )}

          {/* Greeting */}
          <div style={{ flex: 1, minWidth: 0 }}>
            <p
              style={{
                fontFamily: "var(--ff-serif)",
                fontSize: 19,
                fontStyle: "italic",
                color: TEXT,
                overflow: "hidden",
                whiteSpace: "nowrap",
                textOverflow: "ellipsis",
              }}
            >
              Bonjour, {user.prenom} 👋
            </p>
          </div>

          {/* Search */}
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: 8,
              background: SURF,
              border: BORDER,
              borderRadius: 10,
              padding: "0 12px",
              height: 38,
              width: 240,
              flexShrink: 0,
            }}
            className="hidden sm:flex"
          >
            <Search size={13} style={{ color: DIM, flexShrink: 0 }} />
            <input
              type="search"
              placeholder="Rechercher… ⌘K"
              style={{
                background: "transparent",
                border: "none",
                outline: "none",
                color: TEXT,
                fontSize: 13,
                width: "100%",
                fontFamily: "var(--ff-sans)",
              }}
            />
          </div>

          {/* Notification bell */}
          <Link
            href="/espace-membre/notifications"
            style={{ position: "relative", display: "flex", textDecoration: "none", flexShrink: 0 }}
          >
            <span
              style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                width: 38,
                height: 38,
                borderRadius: 10,
                background: SURF,
                border: BORDER,
                color: MUTED,
              }}
            >
              <Bell size={16} />
            </span>
            <span
              style={{
                position: "absolute",
                top: 6,
                right: 6,
                width: 8,
                height: 8,
                borderRadius: "50%",
                background: RED,
                border: "1.5px solid #021541",
                animation: "aurora 2s ease-in-out infinite",
              }}
            />
          </Link>

          {/* Avatar */}
          <div
            style={{
              width: 40,
              height: 40,
              borderRadius: "50%",
              background: `linear-gradient(135deg, ${BLUE}, #031D59)`,
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              fontSize: 13,
              fontWeight: 700,
              color: "#fff",
              flexShrink: 0,
              border: "2px solid rgba(255,255,255,0.14)",
              position: "relative",
              letterSpacing: "0.04em",
            }}
          >
            {user.prenom?.[0]}{user.nom?.[0]}
            <span
              style={{
                position: "absolute",
                bottom: 1,
                right: 1,
                width: 9,
                height: 9,
                borderRadius: "50%",
                background: GREEN,
                border: "1.5px solid #021541",
              }}
            />
          </div>
        </header>

        {/* ── Scrollable content ───────────────────── */}
        <main
          style={{
            flex: 1,
            overflowY: "auto",
            overflowX: "hidden",
          }}
        >
          {children}
        </main>
      </div>
    </div>
  );
}

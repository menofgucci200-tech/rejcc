"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Bell, Info, Loader2, MessageCircle } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { memberApi, type Notice } from "@/lib/api/client";
import { DarkPage } from "@/components/member/DarkPage";

const SURF   = "rgba(8,28,80,0.72)";
const SURF2  = "rgba(12,38,100,0.80)";
const BORDER = "rgba(255,255,255,0.09)";
const TEXT   = "#F4F6F8";
const MUTED  = "rgba(244,246,248,0.60)";
const DIM    = "rgba(244,246,248,0.38)";
const RED    = "#E84A43";

function time(s: string) {
  return new Date(s).toLocaleString("fr-FR", {
    day: "2-digit",
    month: "long",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function NotificationsView() {
  const { token } = useAuth();
  const [items, setItems] = useState<Notice[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;
    memberApi
      .notifications(token)
      .then((r) => setItems(r.notifications))
      .finally(() => setLoading(false));
    memberApi.markAllNotificationsRead(token).catch(() => {});
  }, [token]);

  return (
    <DarkPage
      title="Notifications"
      subtitle="Vos dernières alertes et messages du réseau."
      icon={<Bell size={20} />}
    >
      {loading ? (
        <div style={{ display: "flex", justifyContent: "center", padding: "64px 0" }}>
          <Loader2 size={28} className="animate-spin" style={{ color: "rgba(244,246,248,0.45)" }} />
        </div>
      ) : items.length === 0 ? (
        <div style={{ textAlign: "center", padding: "48px 0" }}>
          <Bell size={36} style={{ color: DIM, margin: "0 auto 14px" }} />
          <p style={{ color: MUTED, fontSize: 14 }}>Aucune notification pour l'instant.</p>
        </div>
      ) : (
        <ul style={{ listStyle: "none", margin: 0, padding: 0, display: "flex", flexDirection: "column", gap: 10, maxWidth: 640 }}>
          {items.map((n) => {
            const Icon = n.type === "message" ? MessageCircle : Info;
            const inner = (
              <div
                style={{
                  display: "flex",
                  alignItems: "flex-start",
                  gap: 14,
                  background: SURF,
                  border: `1px solid ${BORDER}`,
                  borderRadius: 16,
                  padding: "16px 18px",
                }}
              >
                <span
                  style={{
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: 40,
                    height: 40,
                    borderRadius: 11,
                    background: SURF2,
                    flexShrink: 0,
                    color: MUTED,
                  }}
                >
                  <Icon size={17} />
                </span>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <p style={{ fontSize: 14, fontWeight: 600, color: TEXT, margin: 0 }}>{n.title}</p>
                  {n.body && <p style={{ fontSize: 13, color: MUTED, margin: "4px 0 0", lineHeight: 1.5 }}>{n.body}</p>}
                  <p style={{ fontSize: 11.5, color: DIM, margin: "6px 0 0" }}>{time(n.created_at)}</p>
                </div>
                {!n.read_at && (
                  <span
                    style={{
                      width: 8,
                      height: 8,
                      borderRadius: "50%",
                      background: RED,
                      flexShrink: 0,
                      marginTop: 6,
                    }}
                  />
                )}
              </div>
            );
            return (
              <li key={n.id}>
                {n.link ? (
                  <Link href={n.link} style={{ textDecoration: "none" }}>{inner}</Link>
                ) : inner}
              </li>
            );
          })}
        </ul>
      )}
    </DarkPage>
  );
}

"use client";

import Link from "next/link";
import { ArrowLeft } from "lucide-react";

const TEXT  = "#F4F6F8";
const MUTED = "rgba(244,246,248,0.60)";
const DIM   = "rgba(244,246,248,0.38)";
const SURF  = "rgba(8,28,80,0.72)";
const BORDER = "rgba(255,255,255,0.09)";

/** Wrapper de mise en page pour les sous-pages de l'espace membre (annuaire, messagerie, etc.) */
export function DarkPage({
  title,
  subtitle,
  icon,
  children,
}: {
  title: string;
  subtitle?: string;
  icon?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <div style={{ padding: "32px 32px 60px" }}>
      {/* Back link */}
      <Link
        href="/espace-membre"
        style={{
          display: "inline-flex",
          alignItems: "center",
          gap: 6,
          fontSize: 12.5,
          color: DIM,
          textDecoration: "none",
          marginBottom: 22,
          transition: "color 0.18s",
        }}
      >
        <ArrowLeft size={13} /> Tableau de bord
      </Link>

      {/* Page header */}
      <div
        style={{
          display: "flex",
          alignItems: "flex-start",
          gap: 14,
          marginBottom: 32,
          paddingBottom: 24,
          borderBottom: `1px solid ${BORDER}`,
        }}
      >
        {icon && (
          <span
            style={{
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              width: 44,
              height: 44,
              borderRadius: 12,
              background: SURF,
              border: `1px solid ${BORDER}`,
              color: MUTED,
              flexShrink: 0,
              marginTop: 2,
            }}
          >
            {icon}
          </span>
        )}
        <div>
          <h1
            style={{
              fontFamily: "var(--ff-serif)",
              fontStyle: "italic",
              fontSize: 28,
              color: TEXT,
              margin: 0,
              lineHeight: 1.2,
            }}
          >
            {title}
          </h1>
          {subtitle && (
            <p style={{ fontSize: 14, color: MUTED, margin: "6px 0 0" }}>{subtitle}</p>
          )}
        </div>
      </div>

      {/* Content */}
      {children}
    </div>
  );
}

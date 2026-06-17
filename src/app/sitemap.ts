import type { MetadataRoute } from "next";
import { siteConfig } from "@/lib/content/site";
import { articles } from "@/lib/content/news";
import { events } from "@/lib/content/events";

const staticRoutes = [
  "",
  "/a-propos",
  "/activites",
  "/domaines",
  "/evenements",
  "/actualites",
  "/partenaires",
  "/adhesion",
  "/contact",
];

export default function sitemap(): MetadataRoute.Sitemap {
  const now = new Date();
  const base = staticRoutes.map((path) => ({
    url: `${siteConfig.url}${path}`,
    lastModified: now,
    changeFrequency: (path === "" ? "weekly" : "monthly") as "weekly" | "monthly",
    priority: path === "" ? 1 : 0.7,
  }));

  const news = articles.map((a) => ({
    url: `${siteConfig.url}/actualites/${a.slug}`,
    lastModified: new Date(a.iso),
    changeFrequency: "yearly" as const,
    priority: 0.5,
  }));

  const evs = events.map((e) => ({
    url: `${siteConfig.url}/evenements/${e.slug}`,
    lastModified: new Date(e.iso),
    changeFrequency: "yearly" as const,
    priority: 0.5,
  }));

  return [...base, ...news, ...evs];
}

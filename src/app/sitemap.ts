import type { MetadataRoute } from "next";
import { siteConfig } from "@/lib/content/site";

const routes = [
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
  return routes.map((path) => ({
    url: `${siteConfig.url}${path}`,
    lastModified: now,
    changeFrequency: path === "" ? "weekly" : "monthly",
    priority: path === "" ? 1 : 0.7,
  }));
}

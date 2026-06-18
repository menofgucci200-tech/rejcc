import type { Metadata, Viewport } from "next";
import { Anton, Manrope, Libre_Caslon_Display } from "next/font/google";
import "./globals.css";
import { siteConfig } from "@/lib/content/site";
import { Navbar } from "@/components/layout/Navbar";
import { Footer } from "@/components/layout/Footer";
import { SmoothScroll } from "@/components/layout/SmoothScroll";
import { ScrollProgress } from "@/components/layout/ScrollProgress";
import { Loader } from "@/components/layout/Loader";
import { AuthProvider } from "@/lib/auth/AuthContext";

// Impact → Anton  ·  Avenir Next → Manrope  ·  Big Caslon → Libre Caslon Display
const display = Anton({
  weight: "400",
  subsets: ["latin"],
  variable: "--ff-display",
  display: "swap",
});
const sans = Manrope({
  subsets: ["latin"],
  variable: "--ff-sans",
  display: "swap",
});
const serif = Libre_Caslon_Display({
  weight: "400",
  subsets: ["latin"],
  variable: "--ff-serif",
  display: "swap",
});

export const metadata: Metadata = {
  metadataBase: new URL(siteConfig.url),
  title: {
    default: `${siteConfig.name} — ${siteConfig.fullName}`,
    template: `%s · ${siteConfig.name}`,
  },
  description: siteConfig.mission,
  applicationName: siteConfig.name,
  keywords: [
    "REJCC",
    "réseau entrepreneurial",
    "jeunes catholiques",
    "Côte d'Ivoire",
    "entrepreneuriat",
    "incubateur",
    "mentorat",
    "networking",
    "Abidjan",
  ],
  authors: [{ name: siteConfig.name }],
  openGraph: {
    type: "website",
    locale: "fr_FR",
    url: siteConfig.url,
    siteName: siteConfig.name,
    title: `${siteConfig.name} — ${siteConfig.fullName}`,
    description: siteConfig.mission,
  },
  twitter: {
    card: "summary_large_image",
    title: `${siteConfig.name} — ${siteConfig.fullName}`,
    description: siteConfig.mission,
  },
  robots: { index: true, follow: true },
};

export const viewport: Viewport = {
  themeColor: "#031D59",
  width: "device-width",
  initialScale: 1,
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html
      lang="fr"
      className={`${display.variable} ${sans.variable} ${serif.variable}`}
    >
      <body className="min-h-screen bg-white antialiased">
        <AuthProvider>
          <Loader />
          <ScrollProgress />
          <SmoothScroll />
          <Navbar />
          <main>{children}</main>
          <Footer />
        </AuthProvider>
      </body>
    </html>
  );
}

import Link from "next/link";
import { Mail, MapPin, Phone } from "lucide-react";
import { Facebook, Instagram, Linkedin, Youtube } from "@/components/ui/SocialIcons";
import { LogoMark } from "@/components/ui/LogoMark";
import { siteConfig, nav } from "@/lib/content/site";
import { NewsletterForm } from "./NewsletterForm";

const socialIcons = [
  { label: "Facebook", href: "#", Icon: Facebook },
  { label: "Instagram", href: "#", Icon: Instagram },
  { label: "LinkedIn", href: "#", Icon: Linkedin },
  { label: "YouTube", href: "#", Icon: Youtube },
];

const exploreLinks = [
  { label: "Adhérer", href: "/adhesion" },
  { label: "Devenir partenaire", href: "/partenaires" },
  { label: "Nos domaines", href: "/domaines" },
  { label: "Espace membre", href: "/connexion" },
];

export function Footer() {
  return (
    <footer className="relative overflow-hidden bg-brand text-white">
      <div className="pointer-events-none absolute inset-0 bg-dots opacity-[0.06]" />
      <div className="pointer-events-none absolute -left-20 top-0 size-[40vmin] rounded-full bg-azure/20 blur-[100px]" />

      <div className="relative mx-auto w-full max-w-7xl container-px">
        <div className="grid gap-12 border-b border-white/10 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]">
          {/* Brand */}
          <div>
            <LogoMark kind="lockup-white" className="h-16" />
            <p className="mt-5 max-w-xs text-sm leading-relaxed text-white/65">
              {siteConfig.about}
            </p>
            <div className="mt-6 flex gap-2.5">
              {socialIcons.map(({ label, href, Icon }) => (
                <a
                  key={label}
                  href={href}
                  aria-label={label}
                  className="inline-flex size-10 items-center justify-center rounded-full border border-white/15 text-white/80 transition-all hover:border-white/40 hover:bg-white/10 hover:text-white"
                >
                  <Icon className="size-4.5" />
                </a>
              ))}
            </div>
          </div>

          {/* Navigation */}
          <div>
            <h3 className="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">
              Navigation
            </h3>
            <ul className="mt-5 space-y-3">
              {nav.map((item) => (
                <li key={item.href}>
                  <Link
                    href={item.href}
                    className="text-sm text-white/70 transition-colors hover:text-white"
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Explorer */}
          <div>
            <h3 className="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">
              Explorer
            </h3>
            <ul className="mt-5 space-y-3">
              {exploreLinks.map((item) => (
                <li key={item.href}>
                  <Link
                    href={item.href}
                    className="text-sm text-white/70 transition-colors hover:text-white"
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
            <ul className="mt-7 space-y-3 text-sm text-white/70">
              <li className="flex items-center gap-2.5">
                <MapPin className="size-4 shrink-0 text-azure" />
                {siteConfig.contact.address}
              </li>
              <li className="flex items-center gap-2.5">
                <Mail className="size-4 shrink-0 text-azure" />
                {siteConfig.contact.email}
              </li>
              <li className="flex items-center gap-2.5">
                <Phone className="size-4 shrink-0 text-azure" />
                {siteConfig.contact.phone}
              </li>
            </ul>
          </div>

          {/* Newsletter */}
          <div>
            <h3 className="text-xs font-semibold uppercase tracking-[0.18em] text-white/50">
              Restez informé
            </h3>
            <p className="mt-5 text-sm text-white/65">
              Recevez les actualités, événements et opportunités du réseau.
            </p>
            <NewsletterForm />
          </div>
        </div>

        {/* Bottom bar */}
        <div className="flex flex-col items-center justify-between gap-4 py-7 text-sm text-white/55 md:flex-row">
          <p>
            © {new Date().getFullYear()} {siteConfig.name} —{" "}
            <span className="font-serif italic text-white/75">
              {siteConfig.slogan}
            </span>
          </p>
          <div className="flex items-center gap-6">
            <Link href="/contact" className="transition-colors hover:text-white">
              Mentions légales
            </Link>
            <Link href="/contact" className="transition-colors hover:text-white">
              Confidentialité
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
}

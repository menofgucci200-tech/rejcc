import type { Metadata } from "next";
import { MapPin, Mail, Phone } from "lucide-react";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { ContactForm } from "@/components/forms/ContactForm";
import { Facebook, Instagram, Linkedin, Youtube } from "@/components/ui/SocialIcons";
import { siteConfig } from "@/lib/content/site";

export const metadata: Metadata = {
  title: "Contact",
  description: "Contactez le REJCC — formulaire, coordonnées et réseaux sociaux.",
};

const infos = [
  { icon: MapPin, label: "Adresse", value: siteConfig.contact.address },
  { icon: Mail, label: "E-mail", value: siteConfig.contact.email },
  { icon: Phone, label: "Téléphone", value: siteConfig.contact.phone },
];

const socials = [
  { label: "Facebook", Icon: Facebook },
  { label: "Instagram", Icon: Instagram },
  { label: "LinkedIn", Icon: Linkedin },
  { label: "YouTube", Icon: Youtube },
];

export default function ContactPage() {
  return (
    <>
      <PageHeader
        eyebrow="Parlons-en"
        crumb="Contact"
        title={
          <>
            Nous{" "}
            <span className="font-serif italic normal-case text-azure">contacter</span>
          </>
        }
        subtitle="Une question, un projet, une envie de collaborer ? L'équipe du REJCC vous répond."
      />

      <section className="bg-white py-20 sm:py-28">
        <Container className="grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
          {/* Coordonnées */}
          <div>
            <h2 className="font-display text-2xl uppercase tracking-tight text-brand">
              Coordonnées
            </h2>
            <ul className="mt-6 space-y-4">
              {infos.map((info) => (
                <li key={info.label} className="flex items-start gap-4">
                  <span className="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand text-white">
                    <info.icon className="size-5" />
                  </span>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">
                      {info.label}
                    </p>
                    <p className="mt-0.5 font-medium text-brand">{info.value}</p>
                  </div>
                </li>
              ))}
            </ul>

            <p className="mt-8 text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">
              Suivez-nous
            </p>
            <div className="mt-3 flex gap-2.5">
              {socials.map(({ label, Icon }) => (
                <a
                  key={label}
                  href="#"
                  aria-label={label}
                  className="inline-flex size-11 items-center justify-center rounded-full border border-brand/15 text-brand transition-colors hover:bg-brand hover:text-white"
                >
                  <Icon className="size-4.5" />
                </a>
              ))}
            </div>
          </div>

          {/* Formulaire */}
          <ContactForm />
        </Container>
      </section>
    </>
  );
}

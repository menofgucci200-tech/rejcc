import type { Metadata } from "next";
import { MapPin, Mail, Phone } from "lucide-react";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { Reveal } from "@/components/ui/Reveal";
import { ComingSoon } from "@/components/sections/ComingSoon";
import { siteConfig } from "@/lib/content/site";

export const metadata: Metadata = {
  title: "Contact",
  description: "Contactez le REJCC — coordonnées, réseaux sociaux et formulaire.",
};

const infos = [
  { icon: MapPin, label: "Adresse", value: siteConfig.contact.address },
  { icon: Mail, label: "E-mail", value: siteConfig.contact.email },
  { icon: Phone, label: "Téléphone", value: siteConfig.contact.phone },
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

      <section className="bg-white py-24 sm:py-28">
        <Container>
          <div className="grid gap-5 sm:grid-cols-3">
            {infos.map((info, i) => (
              <Reveal key={info.label} delay={i * 0.08}>
                <div className="flex h-full flex-col rounded-3xl border border-brand/10 bg-cloud p-7">
                  <span className="inline-flex size-12 items-center justify-center rounded-2xl bg-brand text-white">
                    <info.icon className="size-5" />
                  </span>
                  <h3 className="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-ink/50">
                    {info.label}
                  </h3>
                  <p className="mt-1.5 font-medium text-brand">{info.value}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      <ComingSoon
        intro="Le formulaire de contact, la carte interactive et la FAQ seront bientôt disponibles sur cette page."
        features={[
          "Formulaire de contact",
          "Carte interactive de localisation",
          "Foire aux questions (FAQ)",
          "Liens vers les réseaux sociaux",
        ]}
      />
    </>
  );
}

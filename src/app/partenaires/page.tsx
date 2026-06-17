import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { Reveal } from "@/components/ui/Reveal";
import { PartenariatForm } from "@/components/forms/PartenariatForm";
import { partners, partnershipBenefits } from "@/lib/content/partners";

export const metadata: Metadata = {
  title: "Partenaires",
  description:
    "Devenez partenaire du REJCC et soutenez une nouvelle génération d'entrepreneurs catholiques en Côte d'Ivoire.",
};

export default function PartenairesPage() {
  return (
    <>
      <PageHeader
        eyebrow="Ensemble, plus loin"
        crumb="Partenaires"
        title={
          <>
            Nos{" "}
            <span className="font-serif italic normal-case text-azure">partenaires</span>
          </>
        }
        subtitle="Entreprises, institutions et organisations qui soutiennent l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire."
      />

      {/* Vitrine partenaires */}
      <section className="bg-white py-20 sm:py-24">
        <Container>
          <SectionHeading
            eyebrow="Ils nous soutiennent"
            title="Un réseau de confiance"
            subtitle="Logos provisoires — la liste officielle de nos partenaires sera bientôt mise à jour."
          />
          <div className="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            {partners.map((p, i) => (
              <Reveal key={p.name} delay={(i % 4) * 0.06}>
                <div className="flex h-28 flex-col items-center justify-center gap-2 rounded-2xl border border-brand/10 bg-cloud transition-colors hover:border-brand/25">
                  <span className="flex size-12 items-center justify-center rounded-xl bg-brand font-display text-lg text-white">
                    {p.initials}
                  </span>
                  <span className="px-2 text-center text-xs font-medium text-ink/70">
                    {p.name}
                  </span>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      {/* Avantages */}
      <section className="bg-cloud py-20 sm:py-24">
        <Container>
          <SectionHeading
            eyebrow="Pourquoi nous rejoindre"
            title="Devenir partenaire, c'est…"
          />
          <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {partnershipBenefits.map((b, i) => (
              <Reveal key={b.title} delay={(i % 4) * 0.07} className="h-full">
                <div className="h-full rounded-3xl border border-brand/10 bg-white p-7">
                  <span className="font-display text-3xl text-accent">
                    0{i + 1}
                  </span>
                  <h3 className="mt-3 text-lg font-bold text-brand">{b.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-ink/70">{b.text}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      {/* Formulaire */}
      <section className="bg-white py-20 sm:py-24">
        <Container className="grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:gap-16">
          <div>
            <SectionHeading
              align="left"
              eyebrow="Devenir partenaire"
              title="Construisons ensemble"
              subtitle="Vous partagez nos valeurs et souhaitez soutenir le réseau ? Parlons-en. Remplissez ce formulaire et notre équipe vous recontacte."
            />
          </div>
          <PartenariatForm />
        </Container>
      </section>
    </>
  );
}

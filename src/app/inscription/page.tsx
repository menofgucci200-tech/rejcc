import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { RegisterForm } from "@/components/auth/forms";

export const metadata: Metadata = {
  title: "Créer un compte",
  description: "Créez votre compte membre REJCC pour accéder à l'espace dédié.",
};

export default function InscriptionPage() {
  return (
    <>
      <PageHeader
        eyebrow="Espace membre"
        crumb="Inscription"
        title={
          <>
            Créer un{" "}
            <span className="font-serif italic normal-case text-azure">compte</span>
          </>
        }
        subtitle="Rejoignez l'espace membre du REJCC : annuaire, ressources et bien plus."
      />
      <section className="bg-cloud py-16 sm:py-24">
        <Container className="max-w-2xl">
          <RegisterForm />
        </Container>
      </section>
    </>
  );
}

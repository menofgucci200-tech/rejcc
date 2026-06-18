import type { Metadata } from "next";
import { PageHeader } from "@/components/layout/PageHeader";
import { Container } from "@/components/ui/Container";
import { LoginForm } from "@/components/auth/forms";

export const metadata: Metadata = {
  title: "Connexion",
  description: "Connectez-vous à votre espace membre REJCC.",
};

export default function ConnexionPage() {
  return (
    <>
      <PageHeader
        eyebrow="Espace membre"
        crumb="Connexion"
        title={
          <>
            Se{" "}
            <span className="font-serif italic normal-case text-azure">connecter</span>
          </>
        }
        subtitle="Accédez à votre tableau de bord, à l'annuaire des membres et aux ressources du réseau."
      />
      <section className="bg-cloud py-16 sm:py-24">
        <Container className="max-w-md">
          <LoginForm />
        </Container>
      </section>
    </>
  );
}

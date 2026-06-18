"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { Container } from "@/components/ui/Container";
import { MemberAreaUnavailable } from "./forms";

/** Protège une page de l'espace membre : redirige vers /connexion si non connecté. */
export function MemberGuard({ children }: { children: React.ReactNode }) {
  const { user, loading, configured } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && configured && !user) router.replace("/connexion");
  }, [loading, configured, user, router]);

  if (!configured) {
    return (
      <section className="bg-cloud pb-24 pt-36 sm:pt-44">
        <Container className="max-w-2xl">
          <MemberAreaUnavailable />
        </Container>
      </section>
    );
  }

  if (loading || !user) {
    return (
      <section className="flex min-h-[60vh] items-center justify-center bg-cloud pt-24">
        <Loader2 className="size-8 animate-spin text-brand" />
      </section>
    );
  }

  return <>{children}</>;
}

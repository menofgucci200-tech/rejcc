"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { MemberAreaUnavailable } from "./forms";
import { Container } from "@/components/ui/Container";

export function AdminGuard({ children }: { children: React.ReactNode }) {
  const { user, loading, configured } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && configured) {
      if (!user) router.replace("/connexion");
      else if (user.role !== "admin") router.replace("/espace-membre");
    }
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

  if (loading || !user || user.role !== "admin") {
    return (
      <section className="flex min-h-[60vh] items-center justify-center bg-cloud pt-24">
        <Loader2 className="size-8 animate-spin text-brand" />
      </section>
    );
  }

  return <>{children}</>;
}

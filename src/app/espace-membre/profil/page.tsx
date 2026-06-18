import type { Metadata } from "next";
import { MemberGuard } from "@/components/auth/MemberGuard";
import { ProfileEditor } from "@/components/auth/ProfileEditor";

export const metadata: Metadata = {
  title: "Mon profil",
  robots: { index: false, follow: false },
};

export default function ProfilPage() {
  return (
    <MemberGuard>
      <ProfileEditor />
    </MemberGuard>
  );
}

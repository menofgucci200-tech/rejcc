import type { Metadata } from "next";
import { MemberGuard } from "@/components/auth/MemberGuard";
import { DocumentsView } from "@/components/auth/DocumentsView";

export const metadata: Metadata = {
  title: "Documents & ressources",
  robots: { index: false, follow: false },
};

export default function DocumentsPage() {
  return (
    <MemberGuard>
      <DocumentsView />
    </MemberGuard>
  );
}

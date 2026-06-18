import type { Metadata } from "next";
import { MemberGuard } from "@/components/auth/MemberGuard";
import { MessagingView } from "@/components/auth/MessagingView";

export const metadata: Metadata = {
  title: "Messagerie",
  robots: { index: false, follow: false },
};

export default function MessageriePage() {
  return (
    <MemberGuard>
      <MessagingView />
    </MemberGuard>
  );
}

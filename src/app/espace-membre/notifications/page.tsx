import type { Metadata } from "next";
import { MemberGuard } from "@/components/auth/MemberGuard";
import { NotificationsView } from "@/components/auth/NotificationsView";

export const metadata: Metadata = {
  title: "Notifications",
  robots: { index: false, follow: false },
};

export default function NotificationsPage() {
  return (
    <MemberGuard>
      <NotificationsView />
    </MemberGuard>
  );
}

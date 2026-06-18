import type { Metadata } from "next";
import { MemberDashboard } from "@/components/member/MemberDashboard";

export const metadata: Metadata = {
  title: "Espace membre",
  robots: { index: false, follow: false },
};

export default function EspaceMembrePage() {
  return <MemberDashboard />;
}

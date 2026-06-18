import type { Metadata } from "next";
import { MemberDirectory } from "@/components/auth/MemberDirectory";

export const metadata: Metadata = {
  title: "Annuaire des membres",
  robots: { index: false, follow: false },
};

export default function AnnuairePage() {
  return <MemberDirectory />;
}

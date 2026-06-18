import type { Metadata } from "next";
import { MessagingView } from "@/components/auth/MessagingView";

export const metadata: Metadata = {
  title: "Messagerie",
  robots: { index: false, follow: false },
};

export default function MessageriePage() {
  return <MessagingView />;
}

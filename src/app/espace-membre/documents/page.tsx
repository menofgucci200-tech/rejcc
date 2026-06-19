import type { Metadata } from "next";
import { DocumentsView } from "@/components/auth/DocumentsView";

export const metadata: Metadata = {
  title: "Documents & ressources",
  robots: { index: false, follow: false },
};

export default function DocumentsPage() {
  return <DocumentsView />;
}

import type { Metadata } from "next";
import { AdminGuard } from "@/components/auth/AdminGuard";
import { AdminShell } from "@/components/admin/AdminShell";
import { AdminDocuments } from "@/components/admin/AdminDocuments";

export const metadata: Metadata = { title: "Documents" };

export default function AdminDocumentsPage() {
  return (
    <AdminGuard>
      <AdminShell>
        <AdminDocuments />
      </AdminShell>
    </AdminGuard>
  );
}

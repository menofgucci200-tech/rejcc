import type { Metadata } from "next";
import { AdminGuard } from "@/components/auth/AdminGuard";
import { AdminShell } from "@/components/admin/AdminShell";
import { AdminContacts } from "@/components/admin/AdminContacts";

export const metadata: Metadata = { title: "Contacts" };

export default function AdminContactsPage() {
  return (
    <AdminGuard>
      <AdminShell>
        <AdminContacts />
      </AdminShell>
    </AdminGuard>
  );
}

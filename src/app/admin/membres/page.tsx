import type { Metadata } from "next";
import { AdminGuard } from "@/components/auth/AdminGuard";
import { AdminShell } from "@/components/admin/AdminShell";
import { AdminMembers } from "@/components/admin/AdminMembers";

export const metadata: Metadata = { title: "Membres" };

export default function AdminMembresPage() {
  return (
    <AdminGuard>
      <AdminShell>
        <AdminMembers />
      </AdminShell>
    </AdminGuard>
  );
}

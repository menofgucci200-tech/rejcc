import type { Metadata } from "next";
import { AdminGuard } from "@/components/auth/AdminGuard";
import { AdminShell } from "@/components/admin/AdminShell";
import { AdminAdhesions } from "@/components/admin/AdminAdhesions";

export const metadata: Metadata = { title: "Adhésions" };

export default function AdminAdhesionsPage() {
  return (
    <AdminGuard>
      <AdminShell>
        <AdminAdhesions />
      </AdminShell>
    </AdminGuard>
  );
}

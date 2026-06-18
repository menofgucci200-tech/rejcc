import type { Metadata } from "next";
import { AdminGuard } from "@/components/auth/AdminGuard";
import { AdminShell } from "@/components/admin/AdminShell";
import { AdminDashboard } from "@/components/admin/AdminDashboard";

export const metadata: Metadata = { title: "Tableau de bord" };

export default function AdminPage() {
  return (
    <AdminGuard>
      <AdminShell>
        <AdminDashboard />
      </AdminShell>
    </AdminGuard>
  );
}

import type { Metadata } from "next";
import { AdminGuard } from "@/components/auth/AdminGuard";
import { AdminShell } from "@/components/admin/AdminShell";
import { AdminNotifications } from "@/components/admin/AdminNotifications";

export const metadata: Metadata = { title: "Notifications" };

export default function AdminNotificationsPage() {
  return (
    <AdminGuard>
      <AdminShell>
        <AdminNotifications />
      </AdminShell>
    </AdminGuard>
  );
}

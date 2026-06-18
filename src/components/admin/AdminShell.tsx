"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  LayoutDashboard, Users, FileText, MessageSquare, FolderOpen, Bell,
  LogOut, Menu, X, ChevronRight,
} from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import { cn } from "@/lib/utils";

const navItems = [
  { icon: LayoutDashboard, label: "Tableau de bord", href: "/admin" },
  { icon: Users,           label: "Membres",         href: "/admin/membres" },
  { icon: FileText,        label: "Adhésions",        href: "/admin/adhesions" },
  { icon: MessageSquare,   label: "Contacts",         href: "/admin/contacts" },
  { icon: FolderOpen,      label: "Documents",        href: "/admin/documents" },
  { icon: Bell,            label: "Notifications",    href: "/admin/notifications" },
];

export function AdminShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const { user, logout } = useAuth();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  async function handleLogout() {
    await logout();
    router.push("/");
  }

  const Sidebar = () => (
    <aside className="flex h-full w-64 flex-col border-r border-brand/10 bg-white">
      {/* Logo */}
      <div className="flex h-16 items-center gap-3 border-b border-brand/10 px-5">
        <span className="inline-flex size-8 items-center justify-center rounded-lg bg-brand text-xs font-bold text-white">
          R
        </span>
        <div>
          <p className="text-xs font-bold uppercase tracking-widest text-brand">REJCC</p>
          <p className="text-[0.65rem] text-ink/50 uppercase tracking-wider">Administration</p>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto p-3">
        <ul className="flex flex-col gap-0.5">
          {navItems.map((item) => {
            const active = pathname === item.href;
            return (
              <li key={item.href}>
                <Link
                  href={item.href}
                  onClick={() => setSidebarOpen(false)}
                  className={cn(
                    "flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors",
                    active
                      ? "bg-brand text-white"
                      : "text-ink/70 hover:bg-brand/5 hover:text-brand",
                  )}
                >
                  <item.icon className="size-4 shrink-0" />
                  {item.label}
                  {active && <ChevronRight className="ml-auto size-3.5" />}
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>

      {/* Profil + déconnexion */}
      <div className="border-t border-brand/10 p-4">
        <p className="truncate text-xs font-semibold text-brand">
          {user?.prenom} {user?.nom}
        </p>
        <p className="truncate text-[0.65rem] text-ink/50">{user?.email}</p>
        <button
          onClick={handleLogout}
          className="mt-3 inline-flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-ink/60 transition-colors hover:bg-accent/10 hover:text-accent"
        >
          <LogOut className="size-3.5" /> Se déconnecter
        </button>
      </div>
    </aside>
  );

  return (
    <div className="flex h-screen overflow-hidden bg-cloud">
      {/* Desktop sidebar */}
      <div className="hidden lg:flex lg:flex-shrink-0">
        <Sidebar />
      </div>

      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-50 flex lg:hidden">
          <div className="absolute inset-0 bg-black/30" onClick={() => setSidebarOpen(false)} />
          <div className="relative z-10 h-full">
            <Sidebar />
          </div>
        </div>
      )}

      {/* Contenu principal */}
      <div className="flex flex-1 flex-col overflow-hidden">
        {/* Topbar mobile */}
        <header className="flex h-16 items-center gap-4 border-b border-brand/10 bg-white px-4 lg:hidden">
          <button onClick={() => setSidebarOpen(true)} aria-label="Menu">
            <Menu className="size-5 text-brand" />
          </button>
          <p className="font-bold text-brand">Administration</p>
          <button onClick={() => setSidebarOpen(false)} className="ml-auto lg:hidden">
            {sidebarOpen && <X className="size-5 text-brand" />}
          </button>
        </header>

        <main className="flex-1 overflow-y-auto p-6 sm:p-8">
          {children}
        </main>
      </div>
    </div>
  );
}

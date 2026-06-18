import { MemberShell } from "@/components/member/MemberShell";

export default function EspaceMembreLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <MemberShell>{children}</MemberShell>;
}

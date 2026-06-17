import { cn } from "@/lib/utils";

/** Petite étiquette "kicker" au-dessus des titres de section. */
export function Eyebrow({
  children,
  className,
  tone = "light",
}: {
  children: React.ReactNode;
  className?: string;
  tone?: "light" | "dark";
}) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[0.18em]",
        tone === "light"
          ? "border-brand/15 bg-brand/5 text-brand"
          : "border-white/20 bg-white/10 text-white",
        className,
      )}
    >
      <span
        className={cn(
          "size-1.5 rounded-full",
          tone === "light" ? "bg-accent" : "bg-accent",
        )}
      />
      {children}
    </span>
  );
}

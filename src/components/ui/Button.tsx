import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { cn } from "@/lib/utils";

type Variant = "primary" | "solid" | "outline" | "ghost" | "white";
type Size = "sm" | "md" | "lg";

const variants: Record<Variant, string> = {
  // Rouge — accent, à utiliser pour l'action principale
  primary:
    "bg-accent text-white shadow-[0_10px_30px_-10px_rgba(172,1,0,0.6)] hover:bg-accent-600 hover:shadow-[0_18px_40px_-12px_rgba(172,1,0,0.7)]",
  // Bleu de marque
  solid:
    "bg-brand text-white shadow-[0_10px_30px_-12px_rgba(3,29,89,0.7)] hover:bg-brand-700",
  outline:
    "border border-brand/25 text-brand bg-white/60 hover:bg-brand hover:text-white hover:border-brand",
  ghost: "text-brand hover:bg-brand/5",
  white:
    "bg-white text-brand hover:bg-cloud shadow-[0_10px_30px_-12px_rgba(0,0,0,0.35)]",
};

const sizes: Record<Size, string> = {
  sm: "h-10 px-4 text-sm",
  md: "h-12 px-6 text-[0.95rem]",
  lg: "h-14 px-8 text-base",
};

export type ButtonProps = {
  href?: string;
  variant?: Variant;
  size?: Size;
  withArrow?: boolean;
  className?: string;
  children: React.ReactNode;
} & React.ButtonHTMLAttributes<HTMLButtonElement>;

export function Button({
  href,
  variant = "primary",
  size = "md",
  withArrow = false,
  className,
  children,
  ...props
}: ButtonProps) {
  const classes = cn(
    "group relative inline-flex items-center justify-center gap-2 rounded-full font-semibold tracking-tight",
    "transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] will-change-transform",
    "hover:-translate-y-0.5 active:translate-y-0",
    variants[variant],
    sizes[size],
    className,
  );

  const inner = (
    <>
      <span>{children}</span>
      {withArrow && (
        <ArrowRight className="size-4 transition-transform duration-300 group-hover:translate-x-1" />
      )}
    </>
  );

  if (href) {
    return (
      <Link href={href} className={classes}>
        {inner}
      </Link>
    );
  }
  return (
    <button className={classes} {...props}>
      {inner}
    </button>
  );
}

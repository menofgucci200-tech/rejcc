import { cn } from "@/lib/utils";
import { Eyebrow } from "./Eyebrow";

export function SectionHeading({
  eyebrow,
  title,
  subtitle,
  align = "center",
  tone = "light",
  className,
  titleClassName,
}: {
  eyebrow?: string;
  title: React.ReactNode;
  subtitle?: React.ReactNode;
  align?: "center" | "left";
  tone?: "light" | "dark";
  className?: string;
  titleClassName?: string;
}) {
  return (
    <div
      className={cn(
        "flex flex-col gap-5",
        align === "center" ? "items-center text-center" : "items-start text-left",
        className,
      )}
    >
      {eyebrow && <Eyebrow tone={tone}>{eyebrow}</Eyebrow>}
      <h2
        className={cn(
          "font-display uppercase leading-[0.95] tracking-[-0.01em]",
          "text-[clamp(2rem,5.2vw,3.75rem)]",
          tone === "light" ? "text-brand" : "text-white",
          titleClassName,
        )}
      >
        {title}
      </h2>
      {subtitle && (
        <p
          className={cn(
            "max-w-2xl text-pretty text-[1.05rem] leading-relaxed",
            align === "center" ? "mx-auto" : "",
            tone === "light" ? "text-ink/70" : "text-white/70",
          )}
        >
          {subtitle}
        </p>
      )}
    </div>
  );
}

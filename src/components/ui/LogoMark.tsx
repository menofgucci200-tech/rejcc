import Image from "next/image";
import { cn } from "@/lib/utils";

const sources = {
  "lockup-color": { src: "/brand/rejcc-logo-color.png", w: 649, h: 1213 },
  "lockup-white": { src: "/brand/rejcc-logo-white.png", w: 649, h: 1213 },
  "mono-color": { src: "/brand/rejcc-monogram-color.png", w: 649, h: 837 },
  "mono-white": { src: "/brand/rejcc-monogram-white.png", w: 649, h: 837 },
} as const;

export function LogoMark({
  kind = "mono-color",
  className,
  priority = false,
  alt = "REJCC",
}: {
  kind?: keyof typeof sources;
  className?: string;
  priority?: boolean;
  alt?: string;
}) {
  const s = sources[kind];
  return (
    <Image
      src={s.src}
      width={s.w}
      height={s.h}
      alt={alt}
      priority={priority}
      className={cn("w-auto select-none", className)}
    />
  );
}

import Image from "next/image";
import type { PaymentId } from "@/lib/content/membership";
import { cn } from "@/lib/utils";

/**
 * Logos officiels des moyens de paiement (fichiers fournis dans public/brand).
 * Chaque image contient déjà le nom de la marque.
 * Dimensions en style inline (la boîte doit être dimensionnée pour `next/image fill`).
 */
const logos: Record<PaymentId, { src: string; alt: string }> = {
  wave: { src: "/brand/wave.jpg", alt: "Wave" },
  orange: { src: "/brand/orange_money.png", alt: "Orange Money" },
  djamo: { src: "/brand/djamo.jpg", alt: "Djamo" },
};

export function PaymentLogo({
  id,
  className,
}: {
  id: PaymentId;
  className?: string;
}) {
  const l = logos[id];
  return (
    <span
      className={cn("overflow-hidden rounded-lg", className)}
      style={{ position: "relative", display: "block", height: 46, width: 104 }}
    >
      <Image src={l.src} alt={l.alt} fill className="object-contain" sizes="104px" />
    </span>
  );
}

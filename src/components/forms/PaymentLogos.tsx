import type { SVGProps, ReactElement } from "react";
import type { PaymentId } from "@/lib/content/membership";

/**
 * Logos stylisés des moyens de paiement (représentations de marque aux
 * couleurs officielles). À remplacer par les fichiers officiels si fournis.
 */
type P = SVGProps<SVGSVGElement>;

export function WaveLogo(props: P) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden {...props}>
      <rect width="32" height="32" rx="8" fill="#1FC3F4" />
      <path
        d="M5 19c2.4 0 2.4-4.5 4.8-4.5S12.2 19 14.6 19s2.4-4.5 4.8-4.5S21.8 19 24.2 19s2.4-4.5 2.8-4.5"
        fill="none"
        stroke="#fff"
        strokeWidth="2.3"
        strokeLinecap="round"
      />
    </svg>
  );
}

export function OrangeMoneyLogo(props: P) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden {...props}>
      <rect width="32" height="32" rx="8" fill="#FF7900" />
      <rect x="12.5" y="12.5" width="7" height="7" rx="1" fill="#fff" />
    </svg>
  );
}

export function DjamoLogo(props: P) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden {...props}>
      <rect width="32" height="32" rx="8" fill="#0B1233" />
      <text
        x="16"
        y="21.5"
        textAnchor="middle"
        fontSize="13"
        fontWeight="800"
        fill="#FFC93C"
        fontFamily="system-ui, sans-serif"
      >
        dj
      </text>
    </svg>
  );
}

export const paymentLogo: Record<PaymentId, (p: P) => ReactElement> = {
  wave: WaveLogo,
  orange: OrangeMoneyLogo,
  djamo: DjamoLogo,
};

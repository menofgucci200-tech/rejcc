import type { SVGProps, ReactElement } from "react";
import type { PaymentId } from "@/lib/content/membership";

/**
 * Logos des moyens de paiement, reproduits fidèlement en SVG d'après les
 * logos officiels (Wave, Orange Money, Djamo).
 */
type P = SVGProps<SVGSVGElement>;

export function WaveLogo(props: P) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden {...props}>
      <rect width="32" height="32" rx="8" fill="#1AC8F5" />
      {/* pieds */}
      <ellipse cx="13" cy="26.4" rx="2.2" ry="1.1" fill="#FF8A1E" />
      <ellipse cx="19" cy="26.4" rx="2.2" ry="1.1" fill="#FF8A1E" />
      {/* corps */}
      <path
        d="M16 6c5 0 7.6 4.1 7.6 10 0 5.6-3.1 9.1-7.6 9.1S8.4 21.6 8.4 16C8.4 10.1 11 6 16 6z"
        fill="#111"
      />
      {/* aileron levé (salut) */}
      <path d="M22.4 10.6c3-1.9 4.7.2 3.1 2.7-1 1.7-2.8 1.8-4.2.9z" fill="#111" />
      {/* ventre */}
      <ellipse cx="16" cy="18.6" rx="4.3" ry="5.6" fill="#fff" />
      {/* yeux */}
      <circle cx="13.6" cy="12.4" r="1.7" fill="#fff" />
      <circle cx="18.4" cy="12.4" r="1.7" fill="#fff" />
      <circle cx="13.8" cy="12.6" r="0.72" fill="#111" />
      <circle cx="18.2" cy="12.6" r="0.72" fill="#111" />
      {/* bec */}
      <path d="M14.8 15h2.4l-1.2 1.7z" fill="#FF8A1E" />
    </svg>
  );
}

export function OrangeMoneyLogo(props: P) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden {...props}>
      <rect width="32" height="32" rx="8" fill="#fff" stroke="#E7EBF1" />
      <g fill="none" strokeWidth="2.8" strokeLinecap="round" strokeLinejoin="round">
        {/* flèche noire ↗ */}
        <path d="M8.6 22 17 11.6" stroke="#111" />
        <path d="M12.2 11.6H17v4.8" stroke="#111" />
        {/* flèche orange ↘ */}
        <path d="M15 10.6 23.4 21" stroke="#FF7900" />
        <path d="M23.4 16.2v4.8h-4.8" stroke="#FF7900" />
      </g>
    </svg>
  );
}

export function DjamoLogo(props: P) {
  return (
    <svg viewBox="0 0 32 32" aria-hidden {...props}>
      <rect width="32" height="32" rx="8" fill="#1B1B23" />
      <text
        x="16"
        y="20.4"
        textAnchor="middle"
        fontFamily="Arial, Helvetica, sans-serif"
        fontWeight="800"
        fontStyle="italic"
        fontSize="9.5"
        letterSpacing="-0.4"
        fill="#fff"
      >
        djamo
      </text>
    </svg>
  );
}

export const paymentLogo: Record<PaymentId, (p: P) => ReactElement> = {
  wave: WaveLogo,
  orange: OrangeMoneyLogo,
  djamo: DjamoLogo,
};

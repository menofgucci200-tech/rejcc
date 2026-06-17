/**
 * Formules d'adhésion REJCC.
 * Tarifs PROVISOIRES (FCFA) — à confirmer par le réseau. `pricesProvisional`
 * permet d'afficher la mention « tarif indicatif » tant que ce n'est pas figé.
 */
export type PlanId = "etudiant" | "porteur" | "entrepreneur";

export type Plan = {
  id: PlanId;
  name: string;
  audience: string;
  price: number;
  period: string;
  features: string[];
  highlight?: boolean;
};

export const plans: Plan[] = [
  {
    id: "etudiant",
    name: "Étudiant",
    audience: "Étudiants & jeunes diplômés",
    price: 5000,
    period: "an",
    features: [
      "Accès à la communauté",
      "Événements & ateliers",
      "Newsletter & ressources",
      "Mentorat de groupe",
    ],
  },
  {
    id: "porteur",
    name: "Porteur de projet",
    audience: "Entrepreneurs débutants & moyens",
    price: 15000,
    period: "an",
    features: [
      "Tous les avantages Étudiant",
      "Mentorat personnalisé",
      "Mise en relation ciblée",
      "Visibilité dans l'annuaire",
    ],
    highlight: true,
  },
  {
    id: "entrepreneur",
    name: "Entrepreneur",
    audience: "Entrepreneurs confirmés",
    price: 30000,
    period: "an",
    features: [
      "Tous les avantages Porteur",
      "Accès partenaires & financements",
      "Opportunités d'intervention",
      "Accompagnement prioritaire",
    ],
  },
];

export const currency = "FCFA";
export const pricesProvisional = true;

export const paymentMethods = [
  { id: "mtn", label: "MTN MoMo" },
  { id: "orange", label: "Orange Money" },
  { id: "moov", label: "Moov Money" },
  { id: "wave", label: "Wave" },
  { id: "carte", label: "Carte bancaire" },
] as const;

export const formatPrice = (n: number) => n.toLocaleString("fr-FR");
export const getPlan = (id: PlanId) => plans.find((p) => p.id === id);

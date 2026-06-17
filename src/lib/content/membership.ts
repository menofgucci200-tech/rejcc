/**
 * Adhésion REJCC — tarif unique de 10 000 FCFA par membre.
 */
export const adhesionFee = 10000;
export const currency = "FCFA";
export const adhesionPeriod = "an";

export type ProfileId = "etudiant" | "porteur" | "entrepreneur";
export const profiles: { id: ProfileId; label: string }[] = [
  { id: "etudiant", label: "Étudiant & jeune diplômé" },
  { id: "porteur", label: "Porteur de projet" },
  { id: "entrepreneur", label: "Entrepreneur confirmé" },
];

export type PaymentId = "wave" | "orange" | "djamo";
export const paymentMethods: { id: PaymentId; label: string }[] = [
  { id: "wave", label: "Wave" },
  { id: "orange", label: "Orange Money" },
  { id: "djamo", label: "Djamo" },
];

export const formatPrice = (n: number) => n.toLocaleString("fr-FR");

export type Partner = {
  name: string;
  initials: string;
  sector: string;
};

// Partenaires provisoires (placeholders) — à remplacer par les vrais logos.
export const partners: Partner[] = [
  { name: "Diocèse d'Abidjan", initials: "DA", sector: "Institution" },
  { name: "Banque Atlantique", initials: "BA", sector: "Finance" },
  { name: "Orange CI", initials: "OC", sector: "Télécom" },
  { name: "CGECI", initials: "CG", sector: "Patronat" },
  { name: "Université Catholique", initials: "UC", sector: "Éducation" },
  { name: "PME Excellence", initials: "PE", sector: "Conseil" },
  { name: "Fondation Espoir", initials: "FE", sector: "ONG" },
  { name: "AgriSol", initials: "AS", sector: "Agro" },
];

export const partnershipTypes = [
  "Partenaire financier",
  "Partenaire technique",
  "Partenaire institutionnel",
  "Partenaire média",
  "Mécénat / Don",
  "Autre",
] as const;

export const partnershipBenefits = [
  {
    title: "Visibilité",
    text: "Associez votre marque à un réseau dynamique de jeunes entrepreneurs catholiques.",
  },
  {
    title: "Impact",
    text: "Soutenez concrètement la création de richesse et d'emplois en Côte d'Ivoire.",
  },
  {
    title: "Accès aux talents",
    text: "Rencontrez des porteurs de projets et des entrepreneurs prometteurs.",
  },
  {
    title: "Sens & valeurs",
    text: "Inscrivez votre engagement dans une démarche éthique et solidaire.",
  },
];

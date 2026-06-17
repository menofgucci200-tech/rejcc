import {
  Sprout,
  Cpu,
  Megaphone,
  Landmark,
  HeartPulse,
  Building2,
  ShoppingBag,
  Scissors,
  Leaf,
  type LucideIcon,
} from "lucide-react";

export type Sector = {
  icon: LucideIcon;
  title: string;
  blurb: string;
  items: string[];
};

/** Les 33 domaines d'activité du REJCC, regroupés en 9 pôles. */
export const sectors: Sector[] = [
  {
    icon: Sprout,
    title: "Agriculture & Agro",
    blurb: "Nourrir l'avenir, de la terre à l'assiette.",
    items: ["Agriculture", "Agroalimentaire", "Élevage", "Pêche"],
  },
  {
    icon: Cpu,
    title: "Tech & Numérique",
    blurb: "Innover et bâtir les solutions de demain.",
    items: [
      "Informatique",
      "Développement Web",
      "Cybersécurité",
      "Intelligence Artificielle",
    ],
  },
  {
    icon: Megaphone,
    title: "Communication & Création",
    blurb: "Faire rayonner les marques et les idées.",
    items: ["Communication", "Marketing", "Audiovisuel", "Design"],
  },
  {
    icon: Landmark,
    title: "Finance & Services",
    blurb: "Structurer, financer et sécuriser la croissance.",
    items: [
      "Finance",
      "Comptabilité",
      "Banque",
      "Assurance",
      "Ressources humaines",
      "Juridique",
    ],
  },
  {
    icon: HeartPulse,
    title: "Éducation & Santé",
    blurb: "Servir l'humain, du savoir au bien-être.",
    items: ["Éducation", "Santé", "Beauté"],
  },
  {
    icon: Building2,
    title: "Immobilier & BTP",
    blurb: "Construire des cadres de vie durables.",
    items: ["Immobilier", "BTP"],
  },
  {
    icon: ShoppingBag,
    title: "Commerce & Mobilité",
    blurb: "Faire circuler la valeur et les personnes.",
    items: ["Commerce", "E-commerce", "Transport", "Hôtellerie", "Tourisme"],
  },
  {
    icon: Scissors,
    title: "Artisanat & Mode",
    blurb: "Le savoir-faire et l'élégance ivoirienne.",
    items: ["Artisanat", "Couture"],
  },
  {
    icon: Leaf,
    title: "Impact & Énergie",
    blurb: "Entreprendre pour la planète et la communauté.",
    items: ["Énergies renouvelables", "ONG", "Développement communautaire"],
  },
];

export const totalDomains = sectors.reduce((n, s) => n + s.items.length, 0);

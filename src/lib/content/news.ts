export type Article = {
  category: string;
  title: string;
  date: string;
  readingTime: string;
  excerpt: string;
};

// Actualités provisoires — à remplacer / piloter depuis le back-office.
export const articles: Article[] = [
  {
    category: "Réseau",
    title: "Le REJCC accueille sa nouvelle promotion d'entrepreneurs",
    date: "12 juin 2026",
    readingTime: "3 min",
    excerpt:
      "Une cohorte de porteurs de projets rejoint le réseau pour un parcours d'accompagnement inédit.",
  },
  {
    category: "Mentorat",
    title: "Programme de mentorat : les binômes 2026 sont lancés",
    date: "28 mai 2026",
    readingTime: "4 min",
    excerpt:
      "Soixante mentors expérimentés accompagnent désormais les jeunes entreprises du réseau.",
  },
  {
    category: "Partenariats",
    title: "Un nouveau partenariat pour financer l'innovation locale",
    date: "9 mai 2026",
    readingTime: "2 min",
    excerpt:
      "Le REJCC s'associe à des acteurs financiers pour soutenir les projets à fort impact.",
  },
];

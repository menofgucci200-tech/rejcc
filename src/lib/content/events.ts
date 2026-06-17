export type EventItem = {
  day: string;
  month: string;
  year: string;
  title: string;
  type: string;
  location: string;
  excerpt: string;
};

// Événements provisoires — à remplacer / piloter depuis le back-office.
export const events: EventItem[] = [
  {
    day: "18",
    month: "Juil.",
    year: "2026",
    title: "Forum REJCC de l'Entrepreneuriat",
    type: "Forum",
    location: "Abidjan, Plateau",
    excerpt:
      "Une journée de conférences, de pitchs et de networking pour accélérer vos projets.",
  },
  {
    day: "02",
    month: "Août",
    year: "2026",
    title: "Atelier — Lever des fonds en Côte d'Ivoire",
    type: "Atelier",
    location: "Abidjan, Cocody",
    excerpt:
      "Maîtrisez les mécanismes de financement adaptés aux jeunes entreprises.",
  },
  {
    day: "20",
    month: "Sept.",
    year: "2026",
    title: "Visite d'entreprise & mentorat",
    type: "Visite",
    location: "Yamoussoukro",
    excerpt:
      "Immersion dans une entreprise modèle, suivie d'une session de mentorat.",
  },
  {
    day: "12",
    month: "Oct.",
    year: "2026",
    title: "Gala de l'Excellence REJCC",
    type: "Gala",
    location: "Abidjan",
    excerpt:
      "La soirée qui célèbre les talents, les projets et les réussites du réseau.",
  },
];

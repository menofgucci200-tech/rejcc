export type EventItem = {
  slug: string;
  iso: string; // YYYY-MM-DD pour tri/calendrier
  day: string;
  month: string;
  year: string;
  time: string;
  title: string;
  type: string;
  location: string;
  excerpt: string;
  description: string[];
};

export const eventTypes = [
  "Forum",
  "Atelier",
  "Visite",
  "Conférence",
  "Networking",
  "Gala",
] as const;

// Événements provisoires — à remplacer / piloter depuis le back-office.
export const events: EventItem[] = [
  {
    slug: "forum-entrepreneuriat-2026",
    iso: "2026-07-18",
    day: "18",
    month: "Juil.",
    year: "2026",
    time: "09:00 – 17:00",
    title: "Forum REJCC de l'Entrepreneuriat",
    type: "Forum",
    location: "Abidjan, Plateau",
    excerpt:
      "Une journée de conférences, de pitchs et de networking pour accélérer vos projets.",
    description: [
      "Le rendez-vous annuel du réseau : une journée complète dédiée à l'entrepreneuriat des jeunes catholiques de Côte d'Ivoire.",
      "Au programme : conférences inspirantes, sessions de pitchs, ateliers pratiques et moments de networking pour nouer des collaborations durables.",
    ],
  },
  {
    slug: "atelier-lever-des-fonds",
    iso: "2026-08-02",
    day: "02",
    month: "Août",
    year: "2026",
    time: "14:00 – 17:00",
    title: "Atelier — Lever des fonds en Côte d'Ivoire",
    type: "Atelier",
    location: "Abidjan, Cocody",
    excerpt:
      "Maîtrisez les mécanismes de financement adaptés aux jeunes entreprises.",
    description: [
      "Un atelier pratique pour comprendre les leviers de financement accessibles aux jeunes entreprises : fonds propres, prêts, subventions et investisseurs.",
      "Repartez avec une feuille de route claire pour préparer votre dossier et convaincre.",
    ],
  },
  {
    slug: "visite-entreprise-yamoussoukro",
    iso: "2026-09-20",
    day: "20",
    month: "Sept.",
    year: "2026",
    time: "10:00 – 13:00",
    title: "Visite d'entreprise & mentorat",
    type: "Visite",
    location: "Yamoussoukro",
    excerpt:
      "Immersion dans une entreprise modèle, suivie d'une session de mentorat.",
    description: [
      "Découvrez de l'intérieur une entreprise qui réussit : organisation, stratégie et bonnes pratiques.",
      "La visite est suivie d'une session de mentorat pour appliquer ces enseignements à vos propres projets.",
    ],
  },
  {
    slug: "conference-foi-et-entrepreneuriat",
    iso: "2026-10-05",
    day: "05",
    month: "Oct.",
    year: "2026",
    time: "18:00 – 20:00",
    title: "Conférence — Foi & entrepreneuriat",
    type: "Conférence",
    location: "Abidjan",
    excerpt:
      "Comment concilier valeurs chrétiennes et réussite entrepreneuriale.",
    description: [
      "Une soirée de réflexion et d'inspiration sur le lien entre foi, éthique et entrepreneuriat.",
      "Des intervenants partagent leur parcours et leur vision d'une réussite porteuse de sens.",
    ],
  },
  {
    slug: "gala-excellence-2026",
    iso: "2026-10-12",
    day: "12",
    month: "Oct.",
    year: "2026",
    time: "19:00 – 23:00",
    title: "Gala de l'Excellence REJCC",
    type: "Gala",
    location: "Abidjan",
    excerpt:
      "La soirée qui célèbre les talents, les projets et les réussites du réseau.",
    description: [
      "Le grand rendez-vous festif du réseau : une soirée de prestige pour célébrer les réussites de l'année.",
      "Remises de distinctions, témoignages et moments de communion autour des valeurs du REJCC.",
    ],
  },
  {
    slug: "soiree-networking-novembre",
    iso: "2026-11-15",
    day: "15",
    month: "Nov.",
    year: "2026",
    time: "18:30 – 21:00",
    title: "Soirée networking des membres",
    type: "Networking",
    location: "Abidjan, Marcory",
    excerpt:
      "Rencontrez les membres du réseau dans un cadre convivial et professionnel.",
    description: [
      "Une soirée dédiée aux rencontres entre membres : échangez, partagez vos projets et créez des synergies.",
      "Un format convivial pour développer votre réseau dans un esprit de confiance.",
    ],
  },
];

export const getEvent = (slug: string) => events.find((e) => e.slug === slug);

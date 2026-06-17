export type Article = {
  slug: string;
  category: string;
  title: string;
  date: string; // affichage
  iso: string; // tri
  readingTime: string;
  author: string;
  excerpt: string;
  body: string[];
};

export const newsCategories = [
  "Réseau",
  "Mentorat",
  "Partenariats",
  "Événements",
  "Formations",
] as const;

// Actualités provisoires — à remplacer / piloter depuis le back-office.
export const articles: Article[] = [
  {
    slug: "nouvelle-promotion-entrepreneurs",
    category: "Réseau",
    title: "Le REJCC accueille sa nouvelle promotion d'entrepreneurs",
    date: "12 juin 2026",
    iso: "2026-06-12",
    readingTime: "3 min",
    author: "La rédaction REJCC",
    excerpt:
      "Une cohorte de porteurs de projets rejoint le réseau pour un parcours d'accompagnement inédit.",
    body: [
      "Le REJCC est fier d'accueillir une nouvelle promotion de jeunes entrepreneurs et porteurs de projets catholiques, sélectionnés pour leur ambition et leur volonté de bâtir des entreprises à impact durable.",
      "Pendant plusieurs mois, ces membres bénéficieront d'un accompagnement structuré : mentorat, formations, mise en relation et accès aux opportunités du réseau. Une dynamique qui illustre la mission du REJCC : connecter pour faire grandir.",
      "« Nous voulons que chaque membre trouve ici les ressources, les rencontres et la confiance nécessaires pour réussir », rappelle l'équipe d'animation du réseau.",
    ],
  },
  {
    slug: "binomes-mentorat-2026",
    category: "Mentorat",
    title: "Programme de mentorat : les binômes 2026 sont lancés",
    date: "28 mai 2026",
    iso: "2026-05-28",
    readingTime: "4 min",
    author: "La rédaction REJCC",
    excerpt:
      "Soixante mentors expérimentés accompagnent désormais les jeunes entreprises du réseau.",
    body: [
      "Le programme de mentorat du REJCC entre dans une nouvelle phase avec la constitution des binômes 2026. Soixante mentors, entrepreneurs et experts confirmés, accompagnent désormais les porteurs de projets du réseau.",
      "Chaque binôme se fixe des objectifs concrets : structuration du modèle économique, stratégie commerciale, levée de fonds ou organisation. Un suivi régulier permet de mesurer les progrès tout au long de l'année.",
      "Ce dispositif incarne la valeur de solidarité chère au REJCC : transmettre, partager et faire grandir ensemble.",
    ],
  },
  {
    slug: "partenariat-financement-innovation",
    category: "Partenariats",
    title: "Un nouveau partenariat pour financer l'innovation locale",
    date: "9 mai 2026",
    iso: "2026-05-09",
    readingTime: "2 min",
    author: "La rédaction REJCC",
    excerpt:
      "Le REJCC s'associe à des acteurs financiers pour soutenir les projets à fort impact.",
    body: [
      "Le REJCC annonce un partenariat destiné à faciliter l'accès au financement pour les entreprises de son réseau. Objectif : soutenir les projets à fort potentiel d'impact économique et social en Côte d'Ivoire.",
      "Ce partenariat ouvrira progressivement l'accès à des appels à projets, des lignes de financement adaptées et un accompagnement à la structuration des dossiers.",
    ],
  },
  {
    slug: "retour-forum-entrepreneuriat",
    category: "Événements",
    title: "Retour sur le Forum de l'Entrepreneuriat",
    date: "22 avril 2026",
    iso: "2026-04-22",
    readingTime: "3 min",
    author: "La rédaction REJCC",
    excerpt:
      "Une journée riche en rencontres, en pitchs et en inspiration pour les membres du réseau.",
    body: [
      "Le Forum de l'Entrepreneuriat a réuni les membres du réseau autour de conférences, d'ateliers pratiques et de sessions de pitchs. Une journée placée sous le signe de l'excellence et du partage.",
      "Les participants ont pu échanger avec des dirigeants inspirants, nouer des collaborations et découvrir des modèles d'entreprises qui réussissent.",
    ],
  },
  {
    slug: "cycle-formations-numerique",
    category: "Formations",
    title: "Un cycle de formations dédié au numérique",
    date: "3 avril 2026",
    iso: "2026-04-03",
    readingTime: "2 min",
    author: "La rédaction REJCC",
    excerpt:
      "Développement web, IA, cybersécurité : montez en compétences avec le REJCC.",
    body: [
      "Le REJCC lance un cycle de formations dédié aux métiers du numérique : développement web, intelligence artificielle, cybersécurité et marketing digital.",
      "Ces formations, animées par des experts du réseau, visent à doter les membres des compétences clés pour innover et faire croître leurs activités.",
    ],
  },
  {
    slug: "rejoindre-communaute-catholique-entrepreneurs",
    category: "Réseau",
    title: "Pourquoi rejoindre une communauté d'entrepreneurs catholiques",
    date: "18 mars 2026",
    iso: "2026-03-18",
    readingTime: "4 min",
    author: "La rédaction REJCC",
    excerpt:
      "Foi, excellence et entrepreneuriat : la force d'un réseau qui partage des valeurs.",
    body: [
      "Entreprendre est un chemin exigeant. Le faire au sein d'une communauté qui partage vos valeurs change tout : confiance, entraide et sens commun deviennent des moteurs de réussite.",
      "Le REJCC réunit des jeunes entrepreneurs catholiques décidés à créer de la richesse au service de l'Église et de la société. Une force collective au service de chaque projet.",
    ],
  },
];

export const getArticle = (slug: string) =>
  articles.find((a) => a.slug === slug);

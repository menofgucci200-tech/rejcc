import {
  Flame,
  Award,
  HeartHandshake,
  Target,
  Gem,
  Network,
  GraduationCap,
  Megaphone,
  Rocket,
  HandHeart,
  UserPlus,
  ListChecks,
  Smartphone,
  PartyPopper,
  type LucideIcon,
} from "lucide-react";

export type Stat = { value: number; suffix?: string; label: string };
export const stats: Stat[] = [
  { value: 350, suffix: "+", label: "Membres engagés" },
  { value: 33, label: "Domaines d'activité" },
  { value: 40, suffix: "+", label: "Événements par an" },
  { value: 60, suffix: "+", label: "Mentors & experts" },
];

export type Value = { icon: LucideIcon; title: string; text: string };
export const values: Value[] = [
  {
    icon: Flame,
    title: "Foi",
    text: "S'inspirer des valeurs chrétiennes pour bâtir des relations durables, éthiques et porteuses de sens.",
  },
  {
    icon: Award,
    title: "Excellence",
    text: "Rechercher la qualité, le professionnalisme et l'amélioration continue dans chaque projet.",
  },
  {
    icon: HeartHandshake,
    title: "Solidarité",
    text: "Avancer ensemble, partager les ressources et faire grandir chaque membre du réseau.",
  },
  {
    icon: Target,
    title: "Impact",
    text: "Créer de la valeur réelle au service de l'Église, de l'économie et de la société ivoirienne.",
  },
  {
    icon: Gem,
    title: "Création de richesse",
    text: "Transformer les talents en entreprises viables, innovantes et économiquement autonomes.",
  },
];

export type Benefit = { icon: LucideIcon; title: string; text: string };
export const benefits: Benefit[] = [
  {
    icon: Network,
    title: "Un réseau de qualité",
    text: "Rejoignez une communauté triée d'entrepreneurs, de porteurs de projets et de décideurs catholiques.",
  },
  {
    icon: GraduationCap,
    title: "Formations & mentorat",
    text: "Montez en compétences grâce à des programmes, ateliers et mentors expérimentés.",
  },
  {
    icon: Rocket,
    title: "Accélérez vos projets",
    text: "Bénéficiez d'un accompagnement, d'opportunités d'affaires et d'une mise en relation ciblée.",
  },
  {
    icon: Megaphone,
    title: "Gagnez en visibilité",
    text: "Présentez votre entreprise à l'écosystème et aux partenaires du réseau.",
  },
  {
    icon: HandHeart,
    title: "Une communauté de foi",
    text: "Entreprendre dans un cadre de confiance, de valeurs partagées et d'entraide sincère.",
  },
  {
    icon: Gem,
    title: "Des opportunités concrètes",
    text: "Accédez à des appels à projets, des financements et des visites d'entreprises.",
  },
];

export type Step = { icon: LucideIcon; title: string; text: string };
export const steps: Step[] = [
  {
    icon: UserPlus,
    title: "Créez votre profil",
    text: "Renseignez votre activité, votre secteur et vos ambitions en quelques minutes.",
  },
  {
    icon: ListChecks,
    title: "Précisez votre profil",
    text: "Étudiant, porteur de projet ou entrepreneur confirmé : dites-nous qui vous êtes.",
  },
  {
    icon: Smartphone,
    title: "Réglez votre cotisation",
    text: "Payez vos 10 000 FCFA en toute simplicité par Wave, Orange Money ou Djamo.",
  },
  {
    icon: PartyPopper,
    title: "Rejoignez la communauté",
    text: "Accédez à votre espace membre, à l'annuaire, aux événements et aux ressources.",
  },
];

export type Testimonial = {
  quote: string;
  name: string;
  role: string;
  initials: string;
};
// Témoignages provisoires — à remplacer par de vrais retours de membres.
export const testimonials: Testimonial[] = [
  {
    quote:
      "Le REJCC m'a ouvert un réseau que je n'aurais jamais atteint seul. En six mois, j'ai trouvé deux partenaires et un mentor.",
    name: "Jean-Marc Koffi",
    role: "Fondateur, AgriTech",
    initials: "JK",
  },
  {
    quote:
      "Entreprendre avec des valeurs partagées change tout. Ici, la foi et l'excellence avancent ensemble.",
    name: "Aïcha Brou",
    role: "Dirigeante, Studio créatif",
    initials: "AB",
  },
  {
    quote:
      "Les formations et le mentorat m'ont aidée à structurer mon projet et à le rendre rentable.",
    name: "Grâce Adjoua",
    role: "Porteuse de projet, E-commerce",
    initials: "GA",
  },
];

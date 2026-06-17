import {
  GraduationCap,
  Users,
  Mic,
  Network,
  Building2,
  HandHeart,
  CalendarDays,
  Wrench,
  type LucideIcon,
} from "lucide-react";

export type Activity = {
  icon: LucideIcon;
  title: string;
  text: string;
};

export const activities: Activity[] = [
  {
    icon: GraduationCap,
    title: "Formations",
    text: "Des parcours pour développer vos compétences entrepreneuriales, techniques et managériales.",
  },
  {
    icon: Users,
    title: "Mentorat",
    text: "Un accompagnement personnalisé par des entrepreneurs et experts confirmés.",
  },
  {
    icon: Mic,
    title: "Conférences",
    text: "Des rencontres inspirantes avec des leaders qui partagent leur vision et leur expérience.",
  },
  {
    icon: Network,
    title: "Networking",
    text: "Des moments privilégiés pour tisser des liens d'affaires et des collaborations durables.",
  },
  {
    icon: Building2,
    title: "Visites d'entreprises",
    text: "Découvrir des modèles qui réussissent et s'en inspirer concrètement.",
  },
  {
    icon: HandHeart,
    title: "Projets communautaires",
    text: "Mettre l'entrepreneuriat au service de l'Église et de la société.",
  },
  {
    icon: CalendarDays,
    title: "Événements",
    text: "Forums, galas et célébrations qui rythment la vie du réseau.",
  },
  {
    icon: Wrench,
    title: "Ateliers",
    text: "Des sessions pratiques pour passer de l'idée à l'exécution.",
  },
];

import { z } from "zod";

/** Schémas partagés entre le front (React Hook Form) et les routes API. */

export const adhesionSchema = z.object({
  plan: z.enum(["etudiant", "porteur", "entrepreneur"], {
    error: "Choisissez une formule d'adhésion.",
  }),
  prenom: z.string().min(2, { error: "Votre prénom est requis." }),
  nom: z.string().min(2, { error: "Votre nom est requis." }),
  email: z.email({ error: "Adresse e-mail invalide." }),
  telephone: z
    .string()
    .regex(/^[0-9]{10}$/, { error: "Numéro invalide (10 chiffres requis)." }),
  genre: z.enum(["Homme", "Femme"], { error: "Sélectionnez votre genre." }),
  ville: z.string().min(2, { error: "Votre ville est requise." }),
  secteur: z.string().min(1, { error: "Sélectionnez votre domaine." }),
  organisation: z.string().max(120).optional().or(z.literal("")),
  message: z.string().max(800, { error: "Message trop long." }).optional().or(z.literal("")),
  paiement: z.enum(["mtn", "orange", "moov", "wave", "carte"], {
    error: "Choisissez un moyen de paiement.",
  }),
  consent: z.literal(true, {
    error: "Vous devez accepter pour finaliser votre adhésion.",
  }),
});
export type AdhesionInput = z.infer<typeof adhesionSchema>;

export const contactSchema = z.object({
  nom: z.string().min(2, { error: "Votre nom est requis." }),
  email: z.email({ error: "Adresse e-mail invalide." }),
  sujet: z.string().min(2, { error: "Le sujet est requis." }),
  message: z
    .string()
    .min(10, { error: "Votre message est trop court." })
    .max(1500, { error: "Votre message est trop long." }),
});
export type ContactInput = z.infer<typeof contactSchema>;

export const newsletterSchema = z.object({
  email: z.email({ error: "Adresse e-mail invalide." }),
});
export type NewsletterInput = z.infer<typeof newsletterSchema>;

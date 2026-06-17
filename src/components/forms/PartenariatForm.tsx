"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Send, Check, Loader2 } from "lucide-react";
import { partenariatSchema, type PartenariatInput } from "@/lib/validation/schemas";
import { partnershipTypes } from "@/lib/content/partners";
import { TextField, TextareaField, SelectField } from "./fields";

export function PartenariatForm() {
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting, isSubmitSuccessful },
  } = useForm<PartenariatInput>({
    resolver: zodResolver(partenariatSchema),
    mode: "onTouched",
    defaultValues: {
      organisation: "",
      contact: "",
      email: "",
      telephone: "",
      type: "",
      message: "",
    },
  });

  async function onSubmit(data: PartenariatInput) {
    const res = await fetch("/api/partenariat", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    if (!res.ok) throw new Error("Erreur");
  }

  if (isSubmitSuccessful) {
    return (
      <div className="flex flex-col items-center justify-center rounded-3xl border border-brand/10 bg-cloud p-10 text-center">
        <span className="inline-flex size-14 items-center justify-center rounded-full bg-accent/10 text-accent">
          <Check className="size-7" />
        </span>
        <h3 className="mt-5 text-xl font-bold text-brand">Demande envoyée&nbsp;!</h3>
        <p className="mt-2 text-sm text-ink/70">
          Merci de votre intérêt. L&apos;équipe du REJCC reviendra vers vous très
          prochainement pour échanger sur ce partenariat.
        </p>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit(onSubmit)}
      className="flex flex-col gap-4 rounded-3xl border border-brand/10 bg-white p-6 sm:p-8"
    >
      <div className="grid gap-4 sm:grid-cols-2">
        <TextField label="Organisation" id="p-org" error={errors.organisation?.message} {...register("organisation")} />
        <TextField label="Personne de contact" id="p-contact" error={errors.contact?.message} {...register("contact")} />
        <TextField label="E-mail" id="p-email" type="email" error={errors.email?.message} {...register("email")} />
        <TextField label="Téléphone" id="p-tel" inputMode="numeric" placeholder="0700000000" error={errors.telephone?.message} {...register("telephone")} />
      </div>
      <SelectField label="Type de partenariat" id="p-type" error={errors.type?.message} {...register("type")}>
        <option value="">Sélectionnez…</option>
        {partnershipTypes.map((t) => (
          <option key={t} value={t}>
            {t}
          </option>
        ))}
      </SelectField>
      <TextareaField label="Votre message" id="p-message" error={errors.message?.message} {...register("message")} />
      <button
        type="submit"
        disabled={isSubmitting}
        className="inline-flex items-center justify-center gap-2 self-start rounded-full bg-accent px-7 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70"
      >
        {isSubmitting ? (
          <>
            <Loader2 className="size-4 animate-spin" /> Envoi…
          </>
        ) : (
          <>
            Envoyer la demande <Send className="size-4" />
          </>
        )}
      </button>
    </form>
  );
}

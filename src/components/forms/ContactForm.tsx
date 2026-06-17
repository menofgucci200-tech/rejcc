"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Send, Check, Loader2 } from "lucide-react";
import { contactSchema, type ContactInput } from "@/lib/validation/schemas";
import { TextField, TextareaField } from "./fields";

export function ContactForm() {
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting, isSubmitSuccessful },
  } = useForm<ContactInput>({
    resolver: zodResolver(contactSchema),
    mode: "onTouched",
    defaultValues: { nom: "", email: "", sujet: "", message: "" },
  });

  async function onSubmit(data: ContactInput) {
    const res = await fetch("/api/contact", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    if (!res.ok) throw new Error("Erreur");
    reset();
  }

  if (isSubmitSuccessful) {
    return (
      <div className="flex h-full flex-col items-center justify-center rounded-3xl border border-brand/10 bg-cloud p-10 text-center">
        <span className="inline-flex size-14 items-center justify-center rounded-full bg-accent/10 text-accent">
          <Check className="size-7" />
        </span>
        <h3 className="mt-5 text-xl font-bold text-brand">Message envoyé&nbsp;!</h3>
        <p className="mt-2 text-sm text-ink/70">
          Merci, l&apos;équipe du REJCC vous répondra dans les meilleurs délais.
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
        <TextField label="Nom complet" id="c-nom" error={errors.nom?.message} {...register("nom")} />
        <TextField label="E-mail" id="c-email" type="email" error={errors.email?.message} {...register("email")} />
      </div>
      <TextField label="Sujet" id="c-sujet" error={errors.sujet?.message} {...register("sujet")} />
      <TextareaField label="Message" id="c-message" error={errors.message?.message} {...register("message")} />
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
            Envoyer le message <Send className="size-4" />
          </>
        )}
      </button>
    </form>
  );
}

"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { motion, AnimatePresence } from "framer-motion";
import { Check, ArrowRight, ArrowLeft, ShieldCheck, PartyPopper, Loader2 } from "lucide-react";
import { adhesionSchema, type AdhesionInput } from "@/lib/validation/schemas";
import {
  plans,
  paymentMethods,
  currency,
  pricesProvisional,
  formatPrice,
  getPlan,
} from "@/lib/content/membership";
import { sectors } from "@/lib/content/domains";
import { TextField, SelectField, TextareaField } from "./fields";
import { cn } from "@/lib/utils";

const stepLabels = ["Formule", "Vos informations", "Paiement"];
const stepFields: (keyof AdhesionInput)[][] = [
  ["plan"],
  ["prenom", "nom", "email", "telephone", "genre", "ville", "secteur"],
  ["paiement", "consent"],
];

export function AdhesionForm() {
  const [step, setStep] = useState(0);
  const [status, setStatus] = useState<"idle" | "submitting" | "success" | "error">("idle");
  const [reference, setReference] = useState("");
  const [serverError, setServerError] = useState("");

  const {
    register,
    handleSubmit,
    trigger,
    watch,
    setValue,
    formState: { errors },
  } = useForm<AdhesionInput>({
    resolver: zodResolver(adhesionSchema),
    mode: "onTouched",
    defaultValues: {
      prenom: "",
      nom: "",
      email: "",
      telephone: "",
      ville: "",
      secteur: "",
      organisation: "",
      message: "",
    },
  });

  const selectedPlan = watch("plan");
  const selectedPayment = watch("paiement");
  const selectedGenre = watch("genre");
  const plan = selectedPlan ? getPlan(selectedPlan) : undefined;

  async function next() {
    const ok = await trigger(stepFields[step]);
    if (ok) setStep((s) => Math.min(s + 1, 2));
  }
  const back = () => setStep((s) => Math.max(s - 1, 0));

  async function onSubmit(data: AdhesionInput) {
    setStatus("submitting");
    setServerError("");
    try {
      const res = await fetch("/api/adhesion", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      const json = await res.json();
      if (!res.ok || !json.ok) throw new Error(json.message || "Une erreur est survenue.");
      setReference(json.reference);
      setStatus("success");
    } catch (e) {
      setServerError(e instanceof Error ? e.message : "Une erreur est survenue.");
      setStatus("error");
    }
  }

  if (status === "success") {
    return (
      <div className="rounded-3xl border border-brand/10 bg-white p-8 text-center sm:p-12">
        <div className="mx-auto inline-flex size-16 items-center justify-center rounded-full bg-accent/10 text-accent">
          <PartyPopper className="size-8" />
        </div>
        <h3 className="mt-6 font-display text-3xl uppercase tracking-tight text-brand">
          Bienvenue au REJCC&nbsp;!
        </h3>
        <p className="mx-auto mt-3 max-w-md text-pretty text-ink/70">
          Votre demande d&apos;adhésion a bien été enregistrée. Vous recevrez une
          confirmation et les instructions de paiement par e-mail.
        </p>
        <p className="mt-6 inline-block rounded-full bg-cloud px-5 py-2 text-sm text-ink/70">
          Référence : <span className="font-semibold text-brand">{reference}</span>
        </p>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit(onSubmit)}
      className="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9"
    >
      {/* Stepper */}
      <ol className="mb-8 flex items-center gap-2">
        {stepLabels.map((label, i) => (
          <li key={label} className="flex flex-1 items-center gap-2">
            <span
              className={cn(
                "inline-flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-colors",
                i < step
                  ? "bg-accent text-white"
                  : i === step
                    ? "bg-brand text-white"
                    : "bg-cloud text-ink/40",
              )}
            >
              {i < step ? <Check className="size-4" /> : i + 1}
            </span>
            <span
              className={cn(
                "hidden text-sm font-semibold sm:block",
                i === step ? "text-brand" : "text-ink/50",
              )}
            >
              {label}
            </span>
            {i < stepLabels.length - 1 && (
              <span className="ml-1 hidden h-px flex-1 bg-brand/10 sm:block" />
            )}
          </li>
        ))}
      </ol>

      <AnimatePresence mode="wait">
        <motion.div
          key={step}
          initial={{ opacity: 0, x: 16 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -16 }}
          transition={{ duration: 0.3, ease: [0.22, 1, 0.36, 1] }}
        >
          {/* Step 1 — Formule */}
          {step === 0 && (
            <div>
              <h3 className="text-lg font-bold text-brand">Choisissez votre formule</h3>
              <p className="mt-1 text-sm text-ink/60">
                Sélectionnez l&apos;adhésion adaptée à votre profil.
                {pricesProvisional && " Tarifs indicatifs, à confirmer."}
              </p>
              <div className="mt-5 grid gap-4 lg:grid-cols-3">
                {plans.map((p) => {
                  const active = selectedPlan === p.id;
                  return (
                    <button
                      type="button"
                      key={p.id}
                      onClick={() => setValue("plan", p.id, { shouldValidate: true })}
                      className={cn(
                        "flex flex-col rounded-2xl border p-5 text-left transition-all",
                        active
                          ? "border-accent bg-accent/[0.03] ring-2 ring-accent/30"
                          : "border-brand/12 hover:border-brand/30",
                      )}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <span className="font-display text-xl uppercase tracking-tight text-brand">
                          {p.name}
                        </span>
                        {p.highlight && (
                          <span className="shrink-0 rounded-full bg-brand px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-white">
                            Populaire
                          </span>
                        )}
                      </div>
                      <span className="mt-0.5 text-xs text-ink/55">{p.audience}</span>
                      <span className="mt-3 text-2xl font-bold text-brand">
                        {formatPrice(p.price)}{" "}
                        <span className="text-sm font-medium text-ink/60">
                          {currency}/{p.period}
                        </span>
                      </span>
                      <ul className="mt-4 space-y-1.5">
                        {p.features.map((f) => (
                          <li key={f} className="flex items-start gap-2 text-sm text-ink/70">
                            <Check className="mt-0.5 size-4 shrink-0 text-accent" />
                            {f}
                          </li>
                        ))}
                      </ul>
                      <span
                        className={cn(
                          "mt-4 inline-flex size-6 items-center justify-center self-end rounded-full border-2 transition-colors",
                          active ? "border-accent bg-accent text-white" : "border-brand/20",
                        )}
                      >
                        {active && <Check className="size-3.5" />}
                      </span>
                    </button>
                  );
                })}
              </div>
              {errors.plan && (
                <p className="mt-3 text-sm font-medium text-accent">{errors.plan.message}</p>
              )}
            </div>
          )}

          {/* Step 2 — Informations */}
          {step === 1 && (
            <div className="grid gap-4 sm:grid-cols-2">
              <TextField label="Prénom" id="prenom" error={errors.prenom?.message} {...register("prenom")} />
              <TextField label="Nom" id="nom" error={errors.nom?.message} {...register("nom")} />
              <TextField label="E-mail" id="email" type="email" error={errors.email?.message} {...register("email")} />
              <TextField
                label="Téléphone"
                id="telephone"
                inputMode="numeric"
                placeholder="0700000000"
                error={errors.telephone?.message}
                {...register("telephone")}
              />
              <div className="flex flex-col gap-1.5">
                <span className="text-sm font-semibold text-brand">Genre</span>
                <div className="flex gap-2">
                  {(["Homme", "Femme"] as const).map((g) => (
                    <button
                      type="button"
                      key={g}
                      onClick={() => setValue("genre", g, { shouldValidate: true })}
                      className={cn(
                        "flex-1 rounded-xl border px-4 py-3 text-sm font-medium transition-colors",
                        selectedGenre === g
                          ? "border-accent bg-accent/[0.04] text-brand"
                          : "border-brand/15 text-ink/70 hover:border-brand/30",
                      )}
                    >
                      {g}
                    </button>
                  ))}
                </div>
                {errors.genre && (
                  <span className="text-xs font-medium text-accent">{errors.genre.message}</span>
                )}
              </div>
              <TextField label="Ville" id="ville" placeholder="Abidjan" error={errors.ville?.message} {...register("ville")} />
              <SelectField label="Domaine d'activité" id="secteur" error={errors.secteur?.message} {...register("secteur")}>
                <option value="">Sélectionnez votre domaine…</option>
                {sectors.map((s) => (
                  <optgroup key={s.title} label={s.title}>
                    {s.items.map((it) => (
                      <option key={it} value={it}>
                        {it}
                      </option>
                    ))}
                  </optgroup>
                ))}
              </SelectField>
              <TextField
                label="Entreprise / projet (facultatif)"
                id="organisation"
                error={errors.organisation?.message}
                {...register("organisation")}
              />
              <div className="sm:col-span-2">
                <TextareaField
                  label="Votre projet en quelques mots (facultatif)"
                  id="message"
                  error={errors.message?.message}
                  {...register("message")}
                />
              </div>
            </div>
          )}

          {/* Step 3 — Paiement */}
          {step === 2 && (
            <div>
              {plan && (
                <div className="flex items-center justify-between rounded-2xl bg-cloud p-5">
                  <div>
                    <p className="text-sm text-ink/60">Formule sélectionnée</p>
                    <p className="font-display text-xl uppercase tracking-tight text-brand">
                      {plan.name}
                    </p>
                  </div>
                  <p className="text-2xl font-bold text-brand">
                    {formatPrice(plan.price)}{" "}
                    <span className="text-sm font-medium text-ink/60">
                      {currency}/{plan.period}
                    </span>
                  </p>
                </div>
              )}

              <h3 className="mt-6 text-lg font-bold text-brand">Moyen de paiement</h3>
              <div className="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                {paymentMethods.map((m) => (
                  <button
                    type="button"
                    key={m.id}
                    onClick={() => setValue("paiement", m.id, { shouldValidate: true })}
                    className={cn(
                      "rounded-xl border px-4 py-3.5 text-sm font-semibold transition-colors",
                      selectedPayment === m.id
                        ? "border-accent bg-accent/[0.04] text-brand"
                        : "border-brand/15 text-ink/70 hover:border-brand/30",
                    )}
                  >
                    {m.label}
                  </button>
                ))}
              </div>
              {errors.paiement && (
                <p className="mt-2 text-sm font-medium text-accent">{errors.paiement.message}</p>
              )}

              <label className="mt-6 flex items-start gap-3 text-sm text-ink/75">
                <input
                  type="checkbox"
                  {...register("consent")}
                  className="mt-0.5 size-5 shrink-0 accent-[var(--color-accent)]"
                />
                <span>
                  J&apos;accepte d&apos;adhérer au REJCC et de recevoir les communications
                  liées à mon adhésion.
                </span>
              </label>
              {errors.consent && (
                <p className="mt-2 text-sm font-medium text-accent">
                  {errors.consent.message}
                </p>
              )}

              <p className="mt-6 flex items-center gap-2 rounded-xl bg-brand/5 px-4 py-3 text-xs text-ink/60">
                <ShieldCheck className="size-4 shrink-0 text-brand" />
                Paiement en cours d&apos;intégration : votre demande est enregistrée et
                l&apos;équipe vous transmet les instructions de règlement.
              </p>

              {status === "error" && (
                <p className="mt-3 text-sm font-medium text-accent">{serverError}</p>
              )}
            </div>
          )}
        </motion.div>
      </AnimatePresence>

      {/* Navigation */}
      <div className="mt-8 flex items-center justify-between gap-3">
        {step > 0 ? (
          <button
            type="button"
            onClick={back}
            className="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold text-brand transition-colors hover:bg-brand/5"
          >
            <ArrowLeft className="size-4" /> Précédent
          </button>
        ) : (
          <span />
        )}

        {step < 2 ? (
          <button
            type="button"
            onClick={next}
            className="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700"
          >
            Continuer <ArrowRight className="size-4" />
          </button>
        ) : (
          <button
            type="submit"
            disabled={status === "submitting"}
            className="inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70"
          >
            {status === "submitting" ? (
              <>
                <Loader2 className="size-4 animate-spin" /> Envoi…
              </>
            ) : (
              <>
                Finaliser mon adhésion <ArrowRight className="size-4" />
              </>
            )}
          </button>
        )}
      </div>
    </form>
  );
}

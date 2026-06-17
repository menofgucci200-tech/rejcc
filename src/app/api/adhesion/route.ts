import { NextResponse } from "next/server";
import { adhesionSchema } from "@/lib/validation/schemas";

export async function POST(req: Request) {
  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ ok: false, message: "Requête invalide." }, { status: 400 });
  }

  const parsed = adhesionSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json(
      { ok: false, message: parsed.error.issues[0]?.message ?? "Données invalides." },
      { status: 400 },
    );
  }

  const data = parsed.data;
  const reference =
    "REJCC-" +
    Date.now().toString(36).toUpperCase().slice(-5) +
    "-" +
    Math.random().toString(36).toUpperCase().slice(2, 5);

  // TODO Phase 2b — brancher l'API Laravel :
  //  1. Enregistrer le membre (table `members`, statut "en attente de paiement").
  //  2. Initier le paiement Mobile Money selon `data.paiement`
  //     (MTN/Orange/Moov/Wave) — cf. EPCCI_BACKEND/paiements/mtn_ci_webhook.php.
  //  3. Envoyer l'e-mail de confirmation + instructions de règlement.
  console.log("[adhesion]", reference, data.plan, data.email);

  return NextResponse.json({ ok: true, reference });
}

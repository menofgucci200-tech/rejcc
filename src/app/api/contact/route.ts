import { NextResponse } from "next/server";
import { contactSchema } from "@/lib/validation/schemas";

export async function POST(req: Request) {
  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ ok: false, message: "Requête invalide." }, { status: 400 });
  }

  const parsed = contactSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json(
      { ok: false, message: parsed.error.issues[0]?.message ?? "Données invalides." },
      { status: 400 },
    );
  }

  // TODO Phase 2b — enregistrer le message (API Laravel) et notifier l'équipe par e-mail.
  console.log("[contact]", parsed.data.email, parsed.data.sujet);

  return NextResponse.json({ ok: true });
}

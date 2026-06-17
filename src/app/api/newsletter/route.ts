import { NextResponse } from "next/server";
import { newsletterSchema } from "@/lib/validation/schemas";

export async function POST(req: Request) {
  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ ok: false, message: "Requête invalide." }, { status: 400 });
  }

  const parsed = newsletterSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json(
      { ok: false, message: parsed.error.issues[0]?.message ?? "Adresse invalide." },
      { status: 400 },
    );
  }

  // TODO Phase 2b — enregistrer l'inscription (API Laravel) / connecter un service d'e-mailing.
  console.log("[newsletter]", parsed.data.email);

  return NextResponse.json({ ok: true });
}

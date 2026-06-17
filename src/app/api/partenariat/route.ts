import { NextResponse } from "next/server";
import { partenariatSchema } from "@/lib/validation/schemas";
import { forwardToBackend } from "@/lib/api/backend";

export async function POST(req: Request) {
  let body: unknown;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ ok: false, message: "Requête invalide." }, { status: 400 });
  }

  const parsed = partenariatSchema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json(
      { ok: false, message: parsed.error.issues[0]?.message ?? "Données invalides." },
      { status: 400 },
    );
  }

  const forwarded = await forwardToBackend("partenariat", parsed.data);
  if (forwarded) return NextResponse.json(forwarded.json, { status: forwarded.status });

  console.log("[partenariat]", parsed.data.organisation, parsed.data.type);
  return NextResponse.json({ ok: true });
}

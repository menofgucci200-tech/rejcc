import { NextResponse } from "next/server";
import { newsletterSchema } from "@/lib/validation/schemas";
import { forwardToBackend } from "@/lib/api/backend";

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

  const forwarded = await forwardToBackend("newsletter", parsed.data);
  if (forwarded) return NextResponse.json(forwarded.json, { status: forwarded.status });

  console.log("[newsletter]", parsed.data.email);
  return NextResponse.json({ ok: true });
}

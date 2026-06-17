/**
 * Relais optionnel vers l'API Laravel (rejcc-api).
 *
 * Si la variable d'environnement `REJCC_API_URL` est définie, les routes API
 * Next.js transmettent les données validées au backend Laravel (persistance
 * MySQL, paiement, e-mails). Sinon, elles fonctionnent en autonomie (validation
 * + accusé de réception) — comportement actuel tant que le backend n'est pas
 * hébergé.
 */
const backendUrl = process.env.REJCC_API_URL?.replace(/\/+$/, "");

export function isBackendConfigured() {
  return Boolean(backendUrl);
}

export async function forwardToBackend(
  path: string,
  data: unknown,
): Promise<{ status: number; json: Record<string, unknown> } | null> {
  if (!backendUrl) return null;
  try {
    const res = await fetch(`${backendUrl}/api/${path}`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify(data),
    });
    const json = (await res.json().catch(() => ({}))) as Record<string, unknown>;
    return { status: res.status, json };
  } catch {
    return { status: 502, json: { ok: false, message: "Service momentanément indisponible." } };
  }
}

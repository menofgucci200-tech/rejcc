/**
 * Client API côté navigateur pour l'espace membre (appelle directement le
 * backend Laravel). L'URL publique est fournie par NEXT_PUBLIC_API_URL.
 *
 * En développement : NEXT_PUBLIC_API_URL=http://127.0.0.1:8000 (.env.local).
 * En production : à définir une fois le backend hébergé. Tant qu'elle est
 * absente, `apiConfigured()` est faux et l'espace membre affiche un message.
 */
const API = process.env.NEXT_PUBLIC_API_URL?.replace(/\/+$/, "");

export type Member = {
  id: number;
  prenom: string;
  nom: string;
  email: string;
  telephone?: string;
  genre?: string | null;
  ville?: string | null;
  secteur?: string | null;
  profil?: string | null;
  organisation?: string | null;
  bio?: string | null;
  photo?: string | null;
  role?: string;
};

export function apiConfigured(): boolean {
  return Boolean(API);
}

async function request<T>(
  path: string,
  options: RequestInit & { token?: string } = {},
): Promise<T> {
  if (!API) throw new Error("L'espace membre n'est pas encore disponible en ligne.");
  const { token, headers, ...rest } = options;
  const res = await fetch(`${API}/api${path}`, {
    ...rest,
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...headers,
    },
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json?.ok === false) {
    throw new Error(json?.message || "Une erreur est survenue.");
  }
  return json as T;
}

type AuthResponse = { ok: true; token: string; user: Member };

export const authApi = {
  register: (data: Record<string, unknown>) =>
    request<AuthResponse>("/auth/register", { method: "POST", body: JSON.stringify(data) }),
  login: (data: { email: string; password: string }) =>
    request<AuthResponse>("/auth/login", { method: "POST", body: JSON.stringify(data) }),
  me: (token: string) => request<{ ok: true; user: Member }>("/auth/me", { token }),
  logout: (token: string) =>
    request<{ ok: true }>("/auth/logout", { method: "POST", token }),
  updateProfile: (token: string, data: Record<string, unknown>) =>
    request<{ ok: true; user: Member }>("/auth/profile", {
      method: "PUT",
      token,
      body: JSON.stringify(data),
    }),
  members: (token: string) =>
    request<{ ok: true; members: Member[] }>("/members", { token }),
};

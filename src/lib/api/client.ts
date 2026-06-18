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

export type Conversation = {
  user_id: number;
  prenom: string;
  nom: string;
  last: string;
  at: string;
  unread: number;
};
export type ChatMessage = {
  id: number;
  sender_id: number;
  recipient_id: number;
  body: string;
  created_at: string;
};
export type Notice = {
  id: number;
  type: string;
  title: string;
  body?: string | null;
  link?: string | null;
  read_at?: string | null;
  created_at: string;
};
export type DocItem = {
  id: number;
  title: string;
  description?: string | null;
  category: string;
  url: string;
  size?: string | null;
};

export const memberApi = {
  conversations: (token: string) =>
    request<{ ok: true; conversations: Conversation[] }>("/messages", { token }),
  thread: (token: string, userId: number) =>
    request<{ ok: true; me: number; partner: Member; messages: ChatMessage[] }>(
      `/messages/${userId}`,
      { token },
    ),
  sendMessage: (token: string, recipientId: number, body: string) =>
    request<{ ok: true }>("/messages", {
      method: "POST",
      token,
      body: JSON.stringify({ recipient_id: recipientId, body }),
    }),
  notifications: (token: string) =>
    request<{ ok: true; unread: number; notifications: Notice[] }>("/notifications", { token }),
  markAllNotificationsRead: (token: string) =>
    request<{ ok: true }>("/notifications/read-all", { method: "POST", token }),
  documents: (token: string) =>
    request<{ ok: true; documents: DocItem[] }>("/documents", { token }),
};

"use client";

import {
  createContext,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";
import { authApi, apiConfigured, type Member } from "@/lib/api/client";

const TOKEN_KEY = "rejcc-token";

type AuthState = {
  user: Member | null;
  token: string | null;
  loading: boolean;
  configured: boolean;
  login: (email: string, password: string) => Promise<Member>;
  register: (data: Record<string, unknown>) => Promise<Member>;
  logout: () => Promise<void>;
  setUser: (u: Member) => void;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<Member | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const t =
      typeof window !== "undefined" ? localStorage.getItem(TOKEN_KEY) : null;
    if (t && apiConfigured()) {
      setToken(t);
      authApi
        .me(t)
        .then((r) => setUser(r.user))
        .catch(() => {
          localStorage.removeItem(TOKEN_KEY);
          setToken(null);
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  function persist(res: { token: string; user: Member }) {
    localStorage.setItem(TOKEN_KEY, res.token);
    setToken(res.token);
    setUser(res.user);
  }

  async function login(email: string, password: string): Promise<Member> {
    const res = await authApi.login({ email, password });
    persist(res);
    return res.user;
  }

  async function register(data: Record<string, unknown>): Promise<Member> {
    const res = await authApi.register(data);
    persist(res);
    return res.user;
  }

  async function logout() {
    if (token) await authApi.logout(token).catch(() => {});
    localStorage.removeItem(TOKEN_KEY);
    setToken(null);
    setUser(null);
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        configured: apiConfigured(),
        login,
        register,
        logout,
        setUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth doit être utilisé dans <AuthProvider>");
  return ctx;
}

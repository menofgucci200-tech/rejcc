"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { ArrowLeft, Send, Loader2, MessageCircle, Users } from "lucide-react";
import { useAuth } from "@/lib/auth/AuthContext";
import {
  memberApi,
  type Conversation,
  type ChatMessage,
} from "@/lib/api/client";
import { Container } from "@/components/ui/Container";
import { cn } from "@/lib/utils";

function time(s: string) {
  return new Date(s).toLocaleString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function MessagingView() {
  const { token, user } = useAuth();
  const [convos, setConvos] = useState<Conversation[]>([]);
  const [activeId, setActiveId] = useState<number | null>(null);
  const [partner, setPartner] = useState<{ prenom: string; nom: string } | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [body, setBody] = useState("");
  const [loadingConvos, setLoadingConvos] = useState(true);
  const [loadingThread, setLoadingThread] = useState(false);
  const me = user?.id;

  function loadConvos() {
    if (!token) return;
    memberApi
      .conversations(token)
      .then((r) => setConvos(r.conversations))
      .finally(() => setLoadingConvos(false));
  }

  useEffect(loadConvos, [token]);

  // Ouvre une conversation passée via ?to=<id> (depuis l'annuaire).
  useEffect(() => {
    if (!token) return;
    const to = new URLSearchParams(window.location.search).get("to");
    if (to) openThread(Number(to));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token]);

  async function openThread(uid: number) {
    if (!token) return;
    setActiveId(uid);
    setLoadingThread(true);
    try {
      const r = await memberApi.thread(token, uid);
      setPartner(r.partner);
      setMessages(r.messages);
    } finally {
      setLoadingThread(false);
    }
  }

  async function onSend(e: React.FormEvent) {
    e.preventDefault();
    if (!token || !activeId || !body.trim()) return;
    const text = body.trim();
    setBody("");
    await memberApi.sendMessage(token, activeId, text);
    await openThread(activeId);
    loadConvos();
  }

  return (
    <>
      <header className="relative overflow-hidden bg-brand pb-12 pt-36 sm:pt-44">
        <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.22] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]" />
        <Container className="relative">
          <Link
            href="/espace-membre"
            className="inline-flex items-center gap-2 text-sm text-white/60 transition-colors hover:text-white"
          >
            <ArrowLeft className="size-4" /> Tableau de bord
          </Link>
          <h1 className="mt-5 font-display text-[clamp(2rem,5vw,3.25rem)] uppercase leading-none tracking-tight text-white">
            Messagerie
          </h1>
        </Container>
      </header>

      <section className="bg-cloud py-12 sm:py-16">
        <Container>
          <div className="grid gap-4 lg:grid-cols-[330px_1fr]">
            {/* Conversations */}
            <aside
              className={cn(
                "rounded-3xl border border-brand/10 bg-white p-3",
                activeId ? "hidden lg:block" : "block",
              )}
            >
              {loadingConvos ? (
                <div className="flex justify-center py-10">
                  <Loader2 className="size-6 animate-spin text-brand" />
                </div>
              ) : convos.length === 0 ? (
                <div className="px-3 py-8 text-center text-sm text-ink/55">
                  <MessageCircle className="mx-auto mb-3 size-7 text-brand/30" />
                  Aucune conversation. Démarrez-en une depuis l&apos;annuaire.
                  <Link
                    href="/espace-membre/annuaire"
                    className="mt-4 inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-xs font-semibold text-white"
                  >
                    <Users className="size-3.5" /> Voir l&apos;annuaire
                  </Link>
                </div>
              ) : (
                <ul className="flex flex-col">
                  {convos.map((c) => (
                    <li key={c.user_id}>
                      <button
                        onClick={() => openThread(c.user_id)}
                        className={cn(
                          "flex w-full items-center gap-3 rounded-2xl p-3 text-left transition-colors",
                          activeId === c.user_id ? "bg-brand/5" : "hover:bg-cloud",
                        )}
                      >
                        <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">
                          {c.prenom?.[0]}
                          {c.nom?.[0]}
                        </span>
                        <span className="min-w-0 flex-1">
                          <span className="flex items-center justify-between gap-2">
                            <span className="truncate font-semibold text-brand">
                              {c.prenom} {c.nom}
                            </span>
                            {c.unread > 0 && (
                              <span className="inline-flex size-5 items-center justify-center rounded-full bg-accent text-[0.65rem] font-bold text-white">
                                {c.unread}
                              </span>
                            )}
                          </span>
                          <span className="block truncate text-sm text-ink/55">{c.last}</span>
                        </span>
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </aside>

            {/* Fil */}
            <section
              className={cn(
                "min-h-[460px] flex-col rounded-3xl border border-brand/10 bg-white",
                activeId ? "flex" : "hidden lg:flex",
              )}
            >
              {!activeId ? (
                <div className="flex flex-1 items-center justify-center p-10 text-center text-ink/50">
                  Sélectionnez une conversation pour afficher les messages.
                </div>
              ) : (
                <>
                  <div className="flex items-center gap-3 border-b border-brand/10 p-4">
                    <button
                      onClick={() => setActiveId(null)}
                      className="lg:hidden"
                      aria-label="Retour"
                    >
                      <ArrowLeft className="size-5 text-brand" />
                    </button>
                    <span className="inline-flex size-10 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">
                      {partner?.prenom?.[0]}
                      {partner?.nom?.[0]}
                    </span>
                    <p className="font-bold text-brand">
                      {partner ? `${partner.prenom} ${partner.nom}` : ""}
                    </p>
                  </div>

                  <div className="flex flex-1 flex-col gap-2.5 overflow-y-auto p-4">
                    {loadingThread ? (
                      <div className="flex flex-1 items-center justify-center">
                        <Loader2 className="size-6 animate-spin text-brand" />
                      </div>
                    ) : (
                      messages.map((m) => {
                        const mine = m.sender_id === me;
                        return (
                          <div
                            key={m.id}
                            className={cn("flex flex-col", mine ? "items-end" : "items-start")}
                          >
                            <div
                              className={cn(
                                "max-w-[80%] rounded-2xl px-4 py-2.5 text-sm",
                                mine
                                  ? "bg-brand text-white"
                                  : "bg-cloud text-ink",
                              )}
                            >
                              {m.body}
                            </div>
                            <span className="mt-1 px-1 text-[0.7rem] text-ink/40">
                              {time(m.created_at)}
                            </span>
                          </div>
                        );
                      })
                    )}
                  </div>

                  <form onSubmit={onSend} className="flex items-center gap-2 border-t border-brand/10 p-3">
                    <input
                      value={body}
                      onChange={(e) => setBody(e.target.value)}
                      placeholder="Votre message…"
                      className="min-w-0 flex-1 rounded-full border border-brand/15 bg-white px-4 py-2.5 text-sm text-brand outline-none focus:border-brand focus:ring-2 focus:ring-accent/20"
                    />
                    <button
                      type="submit"
                      aria-label="Envoyer"
                      className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-accent text-white transition-colors hover:bg-accent-600"
                    >
                      <Send className="size-4" />
                    </button>
                  </form>
                </>
              )}
            </section>
          </div>
        </Container>
      </section>
    </>
  );
}

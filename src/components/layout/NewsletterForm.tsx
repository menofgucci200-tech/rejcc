"use client";

import { useState } from "react";
import { ArrowRight, Check, Loader2 } from "lucide-react";

export function NewsletterForm() {
  const [email, setEmail] = useState("");
  const [sent, setSent] = useState(false);
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!email || loading) return;
    setLoading(true);
    try {
      const res = await fetch("/api/newsletter", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });
      if (res.ok) {
        setSent(true);
        setEmail("");
        setTimeout(() => setSent(false), 5000);
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="mt-4">
      <div className="flex items-center gap-2 rounded-full border border-white/15 bg-white/5 p-1.5 backdrop-blur">
        <input
          type="email"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="Votre adresse e-mail"
          aria-label="Votre adresse e-mail"
          className="min-w-0 flex-1 bg-transparent px-4 py-2 text-sm text-white placeholder:text-white/40 focus:outline-none"
        />
        <button
          type="submit"
          disabled={loading}
          aria-label="S'inscrire à la newsletter"
          className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-accent text-white transition-colors hover:bg-accent-600 disabled:opacity-70"
        >
          {loading ? (
            <Loader2 className="size-4 animate-spin" />
          ) : sent ? (
            <Check className="size-4" />
          ) : (
            <ArrowRight className="size-4" />
          )}
        </button>
      </div>
      {sent && (
        <p className="mt-2 px-2 text-xs text-white/70">
          Merci ! Votre inscription sera confirmée prochainement.
        </p>
      )}
    </form>
  );
}

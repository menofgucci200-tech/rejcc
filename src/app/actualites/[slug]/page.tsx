import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, Clock, Calendar, User } from "lucide-react";
import { Container } from "@/components/ui/Container";
import { LogoMark } from "@/components/ui/LogoMark";
import { Button } from "@/components/ui/Button";
import { articles, getArticle } from "@/lib/content/news";

export function generateStaticParams() {
  return articles.map((a) => ({ slug: a.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const article = getArticle(slug);
  if (!article) return { title: "Article introuvable" };
  return { title: article.title, description: article.excerpt };
}

export default async function ArticlePage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const article = getArticle(slug);
  if (!article) notFound();

  const related = articles
    .filter((a) => a.slug !== article.slug && a.category === article.category)
    .slice(0, 2);

  return (
    <article>
      {/* En-tête */}
      <header className="relative overflow-hidden bg-brand pb-14 pt-36 sm:pt-44">
        <div className="pointer-events-none absolute inset-0 bg-grid opacity-[0.22] [mask-image:radial-gradient(ellipse_at_top,black,transparent_75%)]" />
        <div className="pointer-events-none absolute -right-[8%] -top-[10%] size-[40vmax] rounded-full bg-azure/20 blur-[120px]" />
        <Container className="relative max-w-3xl">
          <Link
            href="/actualites"
            className="inline-flex items-center gap-2 text-sm text-white/60 transition-colors hover:text-white"
          >
            <ArrowLeft className="size-4" /> Toutes les actualités
          </Link>
          <span className="mt-6 inline-block rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand">
            {article.category}
          </span>
          <h1 className="mt-4 font-display uppercase leading-[0.95] tracking-tight text-white text-[clamp(1.9rem,5vw,3.25rem)]">
            {article.title}
          </h1>
          <div className="mt-6 flex flex-wrap items-center gap-5 text-sm text-white/70">
            <span className="flex items-center gap-2">
              <User className="size-4" /> {article.author}
            </span>
            <span className="flex items-center gap-2">
              <Calendar className="size-4" /> {article.date}
            </span>
            <span className="flex items-center gap-2">
              <Clock className="size-4" /> {article.readingTime}
            </span>
          </div>
        </Container>
      </header>

      {/* Corps */}
      <div className="bg-white py-16 sm:py-20">
        <Container className="max-w-3xl">
          <div className="relative mb-10 aspect-[16/8] overflow-hidden rounded-3xl bg-brand">
            <div className="absolute inset-0 bg-grid opacity-20" />
            <div className="absolute inset-0 bg-linear-to-br from-brand via-brand to-brand-900" />
            <div className="absolute inset-0 flex items-center justify-center">
              <LogoMark kind="mono-white" className="h-20 opacity-80" />
            </div>
          </div>

          <div className="space-y-5 text-lg leading-relaxed text-ink/80">
            {article.body.map((p, i) => (
              <p key={i}>{p}</p>
            ))}
          </div>

          <div className="mt-12 border-t border-brand/10 pt-8">
            <Button href="/adhesion" variant="primary" withArrow>
              Rejoindre le réseau
            </Button>
          </div>
        </Container>
      </div>

      {/* Articles liés */}
      {related.length > 0 && (
        <div className="bg-cloud py-16 sm:py-20">
          <Container className="max-w-3xl">
            <h2 className="font-display text-2xl uppercase tracking-tight text-brand">
              À lire aussi
            </h2>
            <div className="mt-6 grid gap-4 sm:grid-cols-2">
              {related.map((a) => (
                <Link
                  key={a.slug}
                  href={`/actualites/${a.slug}`}
                  className="group rounded-2xl border border-brand/10 bg-white p-6 transition-all hover:-translate-y-1 hover:shadow-[0_24px_50px_-30px_rgba(3,29,89,0.4)]"
                >
                  <span className="text-xs font-semibold uppercase tracking-wide text-accent">
                    {a.category}
                  </span>
                  <h3 className="mt-2 font-bold text-brand group-hover:text-accent">
                    {a.title}
                  </h3>
                  <p className="mt-1.5 text-sm text-ink/65">{a.excerpt}</p>
                </Link>
              ))}
            </div>
          </Container>
        </div>
      )}
    </article>
  );
}

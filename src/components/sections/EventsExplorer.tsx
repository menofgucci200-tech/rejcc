"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { ChevronLeft, ChevronRight, MapPin, Clock, ArrowRight } from "lucide-react";
import { cn } from "@/lib/utils";
import { events, eventTypes } from "@/lib/content/events";

const WEEKDAYS = ["L", "M", "M", "J", "V", "S", "D"];

function monthLabel(d: Date) {
  return d.toLocaleDateString("fr-FR", { month: "long", year: "numeric" });
}

export function EventsExplorer() {
  // Démarre sur le mois du premier événement à venir.
  const firstIso = [...events].sort((a, b) => (a.iso < b.iso ? -1 : 1))[0]?.iso;
  const initial = firstIso ? new Date(firstIso + "T00:00:00") : new Date();
  const [cursor, setCursor] = useState(new Date(initial.getFullYear(), initial.getMonth(), 1));
  const [type, setType] = useState<string>("Tous");
  const [selectedDay, setSelectedDay] = useState<string | null>(null);

  const types = ["Tous", ...eventTypes];

  const byType = useMemo(
    () => events.filter((e) => type === "Tous" || e.type === type),
    [type],
  );

  const eventDays = useMemo(() => new Set(byType.map((e) => e.iso)), [byType]);

  const year = cursor.getFullYear();
  const month = cursor.getMonth();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const startWeekday = (new Date(year, month, 1).getDay() + 6) % 7; // Lundi = 0

  const cells: (string | null)[] = [
    ...Array(startWeekday).fill(null),
    ...Array.from({ length: daysInMonth }, (_, i) => {
      const dd = String(i + 1).padStart(2, "0");
      const mm = String(month + 1).padStart(2, "0");
      return `${year}-${mm}-${dd}`;
    }),
  ];

  const agenda = useMemo(() => {
    return byType
      .filter((e) => {
        if (selectedDay) return e.iso === selectedDay;
        const d = new Date(e.iso + "T00:00:00");
        return d.getFullYear() === year && d.getMonth() === month;
      })
      .sort((a, b) => (a.iso < b.iso ? -1 : 1));
  }, [byType, year, month, selectedDay]);

  function shiftMonth(delta: number) {
    setSelectedDay(null);
    setCursor(new Date(year, month + delta, 1));
  }

  return (
    <div>
      {/* Filtres par type */}
      <div className="flex flex-wrap gap-2">
        {types.map((t) => (
          <button
            key={t}
            onClick={() => {
              setType(t);
              setSelectedDay(null);
            }}
            className={cn(
              "rounded-full px-4 py-2 text-sm font-semibold transition-colors",
              type === t
                ? "bg-brand text-white"
                : "border border-brand/15 text-ink/70 hover:border-brand/30 hover:text-brand",
            )}
          >
            {t}
          </button>
        ))}
      </div>

      <div className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,360px)_1fr]">
        {/* Calendrier */}
        <div className="rounded-3xl border border-brand/10 bg-white p-6">
          <div className="flex items-center justify-between">
            <span className="font-display text-lg uppercase tracking-tight text-brand first-letter:uppercase">
              {monthLabel(cursor)}
            </span>
            <div className="flex gap-1">
              <button
                onClick={() => shiftMonth(-1)}
                aria-label="Mois précédent"
                className="inline-flex size-9 items-center justify-center rounded-full border border-brand/15 text-brand transition-colors hover:bg-brand hover:text-white"
              >
                <ChevronLeft className="size-4" />
              </button>
              <button
                onClick={() => shiftMonth(1)}
                aria-label="Mois suivant"
                className="inline-flex size-9 items-center justify-center rounded-full border border-brand/15 text-brand transition-colors hover:bg-brand hover:text-white"
              >
                <ChevronRight className="size-4" />
              </button>
            </div>
          </div>

          <div className="mt-5 grid grid-cols-7 gap-1 text-center text-xs font-semibold text-ink/40">
            {WEEKDAYS.map((w, i) => (
              <span key={i} className="py-1">
                {w}
              </span>
            ))}
          </div>
          <div className="mt-1 grid grid-cols-7 gap-1">
            {cells.map((iso, i) => {
              if (!iso) return <span key={i} />;
              const has = eventDays.has(iso);
              const day = Number(iso.slice(-2));
              const active = selectedDay === iso;
              return (
                <button
                  key={i}
                  disabled={!has}
                  onClick={() => setSelectedDay(active ? null : iso)}
                  className={cn(
                    "relative flex aspect-square items-center justify-center rounded-xl text-sm transition-colors",
                    active
                      ? "bg-accent text-white"
                      : has
                        ? "bg-brand/5 font-semibold text-brand hover:bg-brand hover:text-white"
                        : "text-ink/40",
                  )}
                >
                  {day}
                  {has && !active && (
                    <span className="absolute bottom-1 size-1 rounded-full bg-accent" />
                  )}
                </button>
              );
            })}
          </div>
          {selectedDay && (
            <button
              onClick={() => setSelectedDay(null)}
              className="mt-4 text-xs font-semibold text-accent hover:underline"
            >
              Réinitialiser le jour sélectionné
            </button>
          )}
        </div>

        {/* Agenda */}
        <div>
          {agenda.length === 0 ? (
            <div className="flex h-full min-h-48 items-center justify-center rounded-3xl border border-dashed border-brand/15 text-center text-ink/55">
              Aucun événement {selectedDay ? "ce jour" : "ce mois-ci"}. Naviguez
              entre les mois pour en découvrir d&apos;autres.
            </div>
          ) : (
            <div className="flex flex-col gap-4">
              {agenda.map((e) => (
                <Link
                  key={e.slug}
                  href={`/evenements/${e.slug}`}
                  className="group flex items-stretch gap-5 rounded-3xl border border-brand/10 bg-white p-5 transition-all duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-35px_rgba(3,29,89,0.4)]"
                >
                  <div className="flex w-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-brand py-4 text-white">
                    <span className="font-display text-3xl leading-none">{e.day}</span>
                    <span className="mt-1 text-xs uppercase tracking-wider text-white/70">
                      {e.month}
                    </span>
                  </div>
                  <div className="flex min-w-0 flex-1 flex-col justify-center">
                    <span className="inline-flex w-fit rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-accent">
                      {e.type}
                    </span>
                    <h3 className="mt-2 text-lg font-bold text-brand">{e.title}</h3>
                    <p className="mt-1 line-clamp-1 text-sm text-ink/65">{e.excerpt}</p>
                    <p className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink/55">
                      <span className="flex items-center gap-1.5">
                        <MapPin className="size-3.5" /> {e.location}
                      </span>
                      <span className="flex items-center gap-1.5">
                        <Clock className="size-3.5" /> {e.time}
                      </span>
                    </p>
                  </div>
                  <ArrowRight className="size-5 self-center text-brand/20 transition-all duration-500 group-hover:translate-x-1 group-hover:text-accent" />
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

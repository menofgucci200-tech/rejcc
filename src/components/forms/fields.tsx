import { forwardRef } from "react";
import { cn } from "@/lib/utils";

export const fieldBase =
  "w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2";

function Wrapper({
  label,
  htmlFor,
  error,
  hint,
  children,
}: {
  label: string;
  htmlFor?: string;
  error?: string;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={htmlFor} className="text-sm font-semibold text-brand">
        {label}
      </label>
      {children}
      {error ? (
        <span className="text-xs font-medium text-accent">{error}</span>
      ) : hint ? (
        <span className="text-xs text-ink/50">{hint}</span>
      ) : null}
    </div>
  );
}

type InputProps = React.InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  error?: string;
  hint?: string;
};

export const TextField = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, hint, className, id, ...props }, ref) => (
    <Wrapper label={label} htmlFor={id} error={error} hint={hint}>
      <input
        id={id}
        ref={ref}
        aria-invalid={!!error}
        className={cn(
          fieldBase,
          error
            ? "border-accent focus:border-accent focus:ring-accent/25"
            : "border-brand/15 focus:border-brand focus:ring-accent/20",
          className,
        )}
        {...props}
      />
    </Wrapper>
  ),
);
TextField.displayName = "TextField";

type TextareaProps = React.TextareaHTMLAttributes<HTMLTextAreaElement> & {
  label: string;
  error?: string;
  hint?: string;
};

export const TextareaField = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, error, hint, className, id, ...props }, ref) => (
    <Wrapper label={label} htmlFor={id} error={error} hint={hint}>
      <textarea
        id={id}
        ref={ref}
        aria-invalid={!!error}
        className={cn(
          fieldBase,
          "min-h-32 resize-y",
          error
            ? "border-accent focus:border-accent focus:ring-accent/25"
            : "border-brand/15 focus:border-brand focus:ring-accent/20",
          className,
        )}
        {...props}
      />
    </Wrapper>
  ),
);
TextareaField.displayName = "TextareaField";

type SelectProps = React.SelectHTMLAttributes<HTMLSelectElement> & {
  label: string;
  error?: string;
  hint?: string;
};

export const SelectField = forwardRef<HTMLSelectElement, SelectProps>(
  ({ label, error, hint, className, id, children, ...props }, ref) => (
    <Wrapper label={label} htmlFor={id} error={error} hint={hint}>
      <select
        id={id}
        ref={ref}
        aria-invalid={!!error}
        className={cn(
          fieldBase,
          "pr-10",
          error
            ? "border-accent focus:border-accent focus:ring-accent/25"
            : "border-brand/15 focus:border-brand focus:ring-accent/20",
          className,
        )}
        {...props}
      >
        {children}
      </select>
    </Wrapper>
  ),
);
SelectField.displayName = "SelectField";

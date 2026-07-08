<div>
    <x-page-header eyebrow="Espace membre" crumb="Suivre ma candidature" subtitle="Vérifiez l'état de votre demande d'adhésion au REJCC.">
        Suivre ma <span class="font-serif italic normal-case text-azure">candidature</span>
    </x-page-header>

    <section class="bg-cloud py-16 sm:py-24">
        <x-ui.container class="max-w-md">
            <div class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
                <form wire:submit="check" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="statut-email" class="text-sm font-semibold text-brand">E-mail utilisé lors de votre adhésion</label>
                        <input wire:model="email" id="statut-email" type="email" required autofocus
                            class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                        @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-accent px-6 py-3.5 font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                        <span wire:loading.remove>Vérifier</span>
                        <span wire:loading>Vérification…</span>
                    </button>
                </form>

                @if ($checked && $statut)
                    <div @class([
                        'mt-6 rounded-2xl border p-5',
                        'border-emerald-200 bg-emerald-50' => $statut === 'accepte',
                        'border-accent/20 bg-accent/5' => $statut === 'refuse',
                        'border-brand/15 bg-cloud' => ! in_array($statut, ['accepte', 'refuse'], true),
                    ])>
                        <p class="text-sm font-semibold text-brand">{{ $nomPrenoms }}</p>

                        @if ($statut === 'accepte')
                            <p class="mt-1 text-sm text-emerald-700">Votre candidature a été <strong>acceptée</strong> ! Vous pouvez vous connecter avec l'e-mail et le mot de passe renseignés lors de votre adhésion.</p>
                            <a href="{{ route('login') }}" wire:navigate class="mt-3 inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand/90">Se connecter</a>
                        @elseif ($statut === 'refuse')
                            <p class="mt-1 text-sm text-ink/70">Votre candidature n'a pas été retenue. N'hésitez pas à <a href="/contact" class="font-semibold text-brand hover:text-accent">nous contacter</a> pour plus d'informations.</p>
                        @else
                            <p class="mt-1 text-sm text-ink/70">Votre candidature est <strong>en cours d'examen</strong> par notre équipe. Nous reviendrons vers vous rapidement.</p>
                        @endif
                    </div>
                @endif
            </div>
        </x-ui.container>
    </section>
</div>

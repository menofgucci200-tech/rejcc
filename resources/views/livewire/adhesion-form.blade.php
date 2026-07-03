@php $stepLabels = ['Vos informations', 'Paiement']; @endphp

<div>
@if ($status === 'success')
    <div class="rounded-3xl border border-brand/10 bg-white p-8 text-center sm:p-12">
        <div class="mx-auto inline-flex size-16 items-center justify-center rounded-full bg-accent/10 text-accent">
            <x-ui.icon name="party-popper" class="size-8" />
        </div>
        <h3 class="mt-6 font-display text-3xl uppercase tracking-tight text-brand">Bienvenue au REJCC&nbsp;!</h3>
        <p class="mx-auto mt-3 max-w-md text-pretty text-ink/70">
            Votre demande d'adhésion a bien été enregistrée. Vous recevrez une confirmation et les instructions de paiement par e-mail.
        </p>
        <p class="mt-6 inline-block rounded-full bg-cloud px-5 py-2 text-sm text-ink/70">
            Référence : <span class="font-semibold text-brand">{{ $reference }}</span>
        </p>
        <div class="mt-8">
            <a href="{{ $dashboardHref }}" wire:navigate class="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3.5 font-semibold text-white transition-colors hover:bg-brand/90">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                {{ $dashboardLabel }}
            </a>
        </div>
    </div>
@else
    <form wire:submit="{{ $step === 0 ? 'next' : 'submit' }}" class="rounded-3xl border border-brand/10 bg-white p-6 shadow-[0_30px_80px_-50px_rgba(3,29,89,0.45)] sm:p-9">
        <div class="mb-7 flex items-center justify-between rounded-2xl bg-brand px-5 py-4 text-white">
            <span class="text-sm font-medium text-white/80">Cotisation d'adhésion</span>
            <span class="font-display text-2xl">
                {{ $fee }} <span class="text-sm font-medium text-white/70">{{ $currency }}/{{ $period }}</span>
            </span>
        </div>

        <ol class="mb-8 flex items-center gap-3">
            @foreach ($stepLabels as $i => $label)
                <li class="flex flex-1 items-center gap-2">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-colors {{ $i < $step ? 'bg-accent text-white' : ($i === $step ? 'bg-brand text-white' : 'bg-cloud text-ink/40') }}">
                        @if ($i < $step)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-4"><path d="M20 6 9 17l-5-5"/></svg>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </span>
                    <span class="text-sm font-semibold {{ $i === $step ? 'text-brand' : 'text-ink/50' }}">{{ $label }}</span>
                    @if ($i < count($stepLabels) - 1)
                        <span class="ml-1 hidden h-px flex-1 bg-brand/10 sm:block"></span>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($step === 0)
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2 flex flex-col gap-1.5">
                    <label for="profil" class="text-sm font-semibold text-brand">Votre profil</label>
                    <select wire:model="profil" id="profil" class="w-full rounded-xl border bg-white px-4 py-3 pr-10 text-brand outline-none transition focus:ring-2 {{ $errors->has('profil') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}">
                        <option value="">Sélectionnez votre profil…</option>
                        @foreach ($profiles as $p)
                            <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                    @error('profil') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="prenom" class="text-sm font-semibold text-brand">Prénom</label>
                    <input wire:model="prenom" id="prenom" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('prenom') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('prenom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="nom" class="text-sm font-semibold text-brand">Nom</label>
                    <input wire:model="nom" id="nom" type="text" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('nom') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('nom') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm font-semibold text-brand">E-mail</label>
                    <input wire:model="email" id="email" type="email" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('email') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="telephone" class="text-sm font-semibold text-brand">Téléphone</label>
                    <input wire:model="telephone" id="telephone" type="text" inputmode="numeric" placeholder="0700000000" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('telephone') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('telephone') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold text-brand">Genre</span>
                    <div class="flex gap-2">
                        @foreach (['Homme', 'Femme'] as $g)
                            <button type="button" wire:click="selectGenre('{{ $g }}')" class="flex-1 rounded-xl border px-4 py-3 text-sm font-medium transition-colors {{ $genre === $g ? 'border-accent bg-accent/[0.04] text-brand' : 'border-brand/15 text-ink/70 hover:border-brand/30' }}">
                                {{ $g }}
                            </button>
                        @endforeach
                    </div>
                    @error('genre') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="ville" class="text-sm font-semibold text-brand">Ville</label>
                    <input wire:model="ville" id="ville" type="text" placeholder="Abidjan" class="w-full rounded-xl border bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:ring-2 {{ $errors->has('ville') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}" />
                    @error('ville') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="secteur" class="text-sm font-semibold text-brand">Domaine d'activité</label>
                    <select wire:model="secteur" id="secteur" class="w-full rounded-xl border bg-white px-4 py-3 pr-10 text-brand outline-none transition focus:ring-2 {{ $errors->has('secteur') ? 'border-accent focus:border-accent focus:ring-accent/25' : 'border-brand/15 focus:border-brand focus:ring-accent/20' }}">
                        <option value="">Sélectionnez votre domaine…</option>
                        @foreach ($sectors as $s)
                            <optgroup label="{{ $s->title }}">
                                @foreach ($s->items as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('secteur') <span class="text-xs font-medium text-accent">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="organisation" class="text-sm font-semibold text-brand">Entreprise / projet (facultatif)</label>
                    <input wire:model="organisation" id="organisation" type="text" class="w-full rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20" />
                </div>

                <div class="sm:col-span-2 flex flex-col gap-1.5">
                    <label for="message" class="text-sm font-semibold text-brand">Votre projet en quelques mots (facultatif)</label>
                    <textarea wire:model="message" id="message" class="min-h-32 w-full resize-y rounded-xl border border-brand/15 bg-white px-4 py-3 text-brand placeholder:text-ink/40 outline-none transition focus:border-brand focus:ring-2 focus:ring-accent/20"></textarea>
                </div>
            </div>
        @else
            <div>
                <h3 class="text-lg font-bold text-brand">Moyen de paiement</h3>
                <p class="mt-1 text-sm text-ink/60">Choisissez comment régler votre cotisation de {{ $fee }} {{ $currency }}.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($paymentMethods as $m)
                        <button type="button" wire:click="selectPaiement('{{ $m['id'] }}')" aria-label="{{ $m['label'] }}" class="flex items-center justify-center rounded-2xl border px-4 py-5 transition-colors {{ $paiement === $m['id'] ? 'border-accent bg-accent/[0.04] ring-2 ring-accent/25' : 'border-brand/15 hover:border-brand/30' }}">
                            <x-ui.payment-logo :id="$m['id']" />
                        </button>
                    @endforeach
                </div>
                @error('paiement') <p class="mt-2 text-sm font-medium text-accent">{{ $message }}</p> @enderror

                <label class="mt-6 flex items-start gap-3 text-sm text-ink/75">
                    <input wire:model="consent" type="checkbox" class="mt-0.5 size-5 shrink-0 accent-[var(--color-accent)]" />
                    <span>J'accepte d'adhérer au REJCC et de recevoir les communications liées à mon adhésion.</span>
                </label>
                @error('consent') <p class="mt-2 text-sm font-medium text-accent">{{ $message }}</p> @enderror

                <p class="mt-6 flex items-center gap-2 rounded-xl bg-brand/5 px-4 py-3 text-xs text-ink/60">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 shrink-0 text-brand"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                    Paiement en cours d'intégration : votre demande est enregistrée et l'équipe vous transmet les instructions de règlement.
                </p>
            </div>
        @endif

        <div class="mt-8 flex items-center justify-between gap-3">
            @if ($step > 0)
                <button type="button" wire:click="back" class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold text-brand transition-colors hover:bg-brand/5">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
                    Précédent
                </button>
            @else
                <span></span>
            @endif

            @if ($step === 0)
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                    Continuer <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            @else
                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-accent-600 disabled:opacity-70">
                    <span wire:loading.remove>Finaliser mon adhésion</span>
                    <span wire:loading>Envoi…</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            @endif
        </div>
    </form>
@endif
</div>

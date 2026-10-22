<div x-data="{ ouvert: false }" x-on:keydown.escape.window="ouvert = false">
    {{-- Pastille fixe en bas à gauche (le bouton vidéo du site est à droite). --}}
    <div class="fixed bottom-20 left-1/2 z-[90] lg:bottom-4 flex -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full border border-amber-300 bg-white/95 py-1.5 pl-3 pr-1.5 shadow-[0_6px_24px_rgba(3,29,89,.18)] backdrop-blur">
        <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-700">Mode test</span>
        @if ($departement)<span class="hidden text-xs text-[#5B677A] sm:inline">{{ $departement }}</span>@endif
        <button type="button" x-on:click="ouvert = true" class="btn-tap inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1.5 text-xs font-bold text-white hover:bg-azure">
            <x-ui.icon name="lightbulb" class="size-3.5" />
            Signaler · Suggérer
        </button>
    </div>

    <div x-show="ouvert" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex items-end justify-center bg-brand/40 p-4 sm:items-center" x-on:click.self="ouvert = false">
        <div class="w-full max-w-lg rounded-[18px] bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="suggestion-titre">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 id="suggestion-titre" class="text-[17px] font-bold text-brand">Signaler un bug ou faire une suggestion</h2>
                    <p class="mt-1 text-xs text-[#5B677A]">
                        Une erreur, un comportement inattendu, ce qui manque à votre département : tout est utile. La page et votre navigateur sont joints automatiquement.
                    </p>
                </div>
                <button type="button" x-on:click="ouvert = false" class="rounded-lg p-1 text-[#5B677A] hover:bg-cloud" aria-label="Fermer">
                    <x-ui.icon name="x" class="size-5" />
                </button>
            </div>

            @if (! $connecte)
                <p class="rounded-xl bg-[#F8FAFC] p-4 text-sm text-ink">
                    Reconnectez-vous avec votre compte de test pour envoyer un retour.
                </p>
                <a href="{{ route('login') }}" class="btn-tap mt-4 inline-flex rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-azure">Se connecter</a>
            @elseif ($envoyee)
                <div class="rounded-xl bg-[#EAF6EE] p-4 text-sm text-[#1C7A45]">
                    Merci, votre retour est enregistré{{ $departement ? ' pour le département '.$departement : '' }}.
                    @if ($envoyees > 1) ({{ $envoyees }} envoyées depuis cette page.) @endif
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="button" wire:click="nouvelle" class="btn-tap rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-azure">Autre retour</button>
                    <button type="button" x-on:click="ouvert = false" class="btn-tap rounded-lg border border-[#C9D6F0] px-4 py-2 text-sm font-bold text-azure hover:bg-[#E8EDF8]">Fermer</button>
                </div>
            @else
                <form wire:submit="envoyer" class="space-y-4">
                    <fieldset>
                        <legend class="mb-2 text-xs font-bold text-brand">Type</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (\App\Livewire\SuggestionTest::CATEGORIES as $cle => $libelle)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-xs has-[:checked]:border-azure has-[:checked]:bg-[#E8EDF8]">
                                    <input type="radio" wire:model="categorie" value="{{ $cle }}" class="text-azure">
                                    {{ $libelle }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div>
                        <label for="suggestion-message" class="mb-2 block text-xs font-bold text-brand">Ce que vous avez constaté</label>
                        <textarea id="suggestion-message" wire:model="message" rows="5" maxlength="3000" required
                            class="w-full rounded-xl border-[#C9D6F0] text-sm focus:border-azure focus:ring-azure"
                            placeholder="Ex. : en cliquant sur « Enregistrer » dans mon profil, rien ne se passe. / Notre département aimerait publier ses propres événements."></textarea>
                        <p class="mt-1 text-[11px] text-[#5B677A]">
                            Envoyée depuis : <span class="font-mono">{{ \Illuminate\Support\Str::limit($page, 60) }}</span>
                            @if ($departement) · Département : {{ $departement }} @endif
                        </p>
                    </div>
                    @if ($erreur)
                        <p class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ $erreur }}</p>
                    @endif
                    <div class="flex justify-end gap-2">
                        <button type="button" x-on:click="ouvert = false" class="btn-tap rounded-lg border border-[#C9D6F0] px-4 py-2 text-sm font-bold text-azure hover:bg-[#E8EDF8]">Annuler</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-tap rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-azure disabled:opacity-60">
                            <span wire:loading.remove wire:target="envoyer">Envoyer</span>
                            <span wire:loading wire:target="envoyer">Envoi…</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

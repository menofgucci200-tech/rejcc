{{--
    Campagne de test sur le site réel : visible uniquement par un compte de
    test (département renseigné). Liseré orangé, pastille « Mode test » et
    bouton pour signaler un bug ou faire une suggestion. Les vrais membres
    ne voient rien.
--}}
@props(['livewire' => true])
@php($departement = session('api_user.departement'))
@if ($departement)
    <div aria-hidden="true" class="pointer-events-none fixed inset-x-0 top-0 z-[130] h-1 bg-amber-400"></div>
    @if ($livewire)
        <livewire:suggestion-test />
    @else
        {{-- Pages d'erreur : formulaire simple, sans Livewire. --}}
        <details class="fixed bottom-20 left-1/2 z-[90] lg:bottom-4 w-[min(92vw,380px)] -translate-x-1/2 rounded-[18px] border border-amber-300 bg-white shadow-[0_6px_24px_rgba(3,29,89,.18)]" @if (session('retour_testeur')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-xs">
                <span class="font-extrabold uppercase tracking-wider text-amber-700">Mode test</span>
                <span class="font-bold text-brand">Signaler cette erreur</span>
            </summary>
            <div class="px-4 pb-4">
                @if (session('retour_testeur'))
                    <p class="rounded-lg bg-[#EAF6EE] px-3 py-2 text-xs text-[#1C7A45]">Merci, votre signalement est enregistré.</p>
                @else
                    <form method="POST" action="{{ route('retour-testeur') }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="categorie" value="bug">
                        <input type="hidden" name="page" value="{{ mb_substr(request()->getRequestUri(), 0, 500) }}">
                        <textarea name="message" rows="3" required minlength="10" maxlength="3000" class="w-full rounded-xl border-[#C9D6F0] text-sm" placeholder="Qu'étiez-vous en train de faire quand cette erreur est apparue ?"></textarea>
                        <button class="btn-tap rounded-lg bg-brand px-4 py-1.5 text-xs font-bold text-white hover:bg-azure">Envoyer</button>
                    </form>
                @endif
            </div>
        </details>
    @endif
@endif

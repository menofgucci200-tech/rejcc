<div>
    <x-admin-light.topbar title="Nouvelle inscription" />

    <div class="mx-auto max-w-[860px] px-8 py-8">
        <div class="mb-6">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Créer un compte</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-3 text-[13px] text-[#5B677A]">Inscrivez directement un membre, un mentor, ou un administrateur — avec un accès complet ou limité à certaines sections.</p>
        </div>

        @if ($message)
            <div class="panel-enter mb-5 flex items-center gap-2.5 rounded-[12px] border border-[#BFE3CD] bg-[#EAF6EE] px-4 py-3 text-[13px] font-semibold text-[#1C8F4C]">
                <x-ui.icon name="check-circle" class="size-4 shrink-0" /> {{ $message }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3.5 rounded-[18px] border border-brand/10 bg-white p-[22px] shadow-[0_2px_8px_rgba(3,29,89,.05)] sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-2 block text-xs font-semibold text-[#5B677A]">Type de compte</label>
                <div class="flex flex-wrap gap-2">
                    @foreach (['member' => 'Membre', 'mentor' => 'Mentor', 'admin' => 'Administrateur'] as $value => $label)
                        <button type="button" wire:click="$set('type', '{{ $value }}')" class="btn-tap rounded-full border px-4 py-2 text-xs font-semibold transition-colors duration-200 {{ $type === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A] hover:border-brand/30' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Prénom</label>
                <input wire:model="prenom" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                @error('prenom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom</label>
                <input wire:model="nom" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                @error('nom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Adresse e-mail</label>
                <input wire:model="email" type="email" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                @error('email') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Téléphone (10 chiffres)</label>
                <input wire:model="telephone" type="text" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                @error('telephone') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Mot de passe provisoire (min. 8 caractères — à communiquer à la personne)</label>
                <x-ui.password-input wire:model="password" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure" />
                @error('password') <span class="text-xs text-accent">{{ $message }}</span> @enderror
            </div>

            @if ($type === 'admin')
                <div class="sm:col-span-2 rounded-[12px] border border-brand/10 bg-cloud/60 p-4">
                    <label class="inline-flex items-center gap-2 text-[13px] font-bold text-brand">
                        <input wire:model.live="accesComplet" type="checkbox" class="size-4 rounded border-brand/20 text-brand" />
                        Accès complet à toute l'administration
                    </label>
                    @if (! $accesComplet)
                        <p class="mb-2 mt-3 text-xs font-semibold text-[#5B677A]">Sections autorisées pour cet administrateur :</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($sectionsDisponibles as $slug => $label)
                                <label class="inline-flex items-center gap-2 text-xs text-[#5B677A]">
                                    <input wire:model="sections" type="checkbox" value="{{ $slug }}" class="size-3.5 rounded border-brand/20 text-brand" />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('sections') <span class="mt-2 block text-xs text-accent">{{ $message }}</span> @enderror
                    @endif
                </div>
            @endif

            <button wire:click="save" wire:loading.attr="disabled" class="btn-tap rounded-[9px] bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-brand/90 hover:shadow-md disabled:opacity-60 sm:col-span-2 sm:w-fit">Créer le compte</button>
        </div>
    </div>
</div>

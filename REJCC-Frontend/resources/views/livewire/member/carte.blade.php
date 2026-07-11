<div>
    <x-member-light.topbar title="Ma carte membre" />

    <div class="mx-auto max-w-[1120px] px-8 py-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="mb-1 text-[17px] font-bold text-brand">Ma carte de membre</h1>
                <div class="h-[3px] w-9 rounded bg-accent"></div>
                <p class="mt-3 max-w-xl text-[13px] text-[#5B677A]">Votre carte officielle du REJCC. Ajoutez votre photo, puis présentez le QR code (recto scanné = accès à votre profil).</p>
            </div>
            @if ($message)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#22A85A]/10 px-3.5 py-1.5 text-xs font-semibold text-[#22A85A]">
                    <x-ui.icon name="check-circle" class="size-3.5" /> {{ $message }}
                </span>
            @endif
        </div>

        {{-- Input fichier caché, relié à la zone photo du recto via l'id --}}
        <input type="file" id="card-photo-input" wire:model="photo" accept="image/*" class="hidden">

        <div wire:loading.class="opacity-60" wire:target="photo">
            <x-member-card
                :name="$name"
                :roleLabel="$roleLabel"
                :role="$role"
                :numero="$numero"
                :code="$code"
                :photo="$photo"
                :dateAdhesion="$dateAdhesion"
                :editable="true"
                uploadId="card-photo-input"
            />
        </div>

        @error('photo') <p class="mt-4 text-center text-xs font-medium text-accent">{{ $message }}</p> @enderror

        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <label for="card-photo-input" class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand/90">
                <x-ui.icon name="image" class="size-3.5" /> {{ $photo ? 'Changer ma photo' : 'Ajouter ma photo' }}
            </label>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white px-4 py-2 text-xs font-bold text-brand hover:bg-cloud">
                <x-ui.icon name="download" class="size-3.5" /> Imprimer / enregistrer en PDF
            </button>
            <span wire:loading wire:target="photo" class="text-xs font-semibold text-[#9AA6B8]">Envoi de la photo…</span>
        </div>
    </div>
</div>

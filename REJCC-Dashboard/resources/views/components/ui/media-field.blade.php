@props([
    'label' => 'Fichier ou lien (image, PDF, vidéo…)',
    'hint' => 'Glissez un fichier, cliquez pour parcourir, ou collez un lien externe.',
    'mediaUrl' => '',
    'mediaName' => '',
    'mediaSize' => '',
])

{{-- S'appuie sur le trait HandlesMedia : propriétés mediaFile / mediaUrl / mediaName / mediaSize. --}}
<div>
    <label class="mb-1 block text-xs font-semibold text-[#5B677A]">{{ $label }}</label>

    @if ($mediaUrl)
        <div class="flex items-center gap-3 rounded-[10px] border border-brand/15 bg-cloud/60 px-3 py-2.5">
            @if (\Illuminate\Support\Str::contains($mediaUrl, ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg']))
                <img src="{{ $mediaUrl }}" alt="" class="size-11 shrink-0 rounded-lg object-cover">
            @else
                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                    <x-ui.icon name="file-text" class="size-5" />
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-semibold text-brand">{{ $mediaName ?: 'Média joint' }}</p>
                <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="truncate text-[11px] text-azure hover:underline">{{ $mediaSize ? $mediaSize.' · ' : '' }}Ouvrir</a>
            </div>
            <button type="button" wire:click="clearMedia" class="shrink-0 rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent" title="Retirer">
                <x-ui.icon name="x" class="size-4" />
            </button>
        </div>
    @else
        <label class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[10px] border border-dashed border-brand/25 bg-cloud/40 px-4 py-5 text-center hover:bg-cloud/70" wire:loading.class="opacity-50" wire:target="mediaFile">
            <x-ui.icon name="image" class="size-6 text-[#9AA6B8]" />
            <span class="text-[12px] font-semibold text-[#5B677A]">Cliquez pour choisir un fichier</span>
            <span class="text-[10.5px] text-[#9AA6B8]">Images, PDF, Word, Excel, audio, vidéo — 20 Mo max</span>
            <input type="file" wire:model="mediaFile" class="hidden">
        </label>
        <div wire:loading wire:target="mediaFile" class="mt-1.5 text-[11px] font-semibold text-azure">Envoi du fichier…</div>
        @error('mediaFile') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror

        <div class="mt-2 flex items-center gap-2">
            <span class="text-[11px] font-semibold text-[#9AA6B8]">ou</span>
            <input wire:model.blur="mediaUrl" type="url" placeholder="Coller un lien (https://…)" class="w-full rounded-[9px] border border-brand/15 px-3 py-2 text-sm outline-none focus:border-azure">
        </div>
        <p class="mt-1 text-[11px] text-[#9AA6B8]">{{ $hint }}</p>
    @endif
</div>

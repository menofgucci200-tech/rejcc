@props(['questions' => [], 'champ' => 'moduleQuiz', 'label' => 'Quiz', 'max' => 20])

{{-- Éditeur de QCM (quiz de module ou examen final) : méthodes ajouterQuestion,
     retirerQuestion, ajouterChoix, retirerChoix du composant Livewire parent. --}}
<div {{ $attributes }}>
    <div class="mb-1.5 flex items-center justify-between gap-3">
        <label class="text-xs font-semibold text-[#5B677A]">{{ $label }}</label>
        @if (count($questions) < $max)
            <button type="button" wire:click="ajouterQuestion('{{ $champ }}')" class="shrink-0 text-[11.5px] font-bold text-azure hover:underline">+ Question</button>
        @endif
    </div>
    @foreach ($questions as $qi => $q)
        <div wire:key="{{ $champ }}-{{ $qi }}" class="mb-2 rounded-[10px] border border-brand/10 bg-cloud/40 p-3">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold text-[#9AA6B8]">Q{{ $qi + 1 }}</span>
                <input wire:model="{{ $champ }}.{{ $qi }}.question" type="text" placeholder="Intitulé de la question" class="min-w-0 flex-1 rounded-[8px] border border-brand/15 bg-white px-2.5 py-1.5 text-[12.5px] outline-none focus:border-azure" />
                <button type="button" wire:click="retirerQuestion('{{ $champ }}', {{ $qi }})" class="text-[11px] font-semibold text-accent hover:underline">Retirer</button>
            </div>
            @error("{$champ}.{$qi}.question") <span class="mt-1 block text-xs text-accent">{{ $message }}</span> @enderror
            <div class="mt-2 flex flex-col gap-1.5 pl-6">
                @foreach ($q['choix'] as $ci => $choix)
                    <div wire:key="{{ $champ }}-{{ $qi }}-{{ $ci }}" class="flex items-center gap-2">
                        <input type="radio" wire:model="{{ $champ }}.{{ $qi }}.bonne" name="{{ $champ }}-bonne-{{ $qi }}" value="{{ $ci }}" title="Bonne réponse" class="accent-[#22A85A]">
                        <input wire:model="{{ $champ }}.{{ $qi }}.choix.{{ $ci }}" type="text" placeholder="Réponse {{ $ci + 1 }}" class="min-w-0 flex-1 rounded-[8px] border border-brand/15 bg-white px-2.5 py-1 text-[12px] outline-none focus:border-azure" />
                        @if (count($q['choix']) > 2)
                            <button type="button" wire:click="retirerChoix('{{ $champ }}', {{ $qi }}, {{ $ci }})" class="text-[#9AA6B8] hover:text-accent" aria-label="Retirer la réponse"><x-ui.icon name="x" class="size-3.5" /></button>
                        @endif
                    </div>
                @endforeach
                @if (count($q['choix']) < 6)
                    <button type="button" wire:click="ajouterChoix('{{ $champ }}', {{ $qi }})" class="w-fit text-[11px] font-semibold text-azure hover:underline">+ Réponse</button>
                @endif
                <p class="text-[10.5px] text-[#9AA6B8]">Cochez le rond de la bonne réponse.</p>
            </div>
        </div>
    @endforeach
    @error($champ) <p class="text-xs font-medium text-accent">{{ $message }}</p> @enderror
</div>

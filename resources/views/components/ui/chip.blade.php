@props(['active' => false])

<button
    type="button"
    {{ $attributes->merge(['class' => 'rounded-full border px-4 py-2 text-sm font-medium transition-colors ' . ($active ? 'border-brand bg-brand text-white' : 'border-brand/15 bg-white text-brand hover:border-brand/40')]) }}
>
    {{ $slot }}
</button>

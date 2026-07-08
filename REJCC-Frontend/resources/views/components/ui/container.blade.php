@props(['as' => 'div'])

<{{ $as }} {{ $attributes->merge(['class' => 'mx-auto w-full max-w-7xl container-px']) }}>
    {{ $slot }}
</{{ $as }}>

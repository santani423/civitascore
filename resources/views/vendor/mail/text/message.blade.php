@props([
    'brand' => null,
    'label' => null,
    'preheader' => null,
    'contact' => null,
    'url' => null,
])
@php
    $brand ??= \Modules\Notification\Support\MailBrand::current();
@endphp
<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        {{ trim((filled($label) ? $label.' ' : '').$brand->name) }}
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            {{ $subcopy }}
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        © {{ date('Y') }} {{ $brand->fullName }}
    </x-slot:footer>
</x-mail::layout>

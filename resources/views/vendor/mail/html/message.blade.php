@props([
    'brand' => null,
    'label' => null,
    'preheader' => null,
    'contact' => null,
    'url' => null,
])
@php
    $brand ??= \Modules\Notification\Support\MailBrand::current();
    $url ??= config('app.frontend_url') ?: config('app.url');
    $contact ??= $brand->contact;
@endphp
<x-mail::layout :preheader="$preheader">
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="$url" :brand="$brand" :label="$label" />
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer :brand="$brand" :contact="$contact" />
</x-slot:footer>
</x-mail::layout>

{{--
    Template semua notifikasi MailMessage (tema "civitas", lihat
    resources/views/vendor/mail). Data opsional lewat ->markdown('notifications::email', [...]):
    brand (MailBrand), brandLabel, eyebrow, signature, contact, homeUrl, preheader.
    Judul = greeting bila diisi, selain itu subjek email.
--}}
@php
    $brand ??= \Modules\Notification\Support\MailBrand::current();
    $lines = array_map('strval', [...$introLines, ...$outroLines]);
    $heading = filled($greeting ?? null) ? $greeting : (filled($subject ?? null) ? $subject : ($level === 'error' ? 'Terjadi kendala' : 'Pemberitahuan'));
    $preheader ??= \Illuminate\Support\Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags(implode(' ', array_map('strval', $introLines))))), 140);
    $buttonColor = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
    // Tautan tombol yang sudah tertulis di isi email tidak perlu diulang di subcopy.
    $actionInText = filled($actionUrl ?? null) && str_contains(implode("\n", $lines), (string) $actionUrl);
@endphp
<x-mail::message :brand="$brand" :label="$brandLabel ?? null" :preheader="$preheader" :contact="$contact ?? null" :url="$homeUrl ?? null">
@if (filled($eyebrow ?? null))
<p class="eyebrow">{{ $eyebrow }}</p>
@endif

# {{ $heading }}

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :color="$buttonColor">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (filled($salutation ?? null))
<p class="salutation">{{ $salutation }}</p>
@else
<p class="salutation">Salam hangat,<br>
<strong>{{ $signature ?? 'Tim '.$brand->name }}</strong></p>
@endif

{{-- Subcopy --}}
@if (isset($actionText) && ! $actionInText)
<x-slot:subcopy>
Tombol <strong>{{ $actionText }}</strong> tidak berfungsi? Salin dan tempel tautan berikut ke browser Anda: <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endif
</x-mail::message>

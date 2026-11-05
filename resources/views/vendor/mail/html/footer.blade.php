@props(['brand', 'contact' => []])
@php
    $contact = array_filter((array) $contact, fn ($value) => is_string($value) && trim($value) !== '');
    $reach = array_filter([
        isset($contact['email']) ? '<a href="mailto:'.e($contact['email']).'">'.e($contact['email']).'</a>' : null,
        isset($contact['phone']) ? 'Telp. '.e($contact['phone']) : null,
        isset($contact['whatsapp']) ? 'WhatsApp '.e($contact['whatsapp']) : null,
        isset($contact['website']) ? '<a href="'.e($contact['website']).'">'.e(preg_replace('#^https?://#', '', $contact['website'])).'</a>' : null,
    ]);
@endphp
<tr>
<td class="footer-row" align="center">
<table class="footer" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="footer-cell" align="center">
<p class="footer-brand">{{ $brand->fullName }}</p>
@isset($contact['address'])
<p class="footer-line">{{ $contact['address'] }}</p>
@endisset
@if ($reach !== [])
<p class="footer-line">{!! implode(' &nbsp;&middot;&nbsp; ', $reach) !!}</p>
@endif
@isset($contact['hours'])
<p class="footer-line">Layanan: {{ $contact['hours'] }}</p>
@endisset
<p class="footer-note">Email ini dikirim otomatis oleh sistem. Bila ada pertanyaan, silakan hubungi kami melalui kontak di atas.</p>
<p class="footer-copy">&copy; {{ date('Y') }} {{ $brand->fullName }}</p>
</td>
</tr>
</table>
</td>
</tr>

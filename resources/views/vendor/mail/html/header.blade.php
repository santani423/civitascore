{{-- Wordmark ala SiteMark portal PMB: logo/monogram + "<label> <nama singkat>" + nama lengkap. --}}
@props(['url', 'brand', 'label' => null])
<tr>
<td class="header" align="center">
<table class="header-inner" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-cell" align="left">
<table class="brand" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-mark-cell" valign="middle">
@if ($brand->logoUrl)
<a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $brand->logoUrl }}" alt="{{ $brand->name }}" height="44" class="brand-logo"></a>
@else
<table cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-mark" width="44" height="44" align="center" valign="middle">{{ $brand->initials }}</td>
</tr>
</table>
@endif
</td>
<td class="brand-text" valign="middle">
<a href="{{ $url }}" class="brand-name" target="_blank" rel="noopener">@if (filled($label))<span class="brand-label">{{ $label }}</span> @endif{{ $brand->name }}</a>
@if (filled($brand->subtitle))
<br><span class="brand-subtitle">{{ $brand->subtitle }}</span>
@endif
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>

@props(['preheader' => null])
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="id">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="x-apple-disable-message-reformatting" />
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet" />
<!--[if mso]>
<style>
* { font-family: 'Segoe UI', Arial, sans-serif !important; }
</style>
<![endif]-->
<style>
@media only screen and (max-width: 640px) {
.wrapper-cell {
padding: 20px 12px 28px !important;
}

.inner-body,
.header-inner,
.footer {
width: 100% !important;
}

.content-cell {
padding: 32px 24px !important;
}

h1 {
font-size: 22px !important;
}
}

@media only screen and (max-width: 500px) {
.button-table,
.button-cell,
.button {
display: block !important;
width: 100% !important;
text-align: center !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>
@if (filled($preheader))
<div class="preheader" style="display: none; max-height: 0; max-width: 0; overflow: hidden; opacity: 0; mso-hide: all; font-size: 1px; line-height: 1px; color: #faf8f5;">{{ $preheader }}{!! str_repeat('&#847;&zwnj;&nbsp;', 60) !!}</div>
@endif

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="wrapper-cell" align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>

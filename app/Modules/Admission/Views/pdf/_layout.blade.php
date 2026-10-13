<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $title ?? 'Dokumen PMB' }}</title>
<style>
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
    h1 { font-size: 16px; margin: 0 0 4px; }
    h2 { font-size: 13px; margin: 16px 0 6px; }
    .muted { color: #6b7280; }
    .header { border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 5px 6px; vertical-align: top; text-align: left; }
    .grid td { border: 1px solid #d1d5db; }
    .grid th { border: 1px solid #d1d5db; background: #f3f4f6; }
    .right { text-align: right; }
    .box { border: 1px solid #d1d5db; padding: 10px; border-radius: 4px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; background: #ccfbf1; color: #0f766e; font-weight: bold; }
    .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 9px; color: #6b7280; text-align: center; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $universityName ?? '' }}</h1>
    <div class="muted">Penerimaan Mahasiswa Baru</div>
</div>
@yield('content')
<div class="footer">Dicetak {{ now()->format('d-m-Y H:i') }} — dokumen ini dibangkitkan oleh sistem PMB.</div>
</body>
</html>

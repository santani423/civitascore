<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kuitansi {{ $tx->receipt_number }}</title>
<style>
    @page { margin: 22px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; }
    h1 { font-size: 15px; margin: 0; }
    h2 { font-size: 13px; margin: 10px 0 6px; letter-spacing: 1px; }
    .muted { color: #6b7280; }
    .header { border-bottom: 2px solid #0f766e; padding-bottom: 6px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 4px 6px; text-align: left; vertical-align: top; }
    .grid td, .grid th { border: 1px solid #d1d5db; }
    .grid th { background: #f3f4f6; }
    .right { text-align: right; }
    .total { font-size: 13px; font-weight: bold; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $university?->name }}</h1>
    <div class="muted">Bagian Keuangan — Administrasi Keuangan Mahasiswa</div>
</div>
<h2>KUITANSI PEMBAYARAN</h2>
<table>
    <tr><td style="width:28%">No. kuitansi</td><td>{{ $tx->receipt_number }}</td><td style="width:22%">No. transaksi</td><td>{{ $tx->transaction_number }}</td></tr>
    <tr><td>Telah diterima dari</td><td>{{ $tx->payer_name ?: $tx->student->name }}</td><td>Tanggal bayar</td><td>{{ optional($tx->paid_at)->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</td></tr>
    <tr><td>Untuk mahasiswa</td><td>{{ $tx->student->name }} ({{ $tx->student->nim }})</td><td>Metode</td><td>{{ $tx->method->label() }}{{ $tx->channel ? ' — '.$tx->channel->name : '' }}</td></tr>
    <tr><td>Program studi</td><td>{{ $tx->student->studyProgram?->name }}</td><td>Referensi</td><td>{{ $tx->provider_reference ?? '-' }}</td></tr>
</table>
<h2>Rincian alokasi</h2>
<table class="grid">
    <tr><th>Tagihan</th><th>Periode</th><th>Termin</th><th class="right">Nominal</th></tr>
    @forelse ($tx->allocations->where('status', 'posted') as $allocation)
        <tr><td>{{ $allocation->invoice?->invoice_number }}</td><td>{{ $allocation->invoice?->period }}</td><td>{{ $allocation->installment ? 'Termin '.$allocation->installment->seq : '-' }}</td><td class="right">{{ \Modules\Finance\Support\Money::format((string) $allocation->amount) }}</td></tr>
    @empty
        <tr><td colspan="4">Dana dicatat sebagai saldo deposit (titipan) mahasiswa.</td></tr>
    @endforelse
</table>
<p class="total">Jumlah diterima: {{ \Modules\Finance\Support\Money::format((string) $tx->amount) }}</p>
@if ((float) $tx->channel_fee > 0)<p class="muted">Termasuk biaya kanal {{ \Modules\Finance\Support\Money::format((string) $tx->channel_fee) }}.</p>@endif
<p class="muted">Kelebihan pembayaran (bila ada) tercatat sebagai saldo deposit dan dapat dipakai untuk tagihan berikutnya atau diajukan pengembaliannya.
Dicetak {{ now()->timezone(config('app.timezone'))->format('d-m-Y H:i') }}{{ $tx->recorder ? ' — petugas: '.$tx->recorder->name : '' }}.</p>
</body>
</html>

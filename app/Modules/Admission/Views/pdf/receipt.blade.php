@extends('admission::pdf._layout', ['title' => 'Kuitansi '.$invoice->invoice_number, 'universityName' => $university?->name])
@section('content')
<h2>KUITANSI PEMBAYARAN</h2>
<table>
    <tr><td style="width:30%">No. tagihan</td><td>{{ $invoice->invoice_number }}</td></tr>
    <tr><td>No. pendaftaran</td><td>{{ $invoice->application->registration_number ?? '-' }}</td></tr>
    <tr><td>Nama</td><td>{{ $invoice->application->account->name }}</td></tr>
    <tr><td>Jenis</td><td>{{ $invoice->type->value === 'registration_fee' ? 'Biaya pendaftaran' : 'Biaya pendidikan awal' }}</td></tr>
</table>
<h2>Rincian</h2>
<table class="grid">
    <tr><th>Uraian</th><th class="right">Nominal</th></tr>
    @foreach ($invoice->items as $item)
        <tr><td>{{ $item->description }}</td><td class="right">{{ \Modules\Admission\Support\Money::format((string) $item->amount) }}</td></tr>
    @endforeach
    <tr><th>Total tagihan</th><th class="right">{{ \Modules\Admission\Support\Money::format((string) $invoice->amount_net) }}</th></tr>
</table>
<h2>Pembayaran diterima</h2>
<table class="grid">
    <tr><th>No. kuitansi</th><th>Tanggal</th><th>Kanal</th><th>Referensi</th><th class="right">Nominal</th></tr>
    @foreach ($transactions as $t)
        <tr><td>{{ $t->receipt_number }}</td><td>{{ optional($t->paid_at)->format('d-m-Y H:i') }}</td><td>{{ $t->channel }}</td><td>{{ $t->provider_reference }}</td><td class="right">{{ \Modules\Admission\Support\Money::format((string) $t->amount) }}</td></tr>
    @endforeach
    <tr><th colspan="4">Total dibayar</th><th class="right">{{ \Modules\Admission\Support\Money::format((string) $invoice->amount_paid) }}</th></tr>
</table>
<p class="muted">Status tagihan: <span class="badge">{{ strtoupper(str_replace('_', ' ', $invoice->status->value)) }}</span></p>
@endsection

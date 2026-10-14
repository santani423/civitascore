@extends('admission::pdf._layout', ['title' => 'Bukti Registrasi '.$conversion->nim, 'universityName' => $application->university?->name])
@section('content')
<table>
    <tr>
        <td><h2 style="margin-top:0">BUKTI REGISTRASI MAHASISWA BARU</h2><div class="muted">{{ $application->period?->name }}</div></td>
        <td class="right" style="width:120px"><img src="{{ $qr }}" width="100" alt="QR"></td>
    </tr>
</table>
<div class="box">
    <table>
        <tr><td style="width:35%">NIM</td><td><strong style="font-size:16px">{{ $conversion->nim }}</strong></td></tr>
        <tr><td>Nama</td><td>{{ $application->account->profile?->full_name ?? $application->account->name }}</td></tr>
        <tr><td>Program studi</td><td>{{ $decision?->studyProgram?->name }}</td></tr>
        <tr><td>Sistem kuliah</td><td>{{ $decision?->studySystem?->name }}</td></tr>
        <tr><td>No. pendaftaran</td><td>{{ $application->registration_number }}</td></tr>
        <tr><td>Status pembayaran biaya awal</td><td>@if ($invoice){{ \Modules\Admission\Support\Money::format((string) $invoice->amount_paid) }} dari {{ \Modules\Admission\Support\Money::format((string) $invoice->amount_net) }} ({{ str_replace('_', ' ', $invoice->status->value) }})@else - @endif</td></tr>
        <tr><td>Tanggal terdaftar</td><td>{{ optional($conversion->succeeded_at)->format('d-m-Y') }}</td></tr>
    </table>
</div>
<p>Aktivasi akun mahasiswa dilakukan melalui tautan yang dikirim ke email Anda. Informasi orientasi (PKKMB) tersedia di portal.</p>
@endsection

@extends('admission::pdf._layout', ['title' => 'Surat Penerimaan '.$letter->letter_number, 'universityName' => $application->university?->name])
@section('content')
<table>
    <tr>
        <td>
            <h2 style="margin-top:0">LETTER OF ACCEPTANCE / SURAT PENERIMAAN</h2>
            <div>Nomor: {{ $letter->letter_number }}</div>
            <div class="muted">Tanggal: {{ now()->format('d-m-Y') }}</div>
        </td>
        <td class="right" style="width:130px"><img src="{{ $qr }}" width="120" alt="QR verifikasi"></td>
    </tr>
</table>
@if ($customBody)
    {!! $customBody !!}
@else
    <p>Dengan hormat,</p>
    <p>Berdasarkan hasil seleksi Penerimaan Mahasiswa Baru <strong>{{ $application->period?->name }}</strong>, kami menyatakan bahwa:</p>
    <div class="box">
        <table>
            <tr><td style="width:35%">Nama</td><td><strong>{{ $application->account->profile?->full_name ?? $application->account->name }}</strong></td></tr>
            <tr><td>No. pendaftaran</td><td>{{ $application->registration_number }}</td></tr>
            <tr><td>Jalur</td><td>{{ $application->admissionPath?->name }}</td></tr>
            <tr><td>Program studi</td><td><strong>{{ $decision->studyProgram?->name }}</strong></td></tr>
            <tr><td>Sistem kuliah</td><td>{{ $decision->studySystem?->name }}</td></tr>
        </table>
    </div>
    <p><strong>DITERIMA</strong> sebagai calon mahasiswa baru. Silakan menyelesaikan daftar ulang melalui portal PMB paling lambat
        <strong>{{ optional($application->reRegistration?->effectiveDeadline())->format('d-m-Y H:i') }}</strong>. Penerimaan dinyatakan sah setelah daftar ulang disetujui.</p>
@endif
<p class="muted">Keaslian surat ini dapat diperiksa dengan memindai kode QR atau membuka {{ $verificationUrl }}.</p>
@endsection

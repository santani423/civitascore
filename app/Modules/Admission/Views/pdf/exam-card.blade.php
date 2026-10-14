@extends('admission::pdf._layout', ['title' => 'Kartu Peserta '.$participant->participant_number, 'universityName' => $application->university?->name])
@section('content')
<h2 style="margin-top:0">KARTU PESERTA UJIAN SELEKSI</h2>
<table class="box">
    <tr>
        <td style="width:110px">@if ($photo)<img src="{{ $photo }}" width="100" alt="Foto">@else<div class="box muted" style="height:120px;width:90px">Foto</div>@endif</td>
        <td>
            <table>
                <tr><td style="width:35%">No. peserta</td><td><strong style="font-size:14px">{{ $participant->participant_number }}</strong></td></tr>
                <tr><td>Nama</td><td>{{ $application->account->profile?->full_name ?? $application->account->name }}</td></tr>
                <tr><td>No. pendaftaran</td><td>{{ $application->registration_number }}</td></tr>
                <tr><td>Ujian</td><td>{{ $participant->schedule->title }}</td></tr>
                <tr><td>Sesi</td><td>{{ $participant->session->code }} — {{ $participant->session->starts_at->setTimezone($application->university?->timezone ?? config('app.timezone'))->format('d-m-Y H:i') }} s.d. {{ $participant->session->ends_at->setTimezone($application->university?->timezone ?? config('app.timezone'))->format('H:i') }}</td></tr>
                <tr><td>Lokasi</td><td>{{ $participant->session->room ? ($participant->session->room->venue?->name.' — '.$participant->session->room->name) : 'Daring' }}</td></tr>
            </table>
        </td>
        <td style="width:130px" class="right"><img src="{{ $qr }}" width="120" alt="QR check-in"></td>
    </tr>
</table>
<h2>Tata tertib</h2>
<ol>
    <li>Hadir paling lambat 30 menit sebelum sesi dimulai dengan membawa kartu ini dan identitas asli.</li>
    <li>Kode QR dipindai pengawas saat check-in; jangan membagikan kartu ini.</li>
    <li>Dilarang membawa alat komunikasi ke dalam ruang ujian kecuali diizinkan pengawas.</li>
    <li>Pelanggaran tata tertib dapat berakibat diskualifikasi.</li>
</ol>
@if ($participant->schedule->instructions)<div class="box">{{ $participant->schedule->instructions }}</div>@endif
@endsection

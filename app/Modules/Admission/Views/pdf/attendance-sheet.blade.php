@extends('admission::pdf._layout', ['title' => 'Daftar Hadir '.$session->code, 'universityName' => $universityName])
@section('content')
<h2 style="margin-top:0">DAFTAR HADIR — {{ $session->schedule->title }}</h2>
<div class="muted">Sesi {{ $session->code }} · {{ $session->starts_at->setTimezone($timezone)->format('d-m-Y H:i') }}–{{ $session->ends_at->setTimezone($timezone)->format('H:i') }} · {{ $session->room ? $session->room->venue?->name.' — '.$session->room->name : 'Daring' }}</div>
<table class="grid" style="margin-top:10px">
    <tr><th style="width:5%">No</th><th style="width:22%">No. Peserta</th><th>Nama</th><th style="width:16%">Status</th><th style="width:20%">Tanda tangan</th></tr>
    @foreach ($participants as $i => $p)
        <tr><td>{{ $i + 1 }}</td><td>{{ $p->participant_number }}</td><td>{{ $p->application->account->name }}</td><td>{{ $p->attendance_status->value }}</td><td></td></tr>
    @endforeach
</table>
<p style="margin-top:24px">Pengawas: ______________________</p>
@endsection

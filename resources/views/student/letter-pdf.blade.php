@extends('student.pdf-layout')

@section('title', $letter_title.' — '.$student['nim'])

@section('content')
    <h1>{{ $letter_title }}</h1>
    <p class="doc-subtitle">Nomor: {{ $number }}</p>

    <p>Yang bertanda tangan di bawah ini, Bagian Akademik {{ $student['university'] ?? 'universitas' }}, menerangkan bahwa:</p>

    <table class="meta">
        <tr><td class="label">Nama</td><td>{{ $student['name'] }}</td></tr>
        <tr><td class="label">NIM</td><td>{{ $student['nim'] }}</td></tr>
        <tr><td class="label">Program Studi</td><td>{{ $student['study_program'] }} ({{ $student['degree_level'] }})</td></tr>
        @if ($student['faculty'])
            <tr><td class="label">Fakultas</td><td>{{ $student['faculty'] }}</td></tr>
        @endif
        <tr><td class="label">Angkatan</td><td>{{ $student['admission_year'] }}</td></tr>
        @if ($term)
            <tr><td class="label">Semester</td><td>{{ $semester }} ({{ $term['label'] }})</td></tr>
        @endif
    </table>

    @if ($letter_type === 'active_student')
        <p>adalah benar mahasiswa yang terdaftar dan <strong>aktif mengikuti perkuliahan</strong> pada semester tersebut.</p>
    @elseif ($letter_type === 'good_conduct')
        <p>selama menjadi mahasiswa berkelakuan baik dan tidak pernah dikenai sanksi akademik.</p>
    @elseif ($letter_type === 'recommendation')
        <p>kami rekomendasikan untuk keperluan sebagaimana tersebut di bawah ini.</p>
    @else
        <p>adalah mahasiswa pada program studi tersebut di atas.</p>
    @endif

    <p>Surat keterangan ini diberikan untuk keperluan: <strong>{{ $purpose }}</strong>.</p>
    <p>Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>

    <table class="signature">
        <tr>
            <td></td>
            <td>
                {{ $issued_at->locale('id')->translatedFormat('j F Y') }}<br>
                Bagian Akademik,<div class="space"></div>
                <strong>{{ $studentRequest->decider?->name ?? '..............................' }}</strong>
            </td>
        </tr>
    </table>
@endsection

@extends('student.pdf-layout')

@section('title', 'Kartu Rencana Studi — '.$student['nim'])

@section('content')
    <h1>Kartu Rencana Studi (KRS)</h1>
    <p class="doc-subtitle">Semester {{ $term['label'] }}</p>

    <table class="meta">
        <tr>
            <td class="label">Nama</td><td>{{ $student['name'] }}</td>
            <td class="label">Semester</td><td>{{ $semester ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td><td>{{ $student['nim'] }}</td>
            <td class="label">Dosen Wali</td><td>{{ $advisor ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Angkatan</td><td>{{ $student['admission_year'] }}</td>
            <td class="label">Status KRS</td><td><span class="status">{{ $status }}</span></td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Mata Kuliah</th>
                <th class="num">Kelas</th>
                <th class="num">SKS</th>
                <th>Dosen</th>
                <th>Jadwal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item['code'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="num">{{ $item['class_code'] }}</td>
                    <td class="num">{{ $item['credits'] }}</td>
                    <td>{{ $item['lecturer'] ?? '-' }}</td>
                    <td>{{ $item['schedule'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total SKS</td>
                <td class="num">{{ $total_credits }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <table class="signature">
        <tr>
            <td>
                Mahasiswa,<div class="space"></div>
                <strong>{{ $student['name'] }}</strong><br>NIM {{ $student['nim'] }}
            </td>
            <td>
                Dosen Wali,<div class="space"></div>
                <strong>{{ $advisor ?? '..............................' }}</strong>
                @if ($decided_at)
                    <br>Disetujui {{ $decided_at->locale('id')->translatedFormat('j F Y') }}{{ $decided_by ? ' oleh '.$decided_by : '' }}
                @endif
            </td>
        </tr>
    </table>
@endsection

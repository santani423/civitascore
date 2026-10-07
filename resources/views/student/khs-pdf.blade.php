@extends('student.pdf-layout')

@section('title', 'Kartu Hasil Studi — '.$student['nim'])

@section('content')
    <h1>Kartu Hasil Studi (KHS)</h1>
    <p class="doc-subtitle">Semester {{ $term['label'] }}</p>

    <table class="meta">
        <tr>
            <td class="label">Nama</td><td>{{ $student['name'] }}</td>
            <td class="label">Semester</td><td>{{ $semester_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td><td>{{ $student['nim'] }}</td>
            <td class="label">Angkatan</td><td>{{ $student['admission_year'] }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Mata Kuliah</th>
                <th class="num">SKS</th>
                <th class="num">Nilai</th>
                <th class="num">Huruf</th>
                <th class="num">Bobot</th>
                <th class="num">Mutu</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $row['course_code'] }}</td>
                    <td>{{ $row['course_name'] }}</td>
                    <td class="num">{{ $row['credits'] }}</td>
                    <td class="num">{{ $row['score'] ?? '-' }}</td>
                    <td class="num">{{ $row['letter_grade'] ?? 'Belum dinilai' }}</td>
                    <td class="num">{{ $row['weight'] !== null ? number_format($row['weight'], 2, ',', '.') : '-' }}</td>
                    <td class="num">{{ $row['quality_points'] !== null ? number_format($row['quality_points'], 2, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $summary['term_credits'] }}</span><span class="caption">SKS Semester (dinilai)</span></td>
            <td><span class="value">{{ number_format($summary['ips'], 2, ',', '.') }}</span><span class="caption">IPS</span></td>
            <td><span class="value">{{ number_format($summary['ipk'], 2, ',', '.') }}</span><span class="caption">IPK (s.d. semester ini)</span></td>
            <td><span class="value">{{ $summary['cumulative_credits'] }}</span><span class="caption">Total SKS Kumulatif</span></td>
        </tr>
    </table>
@endsection

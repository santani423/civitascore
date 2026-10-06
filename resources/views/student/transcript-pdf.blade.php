@extends('student.pdf-layout')

@section('title', 'Transkrip Nilai — '.$student['nim'])

@section('content')
    <h1>Transkrip Nilai Sementara</h1>
    <p class="doc-subtitle">Nilai terbaik setiap mata kuliah yang telah ditempuh</p>

    <table class="meta">
        <tr>
            <td class="label">Nama</td><td>{{ $student['name'] }}</td>
            <td class="label">Program Studi</td><td>{{ $student['study_program'] }} ({{ $student['degree_level'] }})</td>
        </tr>
        <tr>
            <td class="label">NIM</td><td>{{ $student['nim'] }}</td>
            <td class="label">Angkatan</td><td>{{ $student['admission_year'] }}</td>
        </tr>
        <tr>
            <td class="label">Status</td><td>{{ $student['status'] }}</td>
            <td></td><td></td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Mata Kuliah</th>
                <th>Semester Ditempuh</th>
                <th class="num">SKS</th>
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
                    <td>{{ $row['term_label'] }}</td>
                    <td class="num">{{ $row['credits'] }}</td>
                    <td class="num">{{ $row['letter_grade'] }}</td>
                    <td class="num">{{ number_format($row['weight'], 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['quality_points'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $summary['course_count'] }}</span><span class="caption">Mata Kuliah</span></td>
            <td><span class="value">{{ $summary['total_credits'] }}</span><span class="caption">Total SKS</span></td>
            <td><span class="value">{{ $summary['passed_credits'] }}</span><span class="caption">SKS Lulus</span></td>
            <td><span class="value">{{ number_format($summary['ipk'], 2, ',', '.') }}</span><span class="caption">IPK</span></td>
        </tr>
    </table>
@endsection

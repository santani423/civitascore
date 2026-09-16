<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Nilai — {{ $exam->title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .subtitle { color: #555; margin: 0 0 16px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.meta td { padding: 3px 6px; vertical-align: top; }
        table.meta td.label { width: 140px; color: #555; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .summary td { width: 16.6%; text-align: center; border: 1px solid #ddd; padding: 8px 4px; }
        .summary .value { font-size: 14px; font-weight: bold; display: block; }
        .summary .caption { color: #555; font-size: 9px; }
        table.recap { width: 100%; border-collapse: collapse; }
        table.recap th, table.recap td { border: 1px solid #ddd; padding: 4px 6px; font-size: 10px; text-align: left; }
        table.recap th { background: #f8fafc; }
        table.recap td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>Rekap Nilai Ujian</h1>
    <p class="subtitle">{{ $exam->title }} — {{ $summary['course_name'] ?? '-' }}</p>

    <table class="meta">
        <tr>
            <td class="label">Mata Kuliah</td>
            <td>{{ $summary['course_name'] ?? '-' }}</td>
            <td class="label">Kelas</td>
            <td>{{ $summary['class_code'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Semester</td>
            <td>{{ $summary['semester_label'] ?? '-' }}</td>
            <td class="label">Total Peserta</td>
            <td>{{ $summary['total_participants'] }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $summary['completed'] }}</span><span class="caption">Selesai</span></td>
            <td><span class="value">{{ $summary['in_progress'] }}</span><span class="caption">Sedang Mengerjakan</span></td>
            <td><span class="value">{{ $summary['not_started'] }}</span><span class="caption">Belum Mengerjakan</span></td>
            <td><span class="value">{{ $summary['average_score'] ?? '-' }}</span><span class="caption">Rata-rata</span></td>
            <td><span class="value">{{ $summary['highest_score'] ?? '-' }}</span><span class="caption">Nilai Tertinggi</span></td>
            <td><span class="value">{{ $summary['lowest_score'] ?? '-' }}</span><span class="caption">Nilai Terendah</span></td>
        </tr>
    </table>

    <table class="recap">
        <thead>
            <tr>
                <th>No</th>
                <th>NIM</th>
                <th>Nama</th>
                <th>Raw Score</th>
                <th>Penalty</th>
                <th>Final Score</th>
                <th>Grade</th>
                <th>Weighted Score</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="num">{{ $row['number'] }}</td>
                    <td>{{ $row['nim'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="num">{{ $row['attempt']?->raw_score ?? '-' }}</td>
                    <td class="num">{{ $row['attempt']?->penalty_score ?? '-' }}</td>
                    <td class="num">{{ $row['attempt']?->score ?? '-' }}</td>
                    <td>{{ $row['attempt']?->grade ?? '-' }}</td>
                    <td class="num">{{ $row['attempt']?->weighted_score ?? '-' }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

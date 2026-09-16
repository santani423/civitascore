<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Ujian — {{ $result['exam']['title'] }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #ccc; }
        .subtitle { color: #555; margin: 0 0 16px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.meta td { padding: 3px 6px; vertical-align: top; }
        table.meta td.label { width: 140px; color: #555; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .summary td { width: 20%; text-align: center; border: 1px solid #ddd; padding: 8px 4px; }
        .summary .value { font-size: 15px; font-weight: bold; display: block; }
        .summary .caption { color: #555; font-size: 9px; }
        .question { border: 1px solid #ddd; padding: 8px 10px; margin-bottom: 8px; page-break-inside: avoid; }
        .question.correct { border-left: 3px solid #16a34a; }
        .question.wrong { border-left: 3px solid #dc2626; }
        .question-title { font-weight: bold; margin-bottom: 6px; }
        .status-badge { float: right; font-weight: bold; }
        .status-badge.correct { color: #16a34a; }
        .status-badge.wrong { color: #dc2626; }
        ul.options { list-style: none; margin: 0; padding: 0; }
        ul.options li { padding: 3px 6px; margin-bottom: 2px; border: 1px solid #eee; }
        ul.options li.selected-correct { background: #dcfce7; border-color: #16a34a; }
        ul.options li.selected-wrong { background: #fee2e2; border-color: #dc2626; }
        ul.options li.correct-answer { background: #f0fdf4; border-color: #86efac; }
        .tag { font-size: 9px; font-weight: bold; }
        .explanation { margin-top: 6px; padding: 6px 8px; background: #f8fafc; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <h1>Hasil Ujian</h1>
    <p class="subtitle">{{ $result['exam']['title'] }} — {{ $result['exam']['course_name'] ?? '-' }}</p>

    <table class="meta">
        <tr>
            <td class="label">Nama Mahasiswa</td>
            <td>{{ $result['student']['name'] }}</td>
            <td class="label">Waktu Mulai</td>
            <td>{{ \Carbon\Carbon::parse($result['attempt']['started_at'])->translatedFormat('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td>{{ $result['student']['nim'] }}</td>
            <td class="label">Waktu Selesai</td>
            <td>{{ $result['attempt']['submitted_at'] ? \Carbon\Carbon::parse($result['attempt']['submitted_at'])->translatedFormat('d M Y H:i') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Mata Kuliah</td>
            <td>{{ $result['exam']['course_name'] ?? '-' }}</td>
            <td class="label">Durasi Pengerjaan</td>
            <td>
                @if ($result['attempt']['duration_seconds'] !== null)
                    {{ sprintf('%02d:%02d:%02d', intdiv($result['attempt']['duration_seconds'], 3600), intdiv($result['attempt']['duration_seconds'] % 3600, 60), $result['attempt']['duration_seconds'] % 60) }}
                @else
                    -
                @endif
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="value">{{ $result['summary']['total_questions'] }}</span><span class="caption">Jumlah Soal</span></td>
            <td><span class="value">{{ $result['summary']['correct_answers'] }}</span><span class="caption">Jumlah Benar</span></td>
            <td><span class="value">{{ $result['summary']['wrong_answers'] }}</span><span class="caption">Jumlah Salah</span></td>
            <td><span class="value">{{ $result['summary']['score'] }}</span><span class="caption">Nilai Akhir</span></td>
            <td><span class="value">{{ $result['summary']['percentage'] }}%</span><span class="caption">Persentase</span></td>
        </tr>
    </table>

    <h2>Koreksi Jawaban</h2>

    @foreach ($result['questions'] as $question)
        <div class="question {{ $question['is_correct'] ? 'correct' : 'wrong' }}">
            <div class="question-title">
                Soal {{ $question['number'] }}
                <span class="status-badge {{ $question['is_correct'] ? 'correct' : 'wrong' }}">
                    {{ $question['is_correct'] ? 'BENAR' : 'SALAH' }}
                </span>
            </div>
            <p>{{ $question['question_text'] }}</p>

            <ul class="options">
                @foreach ($question['options'] as $option)
                    @php
                        $isSelected = $option['id'] === $question['selected_option_id'];
                        $isCorrectOption = $option['id'] === $question['correct_option_id'];
                        $class = $isCorrectOption ? 'correct-answer' : ($isSelected ? 'selected-wrong' : '');
                        if ($isSelected && $isCorrectOption) {
                            $class = 'selected-correct';
                        }
                    @endphp
                    <li class="{{ $class }}">
                        {{ $option['option_text'] }}
                        @if ($isSelected)
                            <span class="tag"> — Jawaban Anda</span>
                        @endif
                        @if ($isCorrectOption)
                            <span class="tag"> — Jawaban Benar</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if (!empty($question['explanation']))
                <div class="explanation"><strong>Pembahasan:</strong> {{ $question['explanation'] }}</div>
            @endif
        </div>
    @endforeach
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Naskah Ujian — {{ $exam->title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #ccc; }
        .subtitle { color: #555; margin: 0 0 16px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.meta td { padding: 3px 6px; vertical-align: top; }
        table.meta td.label { width: 140px; color: #555; }
        .question { border: 1px solid #ddd; padding: 8px 10px; margin-bottom: 8px; page-break-inside: avoid; }
        .question-title { font-weight: bold; margin-bottom: 6px; }
        ul.options { list-style: none; margin: 0; padding: 0; }
        ul.options li { padding: 3px 6px; margin-bottom: 2px; border: 1px solid #eee; }
        ul.options li.correct-answer { background: #f0fdf4; border-color: #86efac; }
        .tag { font-size: 9px; font-weight: bold; color: #16a34a; }
        .notice { margin-top: 4px; padding: 6px 8px; background: #fef9c3; border: 1px solid #fde047; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Naskah Ujian</h1>
    <p class="subtitle">{{ $exam->title }}</p>

    <table class="meta">
        <tr>
            <td class="label">Mata Kuliah</td>
            <td>{{ $exam->classSection->course?->name ?? '-' }}</td>
            <td class="label">Kelas</td>
            <td>{{ $exam->classSection->class_code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Semester</td>
            <td>{{ $exam->classSection->academicTerm?->academic_year ?? '-' }}</td>
            <td class="label">Durasi</td>
            <td>{{ $exam->duration_minutes }} menit</td>
        </tr>
        <tr>
            <td class="label">Dosen</td>
            <td>{{ $exam->creator?->name ?? '-' }}</td>
            <td class="label">Tanggal</td>
            <td>{{ $exam->starts_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    @if (!$withAnswers)
        <div class="notice">Dokumen ini tidak menyertakan kunci jawaban.</div>
    @endif

    <h2>Soal Ujian</h2>

    @foreach ($exam->questions as $index => $question)
        <div class="question">
            <div class="question-title">Soal {{ $index + 1 }} ({{ $question->points }} poin)</div>
            <p>{{ $question->question_text }}</p>

            <ul class="options">
                @foreach ($question->options as $optionIndex => $option)
                    <li class="{{ $withAnswers && $option->is_correct ? 'correct-answer' : '' }}">
                        {{ chr(65 + $optionIndex) }}. {{ $option->option_text }}
                        @if ($withAnswers && $option->is_correct)
                            <span class="tag"> — Jawaban Benar</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</body>
</html>

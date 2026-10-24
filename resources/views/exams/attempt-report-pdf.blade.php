<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Ujian — {{ $report['student']['name'] }} — {{ $report['exam']['title'] }}</title>
    @php
        $tz = $report['timezone'];
        $dateTime = fn (?string $iso) => $iso ? \Carbon\Carbon::parse($iso)->setTimezone($tz)->locale('id')->translatedFormat('d M Y, H:i:s T') : '-';
        $time = fn (?string $iso) => $iso ? \Carbon\Carbon::parse($iso)->setTimezone($tz)->format('H:i:s') : '-';
        $duration = fn (?int $seconds) => $seconds === null ? '-' : sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
        $statusLabel = ['correct' => 'Benar', 'wrong' => 'Salah', 'unanswered' => 'Tidak Dijawab'];
        $attempt = $report['attempt'];
        $summary = $report['summary'];
    @endphp
    <style>
        @page { margin: 16mm 14mm 18mm 14mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 15px; margin: 0 0 2px; letter-spacing: 0.3px; }
        h2 { font-size: 12px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; color: #166534; }
        .subtitle { color: #555; margin: 0 0 12px; }
        .muted { color: #64748b; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 3px; }
        table { border-collapse: collapse; }
        table.meta { width: 100%; }
        table.meta td { padding: 2px 6px 2px 0; vertical-align: top; }
        table.meta td.label { width: 105px; color: #64748b; }
        table.boxes { width: 100%; margin-bottom: 4px; table-layout: fixed; }
        table.boxes td { text-align: center; border: 1px solid #e2e8f0; padding: 6px 2px; }
        table.boxes .value { font-size: 13px; font-weight: bold; display: block; }
        table.boxes .caption { color: #64748b; font-size: 8px; }
        table.grid { width: 100%; }
        table.grid th, table.grid td { border: 1px solid #e2e8f0; padding: 3px 5px; text-align: left; vertical-align: top; }
        table.grid th { background: #f1f5f9; font-size: 9px; }
        table.grid td.center, table.grid th.center { text-align: center; }
        .correct { color: #166534; font-weight: bold; }
        .wrong { color: #dc2626; font-weight: bold; }
        .unanswered { color: #64748b; font-weight: bold; }
        .danger { color: #dc2626; }
        .question { border: 1px solid #e2e8f0; padding: 6px 8px; margin-bottom: 6px; page-break-inside: avoid; }
        .question.q-correct { border-left: 3px solid #16a34a; }
        .question.q-wrong { border-left: 3px solid #dc2626; }
        .question.q-unanswered { border-left: 3px solid #94a3b8; }
        .question-head { margin-bottom: 4px; }
        .question-head .right { float: right; }
        .question-text { margin: 0 0 5px; font-size: 10.5px; }
        table.options { width: 100%; }
        table.options td { padding: 2px 5px; border: 1px solid #f1f5f9; }
        table.options td.letter { width: 14px; font-weight: bold; text-align: center; }
        table.options tr.opt-key td { background: #f0fdf4; border-color: #86efac; }
        table.options tr.opt-selected-wrong td { background: #fee2e2; border-color: #fca5a5; }
        table.options tr.opt-selected-correct td { background: #dcfce7; border-color: #16a34a; }
        .tag { font-size: 8px; font-weight: bold; }
        .signature { width: 100%; margin-top: 24px; page-break-inside: avoid; }
        .signature td { width: 50%; vertical-align: top; }
    </style>
</head>
<body>
    <div class="footer">
        {{-- Nomor "Halaman X dari Y" ditulis ExamAttemptController::reportPdf() lewat canvas DomPDF (counter(pages) tidak didukung CSS-nya). --}}
        Dicetak {{ $dateTime($report['generated_at']) }} · {{ $report['student']['nim'] }} — {{ $report['exam']['title'] }}
    </div>

    <h1>LAPORAN PENGERJAAN UJIAN</h1>
    <p class="subtitle">{{ $report['exam']['title'] }} — {{ $report['exam']['course_name'] ?? '-' }}</p>

    <h2>Identitas</h2>
    <table class="meta">
        <tr>
            <td class="label">Nama Mahasiswa</td>
            <td><strong>{{ $report['student']['name'] }}</strong></td>
            <td class="label">Mata Kuliah</td>
            <td>{{ $report['exam']['course_code'] ? $report['exam']['course_code'].' — ' : '' }}{{ $report['exam']['course_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td>{{ $report['student']['nim'] }}</td>
            <td class="label">Kelas</td>
            <td>{{ $report['exam']['class_code'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Program Studi</td>
            <td>{{ $report['student']['study_program'] ?? '-' }}</td>
            <td class="label">Semester</td>
            <td>{{ $report['exam']['academic_term'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Percobaan</td>
            <td>Ke-{{ $attempt['attempt_number'] }} dari maks. {{ $report['exam']['max_attempts'] }}</td>
            <td class="label">Dosen Pengampu</td>
            <td>{{ $report['exam']['lecturer_name'] ?? '-' }}</td>
        </tr>
    </table>

    <h2>Waktu Pengerjaan</h2>
    <table class="meta">
        <tr>
            <td class="label">Mulai Mengerjakan</td>
            <td>{{ $dateTime($attempt['started_at']) }}</td>
            <td class="label">Jadwal Ujian</td>
            <td>{{ $dateTime($report['exam']['starts_at']) }} s.d. {{ $dateTime($report['exam']['ends_at']) }}</td>
        </tr>
        <tr>
            <td class="label">Selesai / Dikumpulkan</td>
            <td>{{ $dateTime($attempt['submitted_at']) }}</td>
            <td class="label">Batas Waktu Peserta</td>
            <td>{{ $dateTime($attempt['deadline_at']) }}</td>
        </tr>
        <tr>
            <td class="label">Durasi Pengerjaan</td>
            <td><strong>{{ $duration($attempt['duration_seconds']) }}</strong> dari {{ $duration($attempt['allowed_seconds']) }} yang tersedia</td>
            <td class="label">Durasi Ujian</td>
            <td>{{ $report['exam']['duration_minutes'] }} menit</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>
                @if ($attempt['status'] === 'submitted')
                    Sudah Dikumpulkan ({{ $attempt['submission_type'] === 'auto_timeout' ? 'otomatis, waktu habis' : 'dikumpulkan peserta' }})
                @else
                    Sedang Mengerjakan
                @endif
            </td>
            <td class="label"></td>
            <td></td>
        </tr>
    </table>

    <h2>Ringkasan Nilai</h2>
    <table class="boxes">
        <tr>
            <td><span class="value">{{ $summary['total_questions'] }}</span><span class="caption">Jumlah Soal</span></td>
            <td><span class="value">{{ $summary['answered'] }}</span><span class="caption">Dijawab</span></td>
            <td><span class="value correct">{{ $summary['correct_answers'] }}</span><span class="caption">Benar</span></td>
            <td><span class="value wrong">{{ $summary['wrong_answers'] }}</span><span class="caption">Salah</span></td>
            <td><span class="value unanswered">{{ $summary['unanswered'] }}</span><span class="caption">Tidak Dijawab</span></td>
            <td><span class="value">{{ $summary['earned_points'] }} / {{ $summary['total_points'] }}</span><span class="caption">Poin</span></td>
        </tr>
    </table>
    <table class="boxes">
        <tr>
            <td><span class="value">{{ $summary['raw_score'] ?? '-' }}</span><span class="caption">Raw Score</span></td>
            <td><span class="value danger">{{ (float) $summary['penalty_score'] > 0 ? '-'.$summary['penalty_score'] : '0.00' }}</span><span class="caption">Penalti ({{ $summary['violation_count'] }}x Pelanggaran)</span></td>
            <td><span class="value">{{ $summary['score'] ?? '-' }}</span><span class="caption">Final Score</span></td>
            <td><span class="value">{{ $summary['grade'] ?? '-' }}</span><span class="caption">Grade</span></td>
            <td><span class="value">{{ $summary['weighted_score'] ?? '-' }}</span><span class="caption">Weighted Score{{ $report['exam']['weight_percentage'] !== null ? ' ('.$report['exam']['weight_percentage'].'%)' : '' }}</span></td>
        </tr>
    </table>

    <h2>Lembar Jawaban</h2>
    <table class="grid">
        <thead>
            <tr>
                <th class="center">No</th>
                <th class="center">Jawaban</th>
                <th class="center">Kunci</th>
                <th>Status</th>
                <th class="center">Poin</th>
                <th>Jawaban Disimpan</th>
                <th class="center">Menit ke-</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['questions'] as $question)
                <tr>
                    <td class="center">{{ $question['number'] }}</td>
                    <td class="center">{{ $question['selected_option_label'] ?? '-' }}</td>
                    <td class="center">{{ $question['correct_option_label'] ?? '-' }}</td>
                    <td class="{{ $question['status'] }}">{{ $statusLabel[$question['status']] }}</td>
                    <td class="center">{{ $question['earned_points'] }} / {{ $question['points'] }}</td>
                    <td>{{ $time($question['answered_at']) }}</td>
                    <td class="center">{{ $question['answered_after_seconds'] !== null ? $duration($question['answered_after_seconds']) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted">Waktu jawaban adalah saat jawaban terakhir disimpan (jika peserta mengganti jawaban, yang tercatat waktu perubahan terakhir).</p>

    <h2>Pelanggaran</h2>
    @if (count($report['violations']) === 0)
        <p>Tidak ada pelanggaran tercatat pada percobaan ini.</p>
    @else
        <table class="grid" style="margin-bottom: 6px;">
            <thead>
                <tr>
                    <th>Jenis Pelanggaran</th>
                    <th class="center">Jumlah</th>
                    <th class="center">Total Penalti</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['violation_breakdown'] as $row)
                    <tr>
                        <td>{{ $row['violation_label'] }}</td>
                        <td class="center">{{ $row['count'] }}x</td>
                        <td class="center danger">-{{ $row['penalty_points'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="grid">
            <thead>
                <tr>
                    <th class="center">#</th>
                    <th>Jenis</th>
                    <th>Waktu Kejadian</th>
                    <th class="center">Menit ke-</th>
                    <th class="center">Penalti</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['violations'] as $violation)
                    <tr>
                        <td class="center">{{ $violation['sequence_number'] }}</td>
                        <td>{{ $violation['violation_label'] }}</td>
                        <td>{{ $dateTime($violation['occurred_at']) }}</td>
                        <td class="center">{{ $duration($violation['occurred_after_seconds']) }}</td>
                        <td class="center danger">-{{ $violation['penalty_points'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Linimasa Aktivitas</h2>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 70px;">Waktu</th>
                <th class="center" style="width: 60px;">Menit ke-</th>
                <th>Aktivitas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['timeline'] as $event)
                <tr>
                    <td>{{ $time($event['occurred_at']) }}</td>
                    <td class="center">{{ $duration($event['elapsed_seconds']) }}</td>
                    <td class="{{ $event['type'] === 'violation' ? 'danger' : '' }}">{{ $event['description'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 style="page-break-before: always;">Detail Jawaban per Soal</h2>
    @foreach ($report['questions'] as $question)
        <div class="question q-{{ $question['status'] }}">
            <div class="question-head">
                <strong>Soal {{ $question['number'] }}</strong>
                <span class="muted"> · {{ $question['points'] }} poin{{ $question['answered_at'] ? ' · disimpan '.$time($question['answered_at']) : '' }}</span>
                <span class="right {{ $question['status'] }}">{{ strtoupper($statusLabel[$question['status']]) }}</span>
            </div>
            <p class="question-text">{{ $question['question_text'] ?? '(Soal tidak lagi tersedia)' }}</p>
            <table class="options">
                @foreach ($question['options'] as $option)
                    @php
                        $isSelected = $option['id'] === $question['selected_option_id'];
                        $rowClass = match (true) {
                            $isSelected && $option['is_correct'] => 'opt-selected-correct',
                            $isSelected => 'opt-selected-wrong',
                            $option['is_correct'] => 'opt-key',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="letter">{{ $option['label'] }}</td>
                        <td>
                            {{ $option['option_text'] }}
                            @if ($isSelected)
                                <span class="tag"> — Jawaban Mahasiswa</span>
                            @endif
                            @if ($option['is_correct'])
                                <span class="tag"> — Kunci Jawaban</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
            @if ($question['selected_option_id'] === null)
                <p class="muted" style="margin: 4px 0 0;"><em>Mahasiswa tidak menjawab soal ini.</em></p>
            @endif
        </div>
    @endforeach

    <table class="signature">
        <tr>
            <td></td>
            <td>
                Dosen Pengampu,<br><br><br><br><br>
                <strong>{{ $report['exam']['lecturer_name'] ?? '..............................' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>

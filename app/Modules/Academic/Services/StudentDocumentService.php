<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentRequestType;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\KrsSubmission;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Support\AcademicClock;
use Modules\Academic\Support\PortalFormatter;

/**
 * Dokumen akademik milik mahasiswa (KRS, KHS, transkrip, surat keterangan
 * yang disetujui) dalam bentuk PDF — memakai dompdf seperti PDF hasil ujian
 * yang sudah ada (resources/views/exams). Isi dokumen diambil dari service
 * yang sama dengan halaman portal, sehingga angka di PDF selalu sama dengan
 * yang tampil di layar.
 */
class StudentDocumentService
{
    public function __construct(
        private readonly StudentGradeService $grades,
        private readonly StudentAcademicService $academics,
        private readonly AcademicClock $clock,
    ) {}

    /**
     * Daftar dokumen yang saat ini tersedia untuk diunduh.
     *
     * @return array<int, array<string, mixed>>
     */
    public function available(Student $student): array
    {
        $documents = [];

        $krsTerms = KrsItem::query()
            ->where('student_id', $student->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->with('academicTerm')
            ->get()
            ->pluck('academicTerm')
            ->unique('id')
            ->sortByDesc(fn (AcademicTerm $term) => $term->start_date->format('Ymd'));

        foreach ($krsTerms as $term) {
            $documents[] = [
                'type' => 'krs',
                'title' => "Kartu Rencana Studi {$term->label()}",
                'description' => 'Daftar mata kuliah yang diambil beserta status persetujuan.',
                'url' => "/student/documents/krs?academic_term_id={$term->id}",
                'filename' => $this->filename('KRS', $student, $term->label()),
            ];
        }

        foreach ($this->grades->khs($student)['terms'] as $term) {
            $documents[] = [
                'type' => 'khs',
                'title' => "Kartu Hasil Studi {$term['label']}",
                'description' => 'Nilai, IPS, dan IPK semester tersebut.',
                'url' => "/student/documents/khs/{$term['id']}",
                'filename' => $this->filename('KHS', $student, $term['label']),
            ];
        }

        if ($this->grades->transcript($student)['rows'] !== []) {
            $documents[] = [
                'type' => 'transcript',
                'title' => 'Transkrip Nilai Sementara',
                'description' => 'Seluruh mata kuliah yang telah ditempuh beserta IPK.',
                'url' => '/student/documents/transcript',
                'filename' => $this->filename('Transkrip', $student),
            ];
        }

        StudentRequest::query()
            ->where('student_id', $student->id)
            ->where('type', StudentRequestType::Letter)
            ->where('status', StudentRequestStatus::Approved)
            ->latest('decided_at')
            ->get()
            ->each(function (StudentRequest $request) use (&$documents, $student): void {
                $documents[] = [
                    'type' => 'letter',
                    'title' => StudentRequestService::LETTER_TYPES[$request->payload['letter_type'] ?? 'other'] ?? $request->title,
                    'description' => 'Keperluan: '.($request->payload['purpose'] ?? '-'),
                    'url' => "/student/requests/{$request->id}/letter",
                    'filename' => $this->filename('Surat', $student, $request->decided_at?->format('Ymd')),
                ];
            });

        return $documents;
    }

    public function krsPdf(Student $student, AcademicTerm $term): Response
    {
        $items = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->with(['classSection.course', 'classSection.lecturer', 'classSection.schedules'])
            ->get();

        if ($items->isEmpty()) {
            throw new ConflictException('Belum ada KRS pada semester tersebut.');
        }

        $submission = KrsSubmission::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->with('decider')
            ->first();

        $student->loadMissing('academicAdvisor');

        return $this->render('student.krs-pdf', [
            'student' => $this->grades->studentHeader($student),
            'semester' => $this->academics->semesterNumber($student, $term),
            'term' => PortalFormatter::term($term),
            'advisor' => $student->academicAdvisor?->name,
            'status' => $submission?->status->label() ?? 'Disetujui',
            'decided_at' => $submission?->decided_at,
            'decided_by' => $submission?->decider?->name,
            'items' => $items->map(fn (KrsItem $item): array => [
                'code' => $item->classSection->course->code,
                'name' => $item->classSection->course->name,
                'class_code' => $item->classSection->class_code,
                'credits' => $item->classSection->course->credits,
                'lecturer' => $item->classSection->lecturer?->name,
                'schedule' => $item->classSection->schedules->map(fn ($schedule) => PortalFormatter::scheduleText($schedule))->join('; '),
                'status' => $item->status->label(),
            ])->sortBy('code')->values()->all(),
            'total_credits' => $items->sum(fn (KrsItem $item) => $item->classSection->course->credits),
        ], $this->filename('KRS', $student, $term->label()));
    }

    public function khsPdf(Student $student, AcademicTerm $term): Response
    {
        $khs = $this->grades->khs($student, $term->id);

        return $this->render('student.khs-pdf', [
            'student' => $this->grades->studentHeader($student),
            ...$khs,
        ], $this->filename('KHS', $student, $term->label()));
    }

    public function transcriptPdf(Student $student): Response
    {
        $transcript = $this->grades->transcript($student);

        if ($transcript['rows'] === []) {
            throw new ConflictException('Transkrip belum tersedia karena belum ada nilai.');
        }

        return $this->render('student.transcript-pdf', $transcript, $this->filename('Transkrip', $student));
    }

    public function letterPdf(StudentRequest $request): Response
    {
        if ($request->type !== StudentRequestType::Letter || $request->status !== StudentRequestStatus::Approved) {
            throw new ConflictException('Surat hanya tersedia untuk pengajuan surat yang sudah disetujui.');
        }

        $student = $request->student;
        $term = $request->academicTerm ?? $this->academics->currentTerm();
        $decidedAt = ($request->decided_at ?? now())->setTimezone($this->clock->timezone($student->university_id));

        return $this->render('student.letter-pdf', [
            'student' => $this->grades->studentHeader($student),
            'semester' => $term ? $this->academics->semesterNumber($student, $term) : null,
            'term' => PortalFormatter::term($term),
            'letter_title' => StudentRequestService::LETTER_TYPES[$request->payload['letter_type'] ?? 'other'] ?? $request->title,
            'letter_type' => $request->payload['letter_type'] ?? 'other',
            'purpose' => $request->payload['purpose'] ?? '-',
            'number' => sprintf('%s/AK/%s', strtoupper(substr($request->id, -6)), $decidedAt->format('m/Y')),
            'issued_at' => $decidedAt,
            'studentRequest' => $request,
        ], $this->filename('Surat', $student, $decidedAt->format('Ymd')));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $view, array $data, string $filename): Response
    {
        return Pdf::loadView($view, [
            ...$data,
            'generated_at' => now()->setTimezone($this->clock->timezone())->locale('id'),
        ])->download($filename);
    }

    private function filename(string $prefix, Student $student, ?string $suffix = null): string
    {
        return Str::slug(collect([$prefix, $student->nim, $suffix])->filter()->join(' ')).'.pdf';
    }
}

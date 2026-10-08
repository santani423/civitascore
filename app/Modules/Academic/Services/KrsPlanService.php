<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\KrsSubmissionStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\CoursePrerequisite;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\KrsSubmission;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\AcademicClock;
use Modules\Academic\Support\ClassSectionAccess;
use Modules\Academic\Support\LecturerIdentity;
use Modules\Academic\Support\PortalFormatter;

/**
 * KRS mandiri mahasiswa (RANCANGAN-APLIKASI.md §4.10) beserta persetujuan
 * dosen wali (§4.19). Alurnya:
 *
 *   draft (memilih kelas) → submitted (menunggu dosen wali)
 *     → approved (baris KRS jadi Enrolled: muncul di jadwal, ujian, absensi, nilai)
 *     → rejected (baris kembali draft, mahasiswa merevisi lalu mengajukan ulang)
 *
 * Seluruh validasi dijalankan di server setiap kali KRS diubah: status
 * mahasiswa aktif, semester & periode KRS aktif, kelas tersedia untuk
 * prodinya, mata kuliah ganda, sudah lulus, prasyarat, batas SKS (dari IP
 * semester sebelumnya, AcademicRecordService), kapasitas kelas, dan bentrok
 * jadwal. Mahasiswa yang dilayani selalu diresolusi dari akun yang login
 * (controller) — tidak ada student_id dari klien.
 *
 * Persetujuan sengaja tidak memakai Modul ApprovalWorkflow: penyetujunya
 * adalah dosen wali milik masing-masing mahasiswa (students.academic_advisor_id),
 * sedangkan ApprovalWorkflow hanya mengenal penyetuju tetap per template
 * (role/user). Status persetujuan tetap satu sumber: krs_submissions.
 */
class KrsPlanService
{
    public function __construct(
        private readonly AcademicRecordService $records,
        private readonly StudentAcademicService $academics,
        private readonly StudentNotificationService $notifications,
        private readonly ClassSectionAccess $access,
        private readonly AcademicClock $clock,
        private readonly LecturerIdentity $identity,
    ) {}

    /**
     * Ringkasan KRS semester aktif: status pengajuan, mata kuliah yang
     * dipilih, total & batas SKS, dan apakah KRS masih bisa diubah (beserta
     * alasannya kalau tidak).
     *
     * @return array<string, mixed>
     */
    public function overview(Student $student): array
    {
        $student->loadMissing('academicAdvisor');
        $term = $this->academics->currentTerm();

        if ($term === null) {
            return [
                'term' => null,
                'status' => 'no_active_term',
                'status_label' => 'Belum Ada Semester Aktif',
                'submission' => null,
                'items' => [],
                'total_credits' => 0,
                'max_credits' => null,
                'remaining_credits' => null,
                'can_edit' => false,
                'can_submit' => false,
                'can_cancel_submission' => false,
                'notices' => ['Belum ada semester aktif. Hubungi Bagian Akademik.'],
                'academic_advisor' => PortalFormatter::lecturer($student->academicAdvisor),
            ];
        }

        $now = $this->clock->now($student->university_id);
        $isOpen = $term->isKrsOpen($now);
        $submission = $this->submissionFor($student, $term);
        $items = $this->planItems($student, $term);
        $status = $this->effectiveStatus($submission, $items);
        $credits = $this->sumCredits($items);
        $maxCredits = $this->records->maxSksForTerm($student, $term);
        $notices = $this->editBlockers($student, $term, $status, $now);

        return [
            'term' => PortalFormatter::term($term, $isOpen),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'submission' => $submission ? $this->formatSubmission($submission) : null,
            'items' => $items->map(fn (KrsItem $item) => $this->formatItem($item))->values()->all(),
            'total_credits' => $credits,
            'max_credits' => $maxCredits,
            'remaining_credits' => max(0, $maxCredits - $credits),
            'can_edit' => $notices === [],
            'can_submit' => $notices === [] && $items->contains(fn (KrsItem $item) => $item->status === KrsItemStatus::Draft),
            'can_cancel_submission' => $status === KrsSubmissionStatus::Submitted->value && $isOpen,
            'notices' => $notices,
            'academic_advisor' => PortalFormatter::lecturer($student->academicAdvisor),
        ];
    }

    /**
     * Kelas yang ditawarkan semester aktif untuk prodi mahasiswa,
     * dikelompokkan per mata kuliah, lengkap dengan kelayakan per mata
     * kuliah (sudah lulus, prasyarat) dan per kelas (kursi, bentrok jadwal).
     *
     * @return array<string, mixed>
     */
    public function offerings(Student $student): array
    {
        $term = $this->academics->currentTerm();

        if ($term === null) {
            return ['term' => null, 'courses' => [], 'total_credits' => 0, 'max_credits' => null];
        }

        $classes = ClassSection::query()
            ->where('academic_term_id', $term->id)
            ->where('study_program_id', $student->study_program_id)
            ->where('is_active', true)
            ->whereHas('course', fn (Builder $query) => $query->where('is_active', true))
            ->with(['course.prerequisites.prerequisite', 'lecturer', 'schedules'])
            ->withCount(['krsItems as seats_taken' => fn (Builder $query) => $query->whereIn('status', KrsItemStatus::seatHolding())])
            ->get();

        $planItems = $this->planItems($student, $term);
        $bestGrades = $this->bestGradesByCourse($student);

        $courses = $classes
            ->groupBy('course_id')
            ->map(function (EloquentCollection $courseClasses) use ($planItems, $bestGrades): array {
                /** @var ClassSection $first */
                $first = $courseClasses->first();
                $course = $first->course;
                $bestGrade = $bestGrades[$course->id] ?? null;
                $selected = $planItems->first(fn (KrsItem $item) => $item->classSection->course_id === $course->id);

                $prerequisites = $course->prerequisites->map(fn (CoursePrerequisite $prerequisite): array => [
                    'course_id' => $prerequisite->prerequisite_course_id,
                    'code' => $prerequisite->prerequisite?->code,
                    'name' => $prerequisite->prerequisite?->name,
                    'min_grade' => ($prerequisite->min_letter_grade ?? LetterGrade::PASSING)->value,
                    'satisfied' => $this->prerequisiteSatisfied($prerequisite, $bestGrades),
                ])->values()->all();

                $blockers = [];

                if ($bestGrade?->isPassing()) {
                    $blockers[] = "Sudah lulus dengan nilai {$bestGrade->value}.";
                }

                $unmet = collect($prerequisites)->where('satisfied', false);

                if ($unmet->isNotEmpty()) {
                    $blockers[] = 'Prasyarat belum terpenuhi: '.$unmet->map(fn (array $item) => "{$item['code']} {$item['name']}")->join(', ').'.';
                }

                return [
                    'course' => [
                        'id' => $course->id,
                        'code' => $course->code,
                        'name' => $course->name,
                        'credits' => $course->credits,
                        'semester_level' => $course->semester_level,
                    ],
                    'prerequisites' => $prerequisites,
                    'best_grade' => $bestGrade?->value,
                    'is_retake' => $bestGrade !== null && ! $bestGrade->isPassing(),
                    'is_selected' => $selected !== null,
                    'selected_class_section_id' => $selected?->class_section_id,
                    'blockers' => $blockers,
                    'classes' => $courseClasses->map(function (ClassSection $classSection) use ($planItems, $course): array {
                        $seatsTaken = (int) $classSection->getAttribute('seats_taken');
                        $isSelected = $planItems->contains(fn (KrsItem $item) => $item->class_section_id === $classSection->id);
                        $otherItems = $planItems->reject(fn (KrsItem $item) => $item->classSection->course_id === $course->id);

                        return [
                            ...PortalFormatter::classSection($classSection),
                            'capacity' => $classSection->capacity,
                            'seats_taken' => $seatsTaken,
                            'seats_left' => max(0, $classSection->capacity - $seatsTaken),
                            'is_full' => ! $isSelected && $seatsTaken >= $classSection->capacity,
                            'is_selected' => $isSelected,
                            'conflicts' => $this->scheduleConflicts($classSection, $otherItems)
                                ->map(fn (array $conflict) => "{$conflict['course']} ({$conflict['schedule']})")
                                ->values()
                                ->all(),
                        ];
                    })->sortBy('class_code')->values()->all(),
                ];
            })
            ->sortBy(fn (array $course) => sprintf('%02d|%s', $course['course']['semester_level'], $course['course']['code']))
            ->values()
            ->all();

        return [
            'term' => PortalFormatter::term($term, $term->isKrsOpen($this->clock->now($student->university_id))),
            'courses' => $courses,
            'total_credits' => $this->sumCredits($planItems),
            'max_credits' => $this->records->maxSksForTerm($student, $term),
        ];
    }

    public function addItem(Student $student, string $classSectionId): KrsItem
    {
        return DB::transaction(function () use ($student, $classSectionId): KrsItem {
            $this->lockStudent($student);
            $term = $this->requireCurrentTerm();
            $this->assertCanEdit($student, $term);
            $submission = $this->editableSubmission($student, $term);

            /** @var ClassSection|null $classSection */
            $classSection = ClassSection::query()
                ->with(['course.prerequisites.prerequisite', 'schedules'])
                ->lockForUpdate()
                ->find($classSectionId);

            if ($classSection === null
                || $classSection->academic_term_id !== $term->id
                || $classSection->study_program_id !== $student->study_program_id
                || ! $classSection->is_active
                || ! $classSection->course->is_active) {
                throw new ConflictException('Kelas ini tidak tersedia untuk KRS Anda pada semester ini.');
            }

            $course = $classSection->course;
            $planItems = $this->planItems($student, $term);

            $sameCourse = $planItems->first(fn (KrsItem $item) => $item->classSection->course_id === $course->id);

            if ($sameCourse !== null) {
                throw new ConflictException($sameCourse->class_section_id === $classSection->id
                    ? "Kelas {$course->name} {$classSection->class_code} sudah ada di KRS Anda."
                    : "Mata kuliah {$course->name} sudah ada di KRS Anda (kelas {$sameCourse->classSection->class_code}). Hapus kelas tersebut terlebih dahulu jika ingin pindah kelas.");
            }

            $bestGrades = $this->bestGradesByCourse($student);
            $bestGrade = $bestGrades[$course->id] ?? null;

            if ($bestGrade?->isPassing()) {
                throw new ConflictException("Anda sudah lulus mata kuliah {$course->name} dengan nilai {$bestGrade->value}, sehingga tidak dapat mengambilnya kembali.");
            }

            $unmet = $course->prerequisites->reject(fn (CoursePrerequisite $prerequisite) => $this->prerequisiteSatisfied($prerequisite, $bestGrades));

            if ($unmet->isNotEmpty()) {
                throw new ConflictException('Anda tidak dapat mengambil mata kuliah ini karena prasyarat belum terpenuhi: '
                    .$unmet->map(fn (CoursePrerequisite $prerequisite) => sprintf(
                        '%s %s (minimal %s)',
                        $prerequisite->prerequisite?->code,
                        $prerequisite->prerequisite?->name,
                        ($prerequisite->min_letter_grade ?? LetterGrade::PASSING)->value,
                    ))->join(', ').'.');
            }

            if ($this->records->seatsTaken($classSection) >= $classSection->capacity) {
                throw new ConflictException("Kelas {$course->name} {$classSection->class_code} sudah penuh. Silakan pilih kelas lain.");
            }

            $currentCredits = $this->sumCredits($planItems);
            $maxCredits = $this->records->maxSksForTerm($student, $term);

            if ($currentCredits + $course->credits > $maxCredits) {
                throw new ConflictException("SKS yang dipilih melebihi batas maksimum semester ini ({$maxCredits} SKS). Saat ini Anda mengambil {$currentCredits} SKS, sedangkan mata kuliah ini {$course->credits} SKS.");
            }

            $conflict = $this->scheduleConflicts($classSection, $planItems)->first();

            if ($conflict !== null) {
                throw new ConflictException("Jadwal bentrok dengan mata kuliah {$conflict['course']} ({$conflict['schedule']}).");
            }

            // Unique (student_id, class_section_id): baris lama yang pernah
            // dibatalkan dipakai ulang, bukan baris baru.
            $item = KrsItem::query()
                ->where('student_id', $student->id)
                ->where('class_section_id', $classSection->id)
                ->first();

            $attributes = [
                'academic_term_id' => $term->id,
                'krs_submission_id' => $submission->id,
                'status' => KrsItemStatus::Draft,
            ];

            if ($item !== null) {
                $item->update($attributes);
            } else {
                $item = KrsItem::query()->create([
                    'university_id' => $student->university_id,
                    'student_id' => $student->id,
                    'class_section_id' => $classSection->id,
                    ...$attributes,
                ]);
            }

            $this->refreshDraft($submission);

            return $item;
        });
    }

    public function removeItem(Student $student, KrsItem $item): void
    {
        DB::transaction(function () use ($student, $item): void {
            $this->lockStudent($student);
            $term = $this->requireCurrentTerm();

            if ($item->student_id !== $student->id || $item->academic_term_id !== $term->id) {
                throw new ConflictException('Hanya mata kuliah di KRS semester aktif Anda yang dapat dihapus.');
            }

            $this->assertCanEdit($student, $term);
            $submission = $this->editableSubmission($student, $term);

            if ($item->status !== KrsItemStatus::Draft) {
                throw new ConflictException('Mata kuliah ini sudah diajukan atau disetujui sehingga tidak dapat dihapus.');
            }

            $item->delete();
            $this->refreshDraft($submission);
        });
    }

    public function submit(Student $student): KrsSubmission
    {
        $submission = DB::transaction(function () use ($student): KrsSubmission {
            $this->lockStudent($student);
            $term = $this->requireCurrentTerm();
            $this->assertCanEdit($student, $term);
            $submission = $this->editableSubmission($student, $term);
            $items = $this->planItems($student, $term);
            $drafts = $items->filter(fn (KrsItem $item) => $item->status === KrsItemStatus::Draft);

            if ($drafts->isEmpty()) {
                throw new ConflictException('Belum ada mata kuliah di KRS Anda. Tambahkan mata kuliah terlebih dahulu.');
            }

            $credits = $this->sumCredits($items);
            $maxCredits = $this->records->maxSksForTerm($student, $term);

            if ($credits > $maxCredits) {
                throw new ConflictException("Total SKS ({$credits}) melebihi batas maksimum semester ini ({$maxCredits} SKS). Kurangi mata kuliah sebelum mengajukan.");
            }

            // Pemeriksaan ulang bentrok di seluruh rencana (defensif — setiap
            // penambahan sudah dicek, tapi jadwal kelas bisa berubah setelahnya).
            foreach ($items as $index => $item) {
                $conflict = $this->scheduleConflicts($item->classSection, $items->slice($index + 1))->first();

                if ($conflict !== null) {
                    throw new ConflictException("Jadwal {$item->classSection->course->name} bentrok dengan {$conflict['course']} ({$conflict['schedule']}). Perbaiki KRS sebelum mengajukan.");
                }
            }

            KrsItem::query()->whereKey($drafts->modelKeys())->update(['status' => KrsItemStatus::Pending]);

            $submission->update([
                'status' => KrsSubmissionStatus::Submitted,
                'total_credits' => $credits,
                'max_credits' => $maxCredits,
                'submitted_at' => now(),
                'decided_at' => null,
                'decided_by' => null,
                'decision_note' => null,
            ]);

            return $submission;
        });

        $submission->load('academicTerm');
        $this->notifyReviewers($student, $submission);
        $this->notifications->notifyStudent($student, 'student.krs_submitted', [
            'term' => $submission->academicTerm->label(),
            'credits' => (string) $submission->total_credits,
        ], '/portal/krs');

        return $submission;
    }

    /**
     * Menarik kembali pengajuan yang belum diputuskan (selama periode KRS
     * masih dibuka) supaya mahasiswa bisa mengubah KRS-nya lagi.
     */
    public function cancelSubmission(Student $student): KrsSubmission
    {
        return DB::transaction(function () use ($student): KrsSubmission {
            $this->lockStudent($student);
            $term = $this->requireCurrentTerm();
            $submission = $this->submissionFor($student, $term);

            if ($submission?->status !== KrsSubmissionStatus::Submitted) {
                throw new ConflictException('Tidak ada pengajuan KRS yang sedang menunggu persetujuan.');
            }

            if (! $term->isKrsOpen($this->clock->now($student->university_id))) {
                throw new ConflictException('Periode KRS sudah ditutup sehingga pengajuan tidak dapat ditarik kembali.');
            }

            $submission->items()->where('status', KrsItemStatus::Pending)->update(['status' => KrsItemStatus::Draft]);
            $submission->update(['status' => KrsSubmissionStatus::Draft, 'submitted_at' => null]);

            return $submission->refresh();
        });
    }

    /**
     * Riwayat KRS per semester (terbaru dulu) — termasuk semester yang
     * KRS-nya ditetapkan langsung oleh Bagian Akademik (tanpa pengajuan).
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(Student $student): array
    {
        $items = KrsItem::query()
            ->where('student_id', $student->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->with(['academicTerm', 'classSection.course', 'grade'])
            ->get()
            ->groupBy('academic_term_id');

        $submissions = KrsSubmission::query()
            ->where('student_id', $student->id)
            ->with(['academicTerm', 'decider'])
            ->get()
            ->keyBy('academic_term_id');

        $termIds = $items->keys()->merge($submissions->keys())->unique();

        return $termIds
            ->map(function (string $termId) use ($items, $submissions): array {
                $termItems = $items->get($termId, new EloquentCollection);
                $submission = $submissions->get($termId);
                $term = $submission?->academicTerm ?? $termItems->first()?->academicTerm;
                $status = $this->effectiveStatus($submission, $termItems);

                return [
                    'term' => PortalFormatter::term($term),
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'total_credits' => $this->sumCredits($termItems),
                    'course_count' => $termItems->count(),
                    'submission' => $submission ? $this->formatSubmission($submission) : null,
                    'items' => $termItems->map(fn (KrsItem $item): array => [
                        'id' => $item->id,
                        'status' => $item->status->value,
                        'status_label' => $item->status->label(),
                        'course_code' => $item->classSection->course->code,
                        'course_name' => $item->classSection->course->name,
                        'credits' => $item->classSection->course->credits,
                        'class_code' => $item->classSection->class_code,
                        'letter_grade' => $item->grade?->letter_grade?->value,
                    ])->values()->all(),
                ];
            })
            ->sortByDesc(fn (array $row) => $row['term']['start_date'] ?? '')
            ->values()
            ->all();
    }

    /**
     * Antrean persetujuan KRS untuk penyetuju: Bagian Akademik (krs.approve)
     * melihat semua mahasiswa di universitasnya; dosen wali hanya
     * mahasiswa perwaliannya sendiri.
     *
     * @param  array{status?: string|null, academic_term_id?: string|null, search?: string|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<int, KrsSubmission>
     */
    public function approvalQueue(User $actor, array $filters): LengthAwarePaginator
    {
        $query = KrsSubmission::query()
            ->with(['student.studyProgram', 'student.academicAdvisor', 'academicTerm', 'decider', 'items.classSection.course', 'items.classSection.lecturer', 'items.classSection.schedules'])
            ->orderByRaw('submitted_at IS NULL')
            ->orderBy('submitted_at');

        if (! $actor->hasPermissionTo('krs.approve')) {
            $lecturer = $this->access->lecturerFor($actor);
            $query->whereHas('student', fn (Builder $students) => $students->where('academic_advisor_id', $lecturer?->id ?? ''));
        }

        $status = $filters['status'] ?? KrsSubmissionStatus::Submitted->value;

        if ($status !== 'all' && KrsSubmissionStatus::tryFrom((string) $status) !== null) {
            $query->where('status', $status);
        }

        if (! empty($filters['academic_term_id'])) {
            $query->where('academic_term_id', $filters['academic_term_id']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.trim((string) $filters['search']).'%';
            $query->whereHas('student', fn (Builder $students) => $students->where(fn (Builder $inner) => $inner
                ->where('name', 'like', $term)
                ->orWhere('nim', 'like', $term)));
        }

        return $query->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function approve(KrsSubmission $submission, User $actor, ?string $note = null): KrsSubmission
    {
        $submission = DB::transaction(function () use ($submission, $actor, $note): KrsSubmission {
            /** @var KrsSubmission $locked */
            $locked = KrsSubmission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($locked->status !== KrsSubmissionStatus::Submitted) {
                throw new ConflictException('KRS ini sudah diputuskan sebelumnya atau belum diajukan.');
            }

            $locked->items()->where('status', KrsItemStatus::Pending)->update(['status' => KrsItemStatus::Enrolled]);
            $locked->update([
                'status' => KrsSubmissionStatus::Approved,
                'decided_at' => now(),
                'decided_by' => $actor->id,
                'decision_note' => $note,
            ]);

            return $locked;
        });

        $submission->load(['student', 'academicTerm']);
        $this->notifications->notifyStudent($submission->student, 'student.krs_approved', [
            'term' => $submission->academicTerm->label(),
        ], '/portal/jadwal-kuliah');

        return $submission;
    }

    public function reject(KrsSubmission $submission, User $actor, string $note): KrsSubmission
    {
        $submission = DB::transaction(function () use ($submission, $actor, $note): KrsSubmission {
            /** @var KrsSubmission $locked */
            $locked = KrsSubmission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($locked->status !== KrsSubmissionStatus::Submitted) {
                throw new ConflictException('KRS ini sudah diputuskan sebelumnya atau belum diajukan.');
            }

            // Kembali ke draft (kursi tetap dipegang) supaya mahasiswa bisa
            // merevisi lalu mengajukan ulang selama periode KRS.
            $locked->items()->where('status', KrsItemStatus::Pending)->update(['status' => KrsItemStatus::Draft]);
            $locked->update([
                'status' => KrsSubmissionStatus::Rejected,
                'decided_at' => now(),
                'decided_by' => $actor->id,
                'decision_note' => $note,
            ]);

            return $locked;
        });

        $submission->load(['student', 'academicTerm']);
        $this->notifications->notifyStudent($submission->student, 'student.krs_rejected', [
            'term' => $submission->academicTerm->label(),
            'note' => $note,
        ], '/portal/krs');

        return $submission;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSubmission(KrsSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'status' => $submission->status->value,
            'status_label' => $submission->status->label(),
            'total_credits' => $submission->total_credits,
            'max_credits' => $submission->max_credits,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'decided_at' => $submission->decided_at?->toIso8601String(),
            'decided_by' => $submission->decider?->name,
            'decision_note' => $submission->decision_note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatItem(KrsItem $item): array
    {
        return [
            'id' => $item->id,
            'status' => $item->status->value,
            'status_label' => $item->status->label(),
            'class_section' => PortalFormatter::classSection($item->classSection),
        ];
    }

    private function submissionFor(Student $student, AcademicTerm $term): ?KrsSubmission
    {
        return KrsSubmission::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->with('decider')
            ->first();
    }

    /**
     * @return EloquentCollection<int, KrsItem>
     */
    private function planItems(Student $student, AcademicTerm $term): EloquentCollection
    {
        return KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->with(['classSection.course', 'classSection.lecturer', 'classSection.schedules'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, KrsItem>  $items
     */
    private function effectiveStatus(?KrsSubmission $submission, EloquentCollection $items): string
    {
        if ($submission !== null) {
            return $submission->status->value;
        }

        // KRS yang ditetapkan langsung Bagian Akademik (tanpa pengajuan).
        if ($items->contains(fn (KrsItem $item) => $item->status === KrsItemStatus::Enrolled)) {
            return KrsSubmissionStatus::Approved->value;
        }

        return 'not_started';
    }

    private function statusLabel(string $status): string
    {
        return KrsSubmissionStatus::tryFrom($status)?->label() ?? match ($status) {
            'not_started' => 'Belum Diisi',
            default => 'Belum Ada Semester Aktif',
        };
    }

    /**
     * Alasan KRS tidak bisa diubah saat ini (kosong = boleh diubah).
     *
     * @return array<int, string>
     */
    private function editBlockers(Student $student, AcademicTerm $term, string $status, CarbonImmutable $now): array
    {
        $notices = [];

        if ($student->status !== StudentStatus::Active) {
            $notices[] = "Status akademik Anda saat ini {$student->status->label()}. Pengisian KRS hanya untuk mahasiswa berstatus Aktif.";
        }

        if (! $term->isKrsOpen($now)) {
            $notices[] = $this->periodMessage($term, $now);
        }

        if ($status === KrsSubmissionStatus::Submitted->value) {
            $notices[] = 'KRS sudah diajukan dan sedang menunggu persetujuan dosen wali.';
        }

        if ($status === KrsSubmissionStatus::Approved->value) {
            $notices[] = 'KRS semester ini sudah disetujui dan dikunci. Hubungi Bagian Akademik untuk perubahan KRS.';
        }

        return $notices;
    }

    private function assertCanEdit(Student $student, AcademicTerm $term): void
    {
        $now = $this->clock->now($student->university_id);

        if ($student->status !== StudentStatus::Active) {
            throw new ConflictException("Status akademik Anda saat ini {$student->status->label()}. Pengisian KRS hanya untuk mahasiswa berstatus Aktif.");
        }

        if (! $term->isKrsOpen($now)) {
            throw new ConflictException($this->periodMessage($term, $now));
        }
    }

    private function periodMessage(AcademicTerm $term, CarbonImmutable $now): string
    {
        if ($term->krs_start_date === null || $term->krs_end_date === null) {
            return 'Periode KRS semester ini belum dibuka oleh Bagian Akademik.';
        }

        if ($now->startOfDay()->lessThan($term->krs_start_date->startOfDay())) {
            return "Periode KRS belum dibuka. KRS dapat diisi mulai {$this->date($term->krs_start_date)} sampai {$this->date($term->krs_end_date)}.";
        }

        return "Periode KRS sudah ditutup pada {$this->date($term->krs_end_date)}.";
    }

    /**
     * Pengajuan semester ini yang masih boleh diubah — dibuat (draft) kalau
     * belum ada.
     */
    private function editableSubmission(Student $student, AcademicTerm $term): KrsSubmission
    {
        $submission = KrsSubmission::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->lockForUpdate()
            ->first();

        if ($submission === null) {
            $hasEnrolled = KrsItem::query()
                ->where('student_id', $student->id)
                ->where('academic_term_id', $term->id)
                ->where('status', KrsItemStatus::Enrolled)
                ->exists();

            if ($hasEnrolled) {
                throw new ConflictException('KRS semester ini sudah ditetapkan Bagian Akademik dan tidak dapat diubah lewat portal.');
            }

            return KrsSubmission::query()->create([
                'university_id' => $student->university_id,
                'student_id' => $student->id,
                'academic_term_id' => $term->id,
                'status' => KrsSubmissionStatus::Draft,
            ]);
        }

        if ($submission->status === KrsSubmissionStatus::Submitted) {
            throw new ConflictException('KRS sudah diajukan dan sedang menunggu persetujuan dosen wali. Tarik kembali pengajuan terlebih dahulu untuk mengubah KRS.');
        }

        if ($submission->status === KrsSubmissionStatus::Approved) {
            throw new ConflictException('KRS semester ini sudah disetujui dan dikunci. Hubungi Bagian Akademik untuk perubahan KRS.');
        }

        return $submission;
    }

    /** Setiap perubahan isi KRS (termasuk setelah ditolak) mengembalikannya ke draft. */
    private function refreshDraft(KrsSubmission $submission): void
    {
        $credits = (int) KrsItem::query()
            ->where('krs_submission_id', $submission->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->with('classSection.course')
            ->get()
            ->sum(fn (KrsItem $item) => $item->classSection->course->credits);

        $submission->update(['status' => KrsSubmissionStatus::Draft, 'total_credits' => $credits]);
    }

    private function requireCurrentTerm(): AcademicTerm
    {
        return $this->academics->currentTerm()
            ?? throw new ConflictException('Belum ada semester aktif. Hubungi Bagian Akademik.');
    }

    /** Menyerialkan seluruh perubahan KRS milik satu mahasiswa (anti klik ganda / request paralel). */
    private function lockStudent(Student $student): void
    {
        Student::query()->whereKey($student->id)->lockForUpdate()->first();
    }

    /**
     * Nilai terbaik per mata kuliah dari seluruh riwayat (aturan pengulangan).
     *
     * @return array<string, LetterGrade>
     */
    private function bestGradesByCourse(Student $student): array
    {
        return $this->records->bestAttempts($this->records->gradedRecords($student))
            ->mapWithKeys(fn (Grade $grade) => [$grade->krsItem->classSection->course_id => $grade->letter_grade])
            ->all();
    }

    /**
     * @param  array<string, LetterGrade>  $bestGrades
     */
    private function prerequisiteSatisfied(CoursePrerequisite $prerequisite, array $bestGrades): bool
    {
        $grade = $bestGrades[$prerequisite->prerequisite_course_id] ?? null;

        return $grade !== null && $grade->meets($prerequisite->min_letter_grade ?? LetterGrade::PASSING);
    }

    /**
     * Jadwal `$classSection` yang beririsan dengan jadwal kelas-kelas lain di `$items`.
     *
     * @param  Collection<int, KrsItem>  $items
     * @return Collection<int, array{course: string, schedule: string}>
     */
    private function scheduleConflicts(ClassSection $classSection, Collection $items): Collection
    {
        $conflicts = collect();

        foreach ($classSection->schedules as $schedule) {
            foreach ($items as $item) {
                if ($item->class_section_id === $classSection->id) {
                    continue;
                }

                $item->classSection->schedules
                    ->filter(fn (ClassSchedule $other) => $schedule->overlaps($other))
                    ->each(fn (ClassSchedule $other) => $conflicts->push([
                        'course' => $item->classSection->course->name,
                        'schedule' => PortalFormatter::scheduleText($other),
                    ]));
            }
        }

        return $conflicts;
    }

    /**
     * @param  Collection<int, KrsItem>  $items
     */
    private function sumCredits(Collection $items): int
    {
        return (int) $items->sum(fn (KrsItem $item) => $item->classSection->course->credits);
    }

    private function notifyReviewers(Student $student, KrsSubmission $submission): void
    {
        $placeholders = [
            'student' => $student->name,
            'nim' => $student->nim,
            'term' => $submission->academicTerm->label(),
        ];

        $advisor = $student->academicAdvisor;
        $advisorUser = $advisor === null ? null : $this->identity->accountOf($advisor);

        if ($advisorUser !== null && $advisorUser->is_active) {
            $this->notifications->notifyUser($advisorUser, 'student.krs_review_requested', $placeholders, '/akademik/persetujuan-krs');

            return;
        }

        // Belum ada dosen wali dengan akun — Bagian Akademik yang memutuskan.
        $this->notifications->notifyRole('academic_administrator', 'student.krs_review_requested', $placeholders, '/akademik/persetujuan-krs');
    }

    private function date(CarbonInterface $date): string
    {
        return $date->locale('id')->translatedFormat('j F Y');
    }
}

<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\CourseMaterialType;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\AssignmentSubmission;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\CourseMaterial;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Support\AcademicClock;
use Modules\Academic\Support\ClassSectionAccess;
use Modules\Academic\Support\PortalFormatter;
use Modules\FileManagement\Support\FileAttacher;

/**
 * Perkuliahan (RANCANGAN-APLIKASI.md §4.11-§4.12): materi per pertemuan,
 * tugas, dan pengumpulan tugas — dari dua sisi:
 *
 * - Mahasiswa: hanya kelas yang KRS-nya Enrolled, hanya materi/tugas yang
 *   dipublikasikan; pengumpulan diikat ke KrsItem miliknya.
 * - Dosen pengampu / Bagian Akademik: kelola materi & tugas, lihat dan nilai
 *   pengumpulan (otorisasi kelas lewat ClassSectionAccess di policy).
 *
 * Berkas selalu lewat Modul FileManagement (POST /file-uploads lalu
 * ditautkan dengan FileAttacher) — tidak ada penyimpanan berkas kedua.
 */
class LearningService
{
    /**
     * Ekstensi yang boleh dipilih dosen untuk pembatasan format tugas — sama
     * dengan format yang diterima POST /file-uploads (StoreFileUploadRequest),
     * supaya tidak ada format "boleh" yang ternyata ditolak saat diunggah.
     */
    public const SELECTABLE_EXTENSIONS = Assignment::DEFAULT_EXTENSIONS;

    public function __construct(
        private readonly StudentAcademicService $academics,
        private readonly StudentAttendanceService $attendance,
        private readonly StudentNotificationService $notifications,
        private readonly ClassSectionAccess $access,
        private readonly FileAttacher $files,
        private readonly AcademicClock $clock,
    ) {}

    // ---------------------------------------------------------------
    // Sisi mahasiswa
    // ---------------------------------------------------------------

    /**
     * Mata kuliah yang sedang diikuti (KRS Enrolled semester aktif).
     *
     * @return array<string, mixed>
     */
    public function studentCourses(Student $student): array
    {
        $term = $this->academics->currentTerm();

        if ($term === null) {
            return ['term' => null, 'courses' => []];
        }

        $items = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with(['classSection.course', 'classSection.lecturer', 'classSection.schedules', 'attendances'])
            ->get();

        $classIds = $items->pluck('class_section_id');

        $materialCounts = CourseMaterial::query()->whereIn('class_section_id', $classIds)->where('is_published', true)
            ->selectRaw('class_section_id, count(*) as total')->groupBy('class_section_id')->pluck('total', 'class_section_id');

        $assignments = Assignment::query()->whereIn('class_section_id', $classIds)->where('is_published', true)
            ->get(['id', 'class_section_id', 'due_at']);

        $submittedIds = AssignmentSubmission::query()->whereIn('krs_item_id', $items->modelKeys())->pluck('assignment_id')->all();

        return [
            'term' => PortalFormatter::term($term),
            'courses' => $items->map(fn (KrsItem $item): array => [
                ...PortalFormatter::classSection($item->classSection),
                'krs_item_id' => $item->id,
                'materials_count' => (int) ($materialCounts[$item->class_section_id] ?? 0),
                'assignments_count' => $assignments->where('class_section_id', $item->class_section_id)->count(),
                'pending_assignments_count' => $assignments
                    ->where('class_section_id', $item->class_section_id)
                    ->filter(fn (Assignment $assignment) => ! in_array($assignment->id, $submittedIds, true) && $assignment->due_at->isFuture())
                    ->count(),
                'attendance' => $this->attendance->totals($item->attendances),
            ])->sortBy('course.name')->values()->all(),
        ];
    }

    /**
     * Seluruh materi yang dipublikasikan di kelas-kelas yang diikuti
     * semester aktif, dikelompokkan per mata kuliah lalu per pertemuan.
     *
     * @return array<string, mixed>
     */
    public function studentMaterials(Student $student): array
    {
        $term = $this->academics->currentTerm();

        if ($term === null) {
            return ['term' => null, 'courses' => []];
        }

        $items = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $term->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with(['classSection.course', 'classSection.lecturer', 'classSection.schedules'])
            ->get()
            ->keyBy('class_section_id');

        $materials = CourseMaterial::query()
            ->whereIn('class_section_id', $items->keys())
            ->where('is_published', true)
            ->with('file')
            ->orderByRaw('meeting_number IS NULL')
            ->orderBy('meeting_number')
            ->orderBy('created_at')
            ->get()
            ->groupBy('class_section_id');

        return [
            'term' => PortalFormatter::term($term),
            'courses' => $items->map(fn (KrsItem $item): array => [
                ...PortalFormatter::classSection($item->classSection),
                'materials' => $materials->get($item->class_section_id, collect())
                    ->map(fn (CourseMaterial $material) => $this->formatMaterial($material))
                    ->values()
                    ->all(),
            ])->sortBy('course.name')->values()->all(),
        ];
    }

    /**
     * Detail satu kelas yang diikuti: info kelas, materi per pertemuan,
     * tugas beserta status pengumpulan, ujian, dan rekap presensi.
     *
     * @return array<string, mixed>
     */
    public function studentCourseDetail(Student $student, ClassSection $classSection): array
    {
        $item = $this->enrolledItem($student, $classSection);
        $classSection->loadMissing(['course', 'lecturer', 'schedules', 'academicTerm']);

        $materials = CourseMaterial::query()
            ->where('class_section_id', $classSection->id)
            ->where('is_published', true)
            ->with('file')
            ->orderByRaw('meeting_number IS NULL')
            ->orderBy('meeting_number')
            ->orderBy('created_at')
            ->get();

        $assignments = Assignment::query()
            ->where('class_section_id', $classSection->id)
            ->where('is_published', true)
            ->with(['submissions' => fn ($query) => $query->where('krs_item_id', $item->id)->with('file')])
            ->orderBy('due_at')
            ->get();

        $exams = Exam::query()
            ->where('class_section_id', $classSection->id)
            ->where('is_published', true)
            ->orderBy('starts_at')
            ->get(['id', 'title', 'starts_at', 'ends_at', 'duration_minutes']);

        return [
            ...PortalFormatter::classSection($classSection),
            'krs_item_id' => $item->id,
            'term' => PortalFormatter::term($classSection->academicTerm),
            'meetings' => $materials
                ->groupBy(fn (CourseMaterial $material) => $material->meeting_number ?? 0)
                ->map(fn ($group, $meeting): array => [
                    'meeting_number' => $meeting === 0 ? null : (int) $meeting,
                    'label' => $meeting === 0 ? 'Materi Umum' : "Pertemuan {$meeting}",
                    'materials' => $group->map(fn (CourseMaterial $material) => $this->formatMaterial($material))->values()->all(),
                ])
                ->values()
                ->all(),
            'assignments' => $assignments->map(fn (Assignment $assignment) => $this->formatAssignmentForStudent($assignment, $assignment->submissions->first()))->all(),
            'exams' => $exams->map(fn (Exam $exam): array => [
                'id' => $exam->id,
                'title' => $exam->title,
                'starts_at' => $exam->starts_at?->toIso8601String(),
                'ends_at' => $exam->ends_at?->toIso8601String(),
                'duration_minutes' => $exam->duration_minutes,
            ])->all(),
            'attendance' => $this->attendance->totals($item->attendances()->get()),
        ];
    }

    /**
     * Seluruh tugas dari kelas yang diikuti (semester aktif), dengan status
     * per mahasiswa. Filter: not_submitted|submitted|late|graded|missed.
     *
     * @return array<string, mixed>
     */
    public function studentAssignments(Student $student, ?string $status = null): array
    {
        $term = $this->academics->currentTerm();

        $items = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->when($term !== null, fn (Builder $query) => $query->where('academic_term_id', $term->id))
            ->with('classSection.course')
            ->get()
            ->keyBy('class_section_id');

        $assignments = Assignment::query()
            ->whereIn('class_section_id', $items->keys())
            ->where('is_published', true)
            ->with(['classSection.course', 'classSection.lecturer', 'submissions' => fn ($query) => $query->whereIn('krs_item_id', $items->pluck('id'))->with('file')])
            ->orderBy('due_at')
            ->get();

        $rows = $assignments->map(fn (Assignment $assignment) => $this->formatAssignmentForStudent($assignment, $assignment->submissions->first()));

        return [
            'term' => PortalFormatter::term($term),
            'assignments' => $rows
                ->when($status !== null && $status !== 'all', fn ($collection) => $collection->where('status', $status))
                ->values()
                ->all(),
            'counts' => [
                'all' => $rows->count(),
                ...collect(['not_submitted', 'submitted', 'late', 'graded', 'missed'])
                    ->mapWithKeys(fn (string $key) => [$key => $rows->where('status', $key)->count()])
                    ->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentAssignmentDetail(Student $student, Assignment $assignment): array
    {
        $item = $this->enrolledItem($student, $assignment->classSection);
        $assignment->loadMissing(['classSection.course', 'classSection.lecturer', 'attachment']);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('krs_item_id', $item->id)
            ->with(['file', 'grader'])
            ->first();

        return $this->formatAssignmentForStudent($assignment, $submission);
    }

    /**
     * @param  array<string, mixed>  $data  file_upload_id, notes
     */
    public function submit(Student $student, Assignment $assignment, array $data, User $actor): AssignmentSubmission
    {
        $item = $this->enrolledItem($student, $assignment->classSection);

        if (! $assignment->is_published) {
            throw new ConflictException('Tugas ini belum dipublikasikan.');
        }

        $fileId = $data['file_upload_id'] ?? null;
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

        if (($fileId === null || $fileId === '') && ($notes === null || $notes === '')) {
            throw ValidationException::withMessages(['file_upload_id' => 'Unggah berkas tugas atau tuliskan jawaban.']);
        }

        return DB::transaction(function () use ($assignment, $item, $fileId, $notes, $actor): AssignmentSubmission {
            /** @var AssignmentSubmission|null $existing */
            $existing = AssignmentSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->where('krs_item_id', $item->id)
                ->lockForUpdate()
                ->first();

            $isLate = now()->greaterThan($assignment->due_at);

            if ($isLate && ! $assignment->allow_late_submission) {
                throw new ConflictException('Batas waktu pengumpulan tugas sudah lewat.');
            }

            if ($existing !== null) {
                if ($existing->graded_at !== null) {
                    throw new ConflictException('Tugas sudah dinilai sehingga tidak dapat dikumpulkan ulang.');
                }

                if (! $assignment->allow_resubmission) {
                    throw new ConflictException('Tugas ini tidak mengizinkan pengumpulan ulang.');
                }
            }

            $file = $this->files->resolve(
                $fileId,
                'file_upload_id',
                $actor,
                $existing,
                $assignment->acceptedExtensions(),
                $assignment->max_file_size_mb,
            );

            $attributes = [
                'file_upload_id' => $file?->id,
                'notes' => $notes !== '' ? $notes : null,
                'submitted_at' => now(),
                'is_late' => $isLate,
            ];

            if ($existing !== null) {
                $existing->update([...$attributes, 'submission_count' => $existing->submission_count + 1]);
                $submission = $existing;
            } else {
                $submission = AssignmentSubmission::query()->create([
                    'university_id' => $assignment->university_id,
                    'assignment_id' => $assignment->id,
                    'krs_item_id' => $item->id,
                    'submission_count' => 1,
                    ...$attributes,
                ]);
            }

            $this->files->attach($file, $submission);

            return $submission->refresh();
        });
    }

    // ---------------------------------------------------------------
    // Sisi dosen pengampu / Bagian Akademik
    // ---------------------------------------------------------------

    /**
     * Kelas yang dikelola user: kelas yang ia ampu, atau — untuk Bagian
     * Akademik (classes.update) — semua kelas (wajib dipersempit filter).
     *
     * @param  array{academic_term_id?: string|null, search?: string|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<int, ClassSection>
     */
    public function teachingClasses(User $user, array $filters): LengthAwarePaginator
    {
        $termId = $filters['academic_term_id'] ?? AcademicTerm::query()->where('is_current', true)->value('id');

        $query = ClassSection::query()
            ->with(['course', 'lecturer', 'schedules', 'academicTerm'])
            ->withCount([
                'krsItems as enrolled_count' => fn (Builder $query) => $query->where('status', KrsItemStatus::Enrolled),
                'materials',
                'assignments',
            ])
            ->when($termId !== null, fn (Builder $query) => $query->where('academic_term_id', $termId))
            ->orderBy('class_code');

        if (! $user->hasPermissionTo('classes.update')) {
            $query->where('lecturer_id', $this->access->lecturerFor($user)?->id ?? '');
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim((string) $filters['search']).'%';
            $query->where(fn (Builder $inner) => $inner
                ->where('class_code', 'like', $search)
                ->orWhereHas('course', fn (Builder $course) => $course->where('name', 'like', $search)->orWhere('code', 'like', $search)));
        }

        return $query->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    /**
     * @return EloquentCollection<int, CourseMaterial>
     */
    public function materialsForManager(ClassSection $classSection): EloquentCollection
    {
        return CourseMaterial::query()
            ->where('class_section_id', $classSection->id)
            ->with('file')
            ->orderByRaw('meeting_number IS NULL')
            ->orderBy('meeting_number')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpsertCourseMaterialRequest.
     */
    public function saveMaterial(ClassSection $classSection, array $data, User $actor, ?CourseMaterial $material = null): CourseMaterial
    {
        $type = CourseMaterialType::from((string) ($data['type'] ?? $material?->type->value));
        $file = null;

        if ($type === CourseMaterialType::File) {
            $fileId = $data['file_upload_id'] ?? $material?->file_upload_id;

            if ($fileId === null) {
                throw ValidationException::withMessages(['file_upload_id' => 'Unggah berkas materi.']);
            }

            $file = $this->files->resolve($fileId, 'file_upload_id', $actor, $material);
        } elseif (in_array($type, [CourseMaterialType::Link, CourseMaterialType::Video], true) && empty($data['url'] ?? $material?->url)) {
            throw ValidationException::withMessages(['url' => 'Isi tautan materi.']);
        } elseif ($type === CourseMaterialType::Text && empty($data['description'] ?? $material?->description)) {
            throw ValidationException::withMessages(['description' => 'Isi teks materi.']);
        }

        return DB::transaction(function () use ($classSection, $data, $actor, $material, $type, $file): CourseMaterial {
            $attributes = [
                ...Arr::only($data, ['meeting_number', 'title', 'description', 'url', 'is_published']),
                'type' => $type,
                'file_upload_id' => $type === CourseMaterialType::File ? $file?->id : null,
                'url' => in_array($type, [CourseMaterialType::Link, CourseMaterialType::Video], true) ? ($data['url'] ?? $material?->url) : null,
            ];

            if ($material === null) {
                $material = CourseMaterial::query()->create([
                    'university_id' => $classSection->university_id,
                    'class_section_id' => $classSection->id,
                    'created_by' => $actor->id,
                    'is_published' => $data['is_published'] ?? true,
                    ...$attributes,
                ]);
            } else {
                $material->update($attributes);
            }

            if ($material->is_published && $material->published_at === null) {
                $material->update(['published_at' => now()]);
            }

            $this->files->attach($file, $material);

            return $material->load('file');
        });
    }

    /**
     * @return EloquentCollection<int, Assignment>
     */
    public function assignmentsForManager(ClassSection $classSection): EloquentCollection
    {
        return Assignment::query()
            ->where('class_section_id', $classSection->id)
            ->with('attachment')
            ->withCount(['submissions', 'submissions as graded_count' => fn (Builder $query) => $query->whereNotNull('graded_at')])
            ->orderBy('due_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpsertAssignmentRequest.
     */
    public function saveAssignment(ClassSection $classSection, array $data, User $actor, ?Assignment $assignment = null): Assignment
    {
        $attachment = array_key_exists('attachment_file_id', $data)
            ? $this->files->resolve($data['attachment_file_id'], 'attachment_file_id', $actor, $assignment)
            : $assignment?->attachment;

        $wasPublished = $assignment?->is_published ?? false;

        $saved = DB::transaction(function () use ($classSection, $data, $actor, $assignment, $attachment): Assignment {
            $attributes = [
                ...Arr::only($data, [
                    'title', 'description', 'due_at', 'allow_late_submission', 'allow_resubmission',
                    'max_file_size_mb', 'allowed_extensions', 'max_score', 'is_published',
                ]),
                'attachment_file_id' => $attachment?->id,
            ];

            if ($assignment === null) {
                $assignment = Assignment::query()->create([
                    'university_id' => $classSection->university_id,
                    'class_section_id' => $classSection->id,
                    'created_by' => $actor->id,
                    'is_published' => $data['is_published'] ?? true,
                    ...$attributes,
                ]);
            } else {
                $assignment->update($attributes);
            }

            if ($assignment->is_published && $assignment->published_at === null) {
                $assignment->update(['published_at' => now()]);
            }

            $this->files->attach($attachment, $assignment);

            return $assignment->load(['attachment', 'classSection.course']);
        });

        if ($saved->is_published && ! $wasPublished) {
            $this->notifyEnrolledStudents($saved);
        }

        return $saved;
    }

    public function deleteAssignment(Assignment $assignment): void
    {
        if ($assignment->submissions()->exists()) {
            throw new ConflictException('Tugas yang sudah memiliki pengumpulan tidak dapat dihapus. Batalkan publikasi tugas jika ingin menyembunyikannya.');
        }

        $assignment->delete();
    }

    /**
     * Seluruh peserta kelas (KRS Enrolled) beserta pengumpulannya — yang
     * belum mengumpulkan ikut tampil.
     *
     * @return array<int, array<string, mixed>>
     */
    public function submissionsForManager(Assignment $assignment): array
    {
        $submissions = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->with(['file', 'grader'])
            ->get()
            ->keyBy('krs_item_id');

        return KrsItem::query()
            ->where('class_section_id', $assignment->class_section_id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with('student')
            ->get()
            ->sortBy(fn (KrsItem $item) => $item->student->nim)
            ->map(function (KrsItem $item) use ($submissions, $assignment): array {
                /** @var AssignmentSubmission|null $submission */
                $submission = $submissions->get($item->id);

                return [
                    'krs_item_id' => $item->id,
                    'student' => ['id' => $item->student->id, 'nim' => $item->student->nim, 'name' => $item->student->name],
                    'status' => $this->submissionStatus($assignment, $submission),
                    'submission' => $submission ? $this->formatSubmission($submission) : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{score: float|int|string, feedback?: string|null}  $data
     */
    public function grade(AssignmentSubmission $submission, array $data, User $actor): AssignmentSubmission
    {
        $assignment = $submission->assignment;

        if ((float) $data['score'] > (float) $assignment->max_score) {
            throw ValidationException::withMessages(['score' => "Nilai maksimal tugas ini {$assignment->max_score}."]);
        }

        $submission->update([
            'score' => $data['score'],
            'feedback' => $data['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => $actor->id,
        ]);

        $student = $submission->krsItem->student;
        $this->notifications->notifyStudent($student, 'student.assignment_graded', [
            'title' => $assignment->title,
            'course' => $assignment->classSection->course->name,
            'score' => (string) $submission->score,
        ], "/portal/tugas/{$assignment->id}");

        return $submission->refresh()->load(['file', 'grader']);
    }

    // ---------------------------------------------------------------
    // Bentuk respons
    // ---------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function formatMaterial(CourseMaterial $material): array
    {
        return [
            'id' => $material->id,
            'meeting_number' => $material->meeting_number,
            'title' => $material->title,
            'description' => $material->description,
            'type' => $material->type->value,
            'type_label' => $material->type->label(),
            'url' => $material->url,
            'file' => $material->file ? [
                'id' => $material->file->id,
                'name' => $material->file->original_name,
                'size_bytes' => $material->file->size_bytes,
                'mime_type' => $material->file->mime_type,
            ] : null,
            'is_published' => $material->is_published,
            'published_at' => $material->published_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAssignment(Assignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'class_section_id' => $assignment->class_section_id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'due_at' => $assignment->due_at->toIso8601String(),
            'allow_late_submission' => $assignment->allow_late_submission,
            'allow_resubmission' => $assignment->allow_resubmission,
            'max_file_size_mb' => $assignment->max_file_size_mb,
            'allowed_extensions' => $assignment->acceptedExtensions(),
            'max_score' => $assignment->max_score,
            'is_published' => $assignment->is_published,
            'published_at' => $assignment->published_at?->toIso8601String(),
            'attachment' => $assignment->relationLoaded('attachment') && $assignment->attachment ? [
                'id' => $assignment->attachment->id,
                'name' => $assignment->attachment->original_name,
                'size_bytes' => $assignment->attachment->size_bytes,
            ] : null,
            'submissions_count' => $assignment->getAttribute('submissions_count'),
            'graded_count' => $assignment->getAttribute('graded_count'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSubmission(AssignmentSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'notes' => $submission->notes,
            'submitted_at' => $submission->submitted_at->toIso8601String(),
            'is_late' => $submission->is_late,
            'submission_count' => $submission->submission_count,
            'score' => $submission->score,
            'feedback' => $submission->feedback,
            'graded_at' => $submission->graded_at?->toIso8601String(),
            'graded_by' => $submission->relationLoaded('grader') ? $submission->grader?->name : null,
            'file' => $submission->relationLoaded('file') && $submission->file ? [
                'id' => $submission->file->id,
                'name' => $submission->file->original_name,
                'size_bytes' => $submission->file->size_bytes,
            ] : null,
        ];
    }

    /**
     * not_submitted | submitted | late | graded | missed
     */
    public function submissionStatus(Assignment $assignment, ?AssignmentSubmission $submission): string
    {
        return match (true) {
            $submission?->graded_at !== null => 'graded',
            $submission !== null && $submission->is_late => 'late',
            $submission !== null => 'submitted',
            $assignment->due_at->isPast() && ! $assignment->allow_late_submission => 'missed',
            default => 'not_submitted',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAssignmentForStudent(Assignment $assignment, ?AssignmentSubmission $submission): array
    {
        $status = $this->submissionStatus($assignment, $submission);
        $isPastDue = $assignment->due_at->isPast();
        $canSubmit = $submission?->graded_at === null
            && (! $isPastDue || $assignment->allow_late_submission)
            && ($submission === null || $assignment->allow_resubmission);

        return [
            ...$this->formatAssignment($assignment),
            'course' => $assignment->relationLoaded('classSection') ? [
                'code' => $assignment->classSection->course->code,
                'name' => $assignment->classSection->course->name,
                'class_code' => $assignment->classSection->class_code,
                'lecturer' => $assignment->classSection->relationLoaded('lecturer') ? PortalFormatter::lecturer($assignment->classSection->lecturer) : null,
            ] : null,
            'status' => $status,
            'status_label' => match ($status) {
                'graded' => 'Dinilai',
                'late' => 'Terlambat',
                'submitted' => 'Sudah dikumpulkan',
                'missed' => 'Tidak dikumpulkan',
                default => 'Belum dikerjakan',
            },
            'is_past_due' => $isPastDue,
            'can_submit' => $canSubmit,
            'submission' => $submission ? $this->formatSubmission($submission) : null,
        ];
    }

    /** KrsItem Enrolled milik mahasiswa di kelas ini — dicek ulang di service (defense in depth). */
    private function enrolledItem(Student $student, ClassSection $classSection): KrsItem
    {
        return KrsItem::query()
            ->where('student_id', $student->id)
            ->where('class_section_id', $classSection->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->first()
            ?? throw new ConflictException('Anda tidak terdaftar pada kelas ini.');
    }

    private function notifyEnrolledStudents(Assignment $assignment): void
    {
        $students = Student::query()
            ->whereIn('id', KrsItem::query()
                ->where('class_section_id', $assignment->class_section_id)
                ->where('status', KrsItemStatus::Enrolled)
                ->select('student_id'))
            ->with('user')
            ->get();

        foreach ($students as $student) {
            $this->notifications->notifyStudent($student, 'student.assignment_published', [
                'title' => $assignment->title,
                'course' => $assignment->classSection->course->name,
                'due_at' => $assignment->due_at
                    ->setTimezone($this->clock->timezone($assignment->university_id))
                    ->locale('id')
                    ->translatedFormat('j F Y H:i'),
            ], "/portal/tugas/{$assignment->id}");
        }
    }
}

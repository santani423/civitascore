<?php

namespace Modules\Academic\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\KrsSubmissionStatus;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Services\StudentAnnouncementFeed;

/**
 * Dashboard Portal Mahasiswa dalam satu respons — disusun HANYA dari
 * service yang juga dipakai halaman masing-masing (ringkasan akademik, KRS,
 * jadwal, presensi, tugas, ujian, pengumuman), jadi angka di dashboard tidak
 * mungkin berbeda dengan halaman detailnya. Ditambah daftar "perlu
 * perhatian" (alerts) yang menjawab "apa yang harus saya lakukan?".
 */
class StudentDashboardService
{
    private const UPCOMING_LIMIT = 5;

    public function __construct(
        private readonly StudentAcademicService $academics,
        private readonly KrsPlanService $krs,
        private readonly StudentScheduleService $schedules,
        private readonly StudentAttendanceService $attendance,
        private readonly LearningService $learning,
        private readonly ExamService $exams,
        private readonly StudentAnnouncementFeed $announcements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Student $student, User $user): array
    {
        $summary = $this->academics->summary($student);
        $krs = $this->krs->overview($student);
        $schedule = $this->schedules->overview($student);
        $attendance = $this->attendance->summary($student);
        $assignments = $this->learning->studentAssignments($student)['assignments'];
        $exams = $this->upcomingExams($student);
        $recentGrades = $this->recentGrades($student);
        $unreadAnnouncements = $this->announcements->unreadCount($student, $user);
        $latestAnnouncements = $this->announcements->paginate($student, $user, 'all', null, 3);

        $dueAssignments = collect($assignments)
            ->where('status', 'not_submitted')
            ->filter(fn (array $assignment) => now()->diffInDays($assignment['due_at'], false) <= 7)
            ->sortBy('due_at')
            ->take(self::UPCOMING_LIMIT)
            ->values();

        return [
            'profile' => [
                'name' => $student->name,
                'nim' => $student->nim,
                'photo_file_id' => $student->photo_file_id,
                'study_program' => $summary['study_program']['name'],
                'faculty' => $summary['faculty']['name'] ?? null,
                'admission_year' => $summary['admission_year'],
                'semester' => $summary['semester'],
                'status' => $summary['status'],
            ],
            'academic' => [
                'ipk' => $summary['ipk'],
                'last_ips' => $summary['last_ips'],
                'total_credits' => $summary['total_credits'],
                'passed_credits' => $summary['passed_credits'],
                'current_term_credits' => $summary['current_term_credits'],
                'max_credits' => $summary['max_credits'],
                'remaining_credits' => $summary['remaining_credits'],
                'current_term' => $summary['current_term'],
                'academic_advisor' => $summary['academic_advisor'],
            ],
            'krs' => [
                'status' => $krs['status'],
                'status_label' => $krs['status_label'],
                'total_credits' => $krs['total_credits'],
                'max_credits' => $krs['max_credits'],
                'course_count' => count($krs['items']),
                'is_open' => $krs['term']['is_krs_open'] ?? false,
                'krs_end_date' => $krs['term']['krs_end_date'] ?? null,
                'decision_note' => $krs['submission']['decision_note'] ?? null,
            ],
            'today_schedule' => $schedule['today'],
            'next_class' => $schedule['next_class'],
            'attendance' => [
                'overall' => $attendance['overall'],
                'minimum_percent' => $attendance['minimum_percent'],
                'below_minimum' => collect($attendance['courses'])->where('is_below_minimum', true)->values()->all(),
            ],
            'upcoming_exams' => $exams,
            'assignments_due' => $dueAssignments->all(),
            'recent_grades' => $recentGrades,
            'announcements' => [
                'unread_count' => $unreadAnnouncements,
                'latest' => collect($latestAnnouncements->items())->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'published_at' => $announcement->published_at->toIso8601String(),
                    'is_pinned' => $announcement->is_pinned,
                    'is_read' => (bool) $announcement->getAttribute('is_read'),
                ])->all(),
            ],
            'notifications_unread' => $user->unreadNotifications()->count(),
            'alerts' => $this->alerts($student, $krs, $exams, $dueAssignments, $recentGrades, $attendance, $unreadAnnouncements),
        ];
    }

    /**
     * Ujian dari kelas yang diikuti yang belum berakhir, dengan status
     * personal yang sama persis dengan halaman Ujian (ExamService).
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcomingExams(Student $student): array
    {
        $krsItems = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->get(['id', 'class_section_id']);

        $exams = Exam::query()
            ->whereIn('class_section_id', $krsItems->pluck('class_section_id'))
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with('classSection.course')
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        $latestAttempts = ExamAttempt::query()
            ->whereIn('exam_id', $exams->modelKeys())
            ->whereIn('krs_item_id', $krsItems->modelKeys())
            ->orderByDesc('attempt_number')
            ->get()
            ->groupBy('exam_id')
            ->map(fn (Collection $attempts) => $attempts->first());

        return $exams
            ->map(function (Exam $exam) use ($latestAttempts): array {
                $status = $this->exams->computeStudentStatus($exam, $latestAttempts->get($exam->id));

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'course_name' => $exam->classSection->course->name,
                    'starts_at' => $exam->starts_at?->toIso8601String(),
                    'ends_at' => $exam->ends_at?->toIso8601String(),
                    'duration_minutes' => $exam->duration_minutes,
                    'status' => $status,
                ];
            })
            ->whereIn('status', ['upcoming', 'available', 'in_progress'])
            ->take(self::UPCOMING_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentGrades(Student $student): array
    {
        return Grade::query()
            ->whereNotNull('letter_grade')
            ->where('submitted_at', '>=', now()->subDays(14))
            ->whereHas('krsItem', fn ($query) => $query->where('student_id', $student->id)->where('status', KrsItemStatus::Enrolled))
            ->with(['krsItem.classSection.course', 'krsItem.academicTerm'])
            ->latest('submitted_at')
            ->limit(self::UPCOMING_LIMIT)
            ->get()
            ->map(fn (Grade $grade): array => [
                'course_code' => $grade->krsItem->classSection->course->code,
                'course_name' => $grade->krsItem->classSection->course->name,
                'term_label' => $grade->krsItem->academicTerm->label(),
                'letter_grade' => $grade->letter_grade->value,
                'graded_at' => $grade->submitted_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $krs
     * @param  array<int, array<string, mixed>>  $exams
     * @param  Collection<int, array<string, mixed>>  $dueAssignments
     * @param  array<int, array<string, mixed>>  $recentGrades
     * @param  array<string, mixed>  $attendance
     * @return array<int, array{type: string, severity: string, message: string, link: string|null}>
     */
    private function alerts(Student $student, array $krs, array $exams, Collection $dueAssignments, array $recentGrades, array $attendance, int $unreadAnnouncements): array
    {
        $alerts = [];

        if ($student->status !== StudentStatus::Active) {
            $alerts[] = $this->alert('status', 'warning', "Status akademik Anda saat ini {$student->status->label()}.", '/portal/akademik');
        }

        $isOpen = $krs['term']['is_krs_open'] ?? false;

        if ($isOpen && in_array($krs['status'], ['not_started', KrsSubmissionStatus::Draft->value], true)) {
            $alerts[] = $this->alert('krs', 'warning', $krs['status'] === 'not_started'
                ? 'KRS semester ini belum diambil.'
                : 'KRS Anda masih draft dan belum diajukan ke dosen wali.', '/portal/krs');
        }

        if ($krs['status'] === KrsSubmissionStatus::Submitted->value) {
            $alerts[] = $this->alert('krs', 'info', 'KRS Anda sedang menunggu persetujuan dosen wali.', '/portal/krs');
        }

        if ($krs['status'] === KrsSubmissionStatus::Rejected->value) {
            $alerts[] = $this->alert('krs', 'danger', 'KRS Anda ditolak dosen wali. Perbaiki lalu ajukan kembali.', '/portal/krs');
        }

        foreach ($exams as $exam) {
            if ($exam['status'] === 'available' || $exam['status'] === 'in_progress') {
                $alerts[] = $this->alert('exam', 'danger', "Ujian \"{$exam['title']}\" sedang dibuka.", '/portal/ujian');
            } elseif ($exam['starts_at'] !== null && now()->diffInHours($exam['starts_at'], false) <= 72) {
                $alerts[] = $this->alert('exam', 'info', "Ada jadwal ujian \"{$exam['title']}\" ({$exam['course_name']}).", '/portal/ujian');
            }
        }

        foreach ($dueAssignments as $assignment) {
            if (now()->diffInHours($assignment['due_at'], false) <= 48) {
                $alerts[] = $this->alert('assignment', 'warning', "Tugas \"{$assignment['title']}\" mendekati batas waktu.", "/portal/tugas/{$assignment['id']}");
            }
        }

        if ($recentGrades !== []) {
            $alerts[] = $this->alert('grade', 'success', 'Ada '.count($recentGrades).' nilai baru yang tersedia.', '/portal/nilai');
        }

        foreach ($attendance['courses'] as $course) {
            if ($course['is_below_minimum']) {
                $alerts[] = $this->alert('attendance', 'danger', sprintf(
                    'Kehadiran %s %s%% — di bawah batas minimum %s%%.',
                    $course['course_name'],
                    rtrim(rtrim(number_format((float) $course['percentage'], 1, ',', ''), '0'), ','),
                    rtrim(rtrim(number_format((float) $attendance['minimum_percent'], 1, ',', ''), '0'), ','),
                ), '/portal/absensi');
            }
        }

        if ($unreadAnnouncements > 0) {
            $alerts[] = $this->alert('announcement', 'info', "Ada {$unreadAnnouncements} pengumuman yang belum dibaca.", '/portal/pengumuman');
        }

        return $alerts;
    }

    /**
     * @return array{type: string, severity: string, message: string, link: string|null}
     */
    private function alert(string $type, string $severity, string $message, ?string $link): array
    {
        return compact('type', 'severity', 'message', 'link');
    }
}

<?php

namespace Modules\Announcement\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academic\Models\Student;
use Modules\Academic\Services\StudentAcademicService;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Models\AnnouncementRead;

/**
 * Feed pengumuman Portal Mahasiswa: hanya pengumuman yang sudah terbit dan
 * memang ditujukan kepada mahasiswa itu — cakupan universitas / fakultasnya /
 * program studinya, sasaran peran semua atau mahasiswa, serta (bila diisi)
 * angkatan & semester yang cocok. Status sudah dibaca per pengguna.
 */
class StudentAnnouncementFeed
{
    public const FILTERS = ['all', 'unread', 'study_program', 'faculty', 'admission_year', 'semester', 'students'];

    public function __construct(private readonly StudentAcademicService $academics) {}

    /**
     * @return Builder<Announcement>
     */
    public function visibleTo(Student $student): Builder
    {
        $student->loadMissing('studyProgram');
        $semester = $this->academics->semesterNumber($student);

        return Announcement::query()
            ->where('published_at', '<=', now())
            ->whereIn('audience', ['all', 'students'])
            ->where(fn (Builder $query) => $query
                ->where('target_scope', AnnouncementTargetScope::Universitas)
                ->orWhere(fn (Builder $inner) => $inner
                    ->where('target_scope', AnnouncementTargetScope::Fakultas)
                    ->where('target_id', $student->studyProgram->faculty_id))
                ->orWhere(fn (Builder $inner) => $inner
                    ->where('target_scope', AnnouncementTargetScope::ProgramStudi)
                    ->where('target_id', $student->study_program_id)))
            ->where(fn (Builder $query) => $query
                ->whereNull('target_admission_year')
                ->orWhere('target_admission_year', $student->admission_year))
            ->where(fn (Builder $query) => $query
                ->whereNull('target_semester')
                ->when($semester !== null, fn (Builder $inner) => $inner->orWhere('target_semester', $semester)));
    }

    /**
     * @return LengthAwarePaginator<int, Announcement>
     */
    public function paginate(Student $student, User $user, string $filter = 'all', ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->visibleTo($student)
            ->withExists(['reads as is_read' => fn (Builder $reads) => $reads->where('user_id', $user->id)])
            ->with(['creator', 'attachment'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at');

        match ($filter) {
            'unread' => $query->whereDoesntHave('reads', fn (Builder $reads) => $reads->where('user_id', $user->id)),
            'study_program' => $query->where('target_scope', AnnouncementTargetScope::ProgramStudi),
            'faculty' => $query->where('target_scope', AnnouncementTargetScope::Fakultas),
            'admission_year' => $query->whereNotNull('target_admission_year'),
            'semester' => $query->whereNotNull('target_semester'),
            'students' => $query->where('audience', 'students'),
            default => null,
        };

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(fn (Builder $inner) => $inner->where('title', 'like', $term)->orWhere('body', 'like', $term));
        }

        return $query->paginate(min(max($perPage, 1), 50));
    }

    public function unreadCount(Student $student, User $user): int
    {
        return $this->visibleTo($student)
            ->whereDoesntHave('reads', fn (Builder $reads) => $reads->where('user_id', $user->id))
            ->count();
    }

    public function isVisibleTo(Announcement $announcement, Student $student): bool
    {
        return $this->visibleTo($student)->whereKey($announcement->id)->exists();
    }

    public function markRead(Announcement $announcement, User $user): void
    {
        AnnouncementRead::query()->firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );
    }
}

<?php

namespace Modules\Announcement\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Services\StudentAnnouncementFeed;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('announcements.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('announcements.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('announcements.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('announcements.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('announcements.delete');
    }

    /**
     * Portal Mahasiswa: hanya pengumuman yang memang ditujukan kepadanya;
     * selain itu 404 (tidak membocorkan pengumuman sasaran lain).
     */
    public function viewAsStudent(User $user, Announcement $announcement): Response
    {
        $student = $user->student;

        return $student !== null && app(StudentAnnouncementFeed::class)->isVisibleTo($announcement, $student)
            ? Response::allow()
            : Response::denyAsNotFound('Data tidak ditemukan.');
    }
}

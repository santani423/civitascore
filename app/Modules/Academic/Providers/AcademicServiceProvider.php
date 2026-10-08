<?php

namespace Modules\Academic\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Academic\Listeners\SyncStudentRequestDecision;
use Modules\Academic\Models\Student;
use Modules\Academic\Observers\StudentObserver;
use Modules\Academic\Services\StudentNotificationService;
use Modules\Academic\Support\AcademicClock;
use Modules\Academic\Support\ClassSectionAccess;
use Modules\Academic\Support\LecturerIdentity;

/**
 * Wiring Modul Akademik yang tidak ditemukan otomatis oleh konvensi modul
 * (route & migration sudah dimuat routes/api.php dan ModuleServiceProvider;
 * policy ditemukan otomatis dari Modules\Academic\Policies).
 */
class AcademicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(LecturerIdentity::class);
        $this->app->scoped(ClassSectionAccess::class);
        $this->app->scoped(AcademicClock::class);
        $this->app->scoped(StudentNotificationService::class);
    }

    public function boot(): void
    {
        Student::observe(StudentObserver::class);
        Event::subscribe(SyncStudentRequestDecision::class);
    }
}

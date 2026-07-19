<?php

namespace App\Providers;

use App\Support\Database\EnsureDatabaseExists;
use App\Support\Scoping\InstitutionContextResolver;
use App\Support\Scoping\NullInstitutionContextResolver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Modules\ApprovalWorkflow\Listeners\SendApprovalNotification;
use Modules\Auth\Events\NewDeviceDetected;
use Modules\Auth\Listeners\RecordNewDeviceLogin;
use Modules\FileManagement\Contracts\VirusScanner;
use Modules\FileManagement\Support\NullVirusScanner;
use Modules\Notification\Listeners\LogNotificationDispatch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InstitutionContextResolver::class, NullInstitutionContextResolver::class);
        $this->app->bind(VirusScanner::class, NullVirusScanner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        ResetPassword::createUrlUsing(fn (mixed $notifiable, string $token): string => sprintf(
            '%s/reset-password?token=%s&email=%s',
            rtrim(config('app.frontend_url'), '/'),
            $token,
            urlencode($notifiable->getEmailForPasswordReset()),
        ));

        Event::listen(NewDeviceDetected::class, RecordNewDeviceLogin::class);
        Event::subscribe(LogNotificationDispatch::class);
        Event::subscribe(SendApprovalNotification::class);

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $needsDatabase = str_starts_with($event->command ?? '', 'migrate')
                || in_array($event->command, ['db:seed', 'db:wipe'], true);

            if ($needsDatabase) {
                app(EnsureDatabaseExists::class)->forDefaultConnection();
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

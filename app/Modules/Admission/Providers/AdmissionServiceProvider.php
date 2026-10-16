<?php

namespace Modules\Admission\Providers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Admission\Channels\HttpSmsGateway;
use Modules\Admission\Channels\LogSmsGateway;
use Modules\Admission\Console\PmbDailyCommand;
use Modules\Admission\Console\PmbInstallCommand;
use Modules\Admission\Console\PmbTickCommand;
use Modules\Admission\Contracts\CaptchaVerifier;
use Modules\Admission\Contracts\SmsGateway;
use Modules\Admission\Http\Middleware\EnsureApplicant;
use Modules\Admission\Http\Middleware\EnsureStaff;
use Modules\Admission\Http\Middleware\Idempotent;
use Modules\Admission\Http\Middleware\ResolvePublicTenant;
use Modules\Admission\Integrations\DisabledCaptchaVerifier;
use Modules\Admission\Integrations\TurnstileCaptchaVerifier;
use Modules\Admission\Listeners\SyncApprovalDecisions;
use Modules\Admission\Outbox\Handlers;
use Modules\Admission\Outbox\OutboxProcessor;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Payments\PaymentGatewayManager;
use Modules\Admission\Payments\XenditGateway;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\ScopeResolver;
use Modules\FileManagement\Models\FileUpload;
use RuntimeException;

/**
 * Wiring modul PMB (Admission): binding layanan, penyedia eksternal
 * (gateway, CAPTCHA, SMS), handler outbox, listener approval, rate limiter
 * (11 §A.2), view PDF, dan jadwal job (dijalankan `schedule:run`; endpoint
 * kritis juga memicu publikasi secara malas bila scheduler tidak berjalan).
 */
class AdmissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/pmb.php'), 'pmb');

        $this->app->scoped(PmbSettings::class);
        $this->app->scoped(ScopeResolver::class);

        $this->app->singleton(PaymentGatewayManager::class, function ($app): PaymentGatewayManager {
            $manager = new PaymentGatewayManager($app->make(PmbSettings::class));

            // Gateway online = Xendit (config/services.php); tanpa secret key hanya transfer manual & loket.
            $xendit = config('services.xendit');

            if (! empty($xendit['secret_key'])) {
                $manager->register(new XenditGateway((string) $xendit['secret_key'], (string) ($xendit['webhook_token'] ?? ''), (string) $xendit['base_url'], (int) $xendit['timeout']));
            }

            return $manager;
        });

        $this->app->bind(CaptchaVerifier::class, function ($app): CaptchaVerifier {
            $driver = (string) config('pmb.captcha.driver', 'none');

            if ($driver === 'turnstile') {
                return new TurnstileCaptchaVerifier;
            }

            if ($app->isProduction()) {
                // 13 §B.2: CAPTCHA wajib di produksi — konfigurasi salah tidak boleh diam-diam mematikannya.
                throw new RuntimeException('PMB_CAPTCHA_DRIVER wajib "turnstile" di production.');
            }

            return new DisabledCaptchaVerifier;
        });

        $this->app->bind(SmsGateway::class, fn () => config('pmb.sms.driver') === 'http' ? new HttpSmsGateway : new LogSmsGateway);

        $this->app->singleton(OutboxProcessor::class, fn ($app) => new OutboxProcessor($app->make(TenantContext::class), [
            OutboxRecorder::NOTIFY_APPLICANT => Handlers\NotifyApplicantHandler::class,
            OutboxRecorder::NOTIFY_PERMISSION => Handlers\NotifyPermissionHandler::class,
            OutboxRecorder::CONVERSION_REQUESTED => Handlers\ConversionRequestedHandler::class,
            OutboxRecorder::STUDENT_ENROLLED => Handlers\StudentEnrolledHandler::class,
            OutboxRecorder::DECISION_PUBLISHED => Handlers\DecisionPublishedHandler::class,
            OutboxRecorder::EXAM_CARD => Handlers\ExamCardHandler::class,
            OutboxRecorder::SEAT_RELEASED => Handlers\SeatReleasedHandler::class,
            OutboxRecorder::BROADCAST => Handlers\BroadcastHandler::class,
            OutboxRecorder::EXPORT => Handlers\ExportHandler::class,
        ]));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Views', 'admission');

        $router = $this->app['router'];
        $router->aliasMiddleware('pmb.applicant', EnsureApplicant::class);
        $router->aliasMiddleware('pmb.staff', EnsureStaff::class);
        $router->aliasMiddleware('pmb.public', ResolvePublicTenant::class);
        $router->aliasMiddleware('pmb.idempotent', Idempotent::class);

        Event::subscribe(SyncApprovalDecisions::class);

        $this->configureRateLimiting();

        if ($this->app->runningInConsole()) {
            $this->commands([PmbTickCommand::class, PmbDailyCommand::class, PmbInstallCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('pmb:tick')->everyMinute()->withoutOverlapping(10);
            $schedule->command('pmb:daily')->dailyAt('00:15')->withoutOverlapping(120);
        });

        Route::bind('pmbFile', fn (string $value) => FileUpload::query()->withoutGlobalScopes()->findOrFail($value));
    }

    /** Batas laju per grup endpoint (docs/pmb/11 §A.2). */
    private function configureRateLimiting(): void
    {
        $by = fn (Request $request): string => (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

        RateLimiter::for('pmb-public', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('pmb-status-check', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('pmb-register', fn (Request $r) => Limit::perHour(5)->by($r->ip()));
        RateLimiter::for('pmb-login', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('pmb-otp', fn (Request $r) => Limit::perHour(5)->by($r->ip().'|'.mb_strtolower((string) ($r->input('destination') ?? $r->input('email') ?? $r->input('login') ?? ''))));
        RateLimiter::for('pmb-applicant', fn (Request $r) => Limit::perMinute(120)->by($by($r)));
        RateLimiter::for('pmb-upload', fn (Request $r) => Limit::perMinute(20)->by($by($r)));
        RateLimiter::for('pmb-admin', fn (Request $r) => Limit::perMinute(300)->by($by($r)));
        RateLimiter::for('pmb-export', fn (Request $r) => Limit::perHour(10)->by($by($r)));
        RateLimiter::for('pmb-webhook', fn (Request $r) => Limit::perMinute(600)->by((string) $r->route('provider')));
        RateLimiter::for('pmb-integration', fn (Request $r) => Limit::perMinute(60)->by($by($r)));
    }
}

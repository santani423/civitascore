<?php

namespace Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Contracts\CaptchaVerifier;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\PeriodStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AdmissionPath;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\FeeComponent;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\ProgramProfile;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Services\Announcement\AcceptanceLetterService;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Support\ApplicationStageResolver;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbSettings;
use Modules\Tenancy\Models\University;

/**
 * Website PMB publik (docs/pmb/03 §B, 11 §A.17): hanya konten & konfigurasi
 * terpublikasi, tanpa data pribadi; cache singkat per tenant (B.2).
 */
class PublicSiteController extends Controller
{
    public function __construct(private readonly TenantContext $tenant, private readonly PmbSettings $settings) {}

    public function site(PmbFileStorage $storage): JsonResponse
    {
        return $this->cached('site', function () use ($storage): array {
            $university = University::query()->findOrFail($this->tenant->universityId());
            $logo = $university->logo_file_id ? \Modules\FileManagement\Models\FileUpload::query()->withoutGlobalScopes()->find($university->logo_file_id) : null;
            $legal = fn (ContentType $type) => \Modules\Admission\Services\Configuration\ContentService::activeVersion($type)?->only(['id', 'title', 'slug', 'version', 'publish_at']);

            return [
                'university' => [
                    'id' => $university->getKey(), 'code' => $university->code, 'name' => $university->name, 'short_name' => $university->short_name,
                    'primary_color' => $university->primary_color, 'secondary_color' => $university->secondary_color, 'timezone' => $university->timezone,
                    'website' => $university->website, 'logo_url' => $logo && $logo->status->value === 'clean' ? $storage->temporaryUrl($logo, 60) : null,
                ],
                'contact' => $this->settings->get('pmb.contact'),
                'show_quota_status' => $this->settings->bool('pmb.show_quota_status'),
                'captcha' => ['driver' => config('pmb.captcha.driver'), 'site_key' => config('pmb.captcha.turnstile_site_key')],
                'legal' => ['privacy_policy' => $legal(ContentType::PrivacyPolicy), 'terms' => $legal(ContentType::Terms)],
                'banners' => $this->publishedContents(ContentType::Banner)->map->only(['id', 'title', 'body', 'file_id', 'sort_order'])->values(),
            ];
        }, 60);
    }

    public function activePeriods(): JsonResponse
    {
        return $this->cached('periods', fn () => Period::query()->where('status', PeriodStatus::Published->value)->with(['waves' => fn ($q) => $q->whereNotIn('status', ['draft'])->orderBy('sequence'), 'waves.wavePaths.admissionPath', 'entryTerm'])->get()
            ->map(fn (Period $p) => [
                'id' => $p->getKey(), 'code' => $p->code, 'name' => $p->name, 'description' => $p->description,
                'starts_on' => $p->starts_on?->toDateString(), 'ends_on' => $p->ends_on?->toDateString(),
                'waves' => $p->waves->map(fn ($w) => [
                    'id' => $w->getKey(), 'code' => $w->code, 'name' => $w->name, 'status' => $w->status->value, 'is_ods' => $w->is_ods,
                    'registration_opens_at' => $w->registration_opens_at?->toIso8601String(), 'registration_closes_at' => $w->registration_closes_at?->toIso8601String(),
                    'payment_deadline_at' => $w->payment_deadline_at?->toIso8601String(), 'exam_starts_on' => $w->exam_starts_on?->toDateString(),
                    'exam_ends_on' => $w->exam_ends_on?->toDateString(), 'announcement_at' => $w->announcement_at?->toIso8601String(),
                    're_registration_opens_at' => $w->re_registration_opens_at?->toIso8601String(), 're_registration_closes_at' => $w->re_registration_closes_at?->toIso8601String(),
                    'paths' => $w->wavePaths->where('is_open', true)->map(fn ($wp) => ['code' => $wp->admissionPath?->code, 'name' => $wp->admissionPath?->name, 'fee' => (string) $wp->registration_fee])->values(),
                ])->values(),
            ])->values()->all());
    }

    public function openWavePaths(ApplicationService $applications): JsonResponse
    {
        return ApiResponse::success($applications->openWavePaths(null));
    }

    public function programs(Request $request): JsonResponse
    {
        $data = $this->cached('programs', function (): array {
            $periodIds = Period::query()->where('status', PeriodStatus::Published->value)->pluck('id');
            $offered = Quota::query()->whereIn('period_id', $periodIds)->where('is_offered', true)->with('studySystem', 'admissionPath')->get()->groupBy('study_program_id');

            return ProgramProfile::query()->where('is_published', true)->with(['studyProgram.faculty'])->get()
                ->map(fn (ProgramProfile $profile) => [
                    'slug' => $profile->slug, 'name' => $profile->studyProgram?->name, 'degree_level' => $profile->studyProgram?->degree_level,
                    'faculty' => $profile->studyProgram?->faculty?->name, 'summary' => $profile->summary, 'accreditation' => $profile->accreditation_label,
                    'study_systems' => $offered->get($profile->study_program_id, collect())->map(fn ($q) => $q->studySystem?->name)->unique()->values(),
                    'paths' => $offered->get($profile->study_program_id, collect())->map(fn ($q) => $q->admissionPath?->name)->unique()->values(),
                    'cover_file_id' => $profile->cover_file_id,
                ])->values()->all();
        });

        return $data;
    }

    public function program(string $slug, PmbFileStorage $storage): JsonResponse
    {
        $profile = ProgramProfile::query()->where('slug', $slug)->where('is_published', true)->with(['studyProgram.faculty', 'brochure', 'cover'])->first();

        if ($profile === null) {
            return ApiResponse::error('Data tidak ditemukan.', status: 404);
        }

        $periodIds = Period::query()->where('status', PeriodStatus::Published->value)->pluck('id');
        $quotas = Quota::query()->whereIn('period_id', $periodIds)->where('study_program_id', $profile->study_program_id)->where('is_offered', true)->with(['admissionPath', 'studySystem'])->get();
        $fees = FeeComponent::query()->whereIn('period_id', $periodIds)->where('study_program_id', $profile->study_program_id)->where('is_published', true)->with('studySystem', 'admissionPath')->orderBy('sort_order')->get();

        return ApiResponse::success([
            'slug' => $profile->slug, 'name' => $profile->studyProgram?->name, 'degree_level' => $profile->studyProgram?->degree_level,
            'faculty' => $profile->studyProgram?->faculty?->name, 'summary' => $profile->summary, 'description' => $profile->description,
            'accreditation_label' => $profile->accreditation_label, 'accreditation_valid_until' => $profile->accreditation_valid_until?->toDateString(),
            'degree_title' => $profile->degree_title, 'study_duration' => $profile->study_duration, 'career_prospects' => $profile->career_prospects,
            'brochure_url' => $profile->brochure && $profile->brochure->status->value === 'clean' ? $storage->temporaryUrl($profile->brochure, 60) : null,
            'cover_url' => $profile->cover && $profile->cover->status->value === 'clean' ? $storage->temporaryUrl($profile->cover, 60) : null,
            'paths' => $quotas->map(fn ($q) => ['path' => $q->admissionPath?->name, 'path_code' => $q->admissionPath?->code, 'study_system' => $q->studySystem?->name, 'quota_status' => $this->quotaStatus($q)])->values(),
            'fees' => $fees->map(fn ($f) => ['component' => $f->name, 'code' => $f->component_code, 'amount' => (string) $f->amount, 'study_system' => $f->studySystem?->name, 'path' => $f->admissionPath?->name, 'installable' => $f->installable])->values(),
        ]);
    }

    public function paths(): JsonResponse
    {
        return $this->cached('paths', fn () => AdmissionPath::query()->where('is_active', true)->get()->map(fn (AdmissionPath $p) => [
            'code' => $p->code, 'name' => $p->name, 'category' => $p->category->value, 'description' => $p->description,
            'open_waves' => WavePath::query()->where('admission_path_id', $p->getKey())->where('is_open', true)
                ->whereHas('wave', fn ($q) => $q->whereIn('status', ['scheduled', 'open'])->whereHas('period', fn ($pq) => $pq->where('status', 'published')))
                ->with('wave')->get()->map(fn ($wp) => ['wave' => $wp->wave->name, 'closes_at' => $wp->wave->registration_closes_at?->toIso8601String(), 'fee' => (string) $wp->registration_fee])->values(),
        ])->values()->all());
    }

    public function path(string $code): JsonResponse
    {
        $path = AdmissionPath::query()->where('code', strtoupper($code))->where('is_active', true)->first();

        if ($path === null) {
            return ApiResponse::error('Data tidak ditemukan.', status: 404);
        }

        $wavePaths = WavePath::query()->where('admission_path_id', $path->getKey())
            ->whereHas('wave', fn ($q) => $q->whereIn('status', ['scheduled', 'open', 'closed', 'announced'])->whereHas('period', fn ($pq) => $pq->where('status', 'published')))
            ->with(['wave', 'selectionScheme.components'])->get();

        return ApiResponse::success([
            'code' => $path->code, 'name' => $path->name, 'category' => $path->category->value, 'description' => $path->description,
            'requires_exam' => $path->requires_exam, 'eligible_degree_levels' => $path->eligible_degree_levels,
            'waves' => $wavePaths->map(fn (WavePath $wp) => [
                'wave' => $wp->wave->name, 'status' => $wp->wave->status->value, 'opens_at' => $wp->wave->registration_opens_at?->toIso8601String(),
                'closes_at' => $wp->wave->registration_closes_at?->toIso8601String(), 'announcement_at' => $wp->wave->announcement_at?->toIso8601String(),
                'fee' => (string) $wp->registration_fee, 'max_choices' => $wp->max_choices, 'public_notes' => $wp->public_notes,
                // Nama komponen seleksi saja — bobot rinci tidak dipublikasikan (FR-WEB-04).
                'selection_components' => $wp->selectionScheme?->components->pluck('name')->values() ?? [],
                'documents' => DocumentRequirement::query()->where('wave_path_id', $wp->getKey())->with('documentType')->orderBy('sort_order')->get()
                    ->map(fn ($r) => ['name' => $r->documentType?->name, 'stage' => $r->stage->value, 'mandatory' => $r->is_mandatory, 'instructions' => $r->instructions, 'formats' => $r->documentType?->allowed_mime_types, 'max_size_kb' => $r->documentType?->max_size_kb])->values(),
            ])->values(),
        ]);
    }

    public function fees(Request $request): JsonResponse
    {
        $periodIds = Period::query()->where('status', PeriodStatus::Published->value)->pluck('id');
        $rows = FeeComponent::query()->whereIn('period_id', $periodIds)->where('is_published', true)
            ->when($request->query('study_program_id'), fn ($q, $v) => $q->where('study_program_id', $v))
            ->when($request->query('study_system_id'), fn ($q, $v) => $q->where('study_system_id', $v))
            ->with(['studyProgram', 'studySystem', 'admissionPath'])->orderBy('sort_order')->get();

        return ApiResponse::success($rows->map(fn (FeeComponent $f) => [
            'program' => $f->studyProgram?->name, 'study_program_id' => $f->study_program_id, 'study_system' => $f->studySystem?->name,
            'study_system_id' => $f->study_system_id, 'path' => $f->admissionPath?->name, 'component' => $f->name, 'code' => $f->component_code,
            'amount' => (string) $f->amount, 'installable' => $f->installable, 'installment_plan' => $f->installment_plan,
        ])->values());
    }

    public function contents(Request $request): JsonResponse
    {
        $type = ContentType::tryFrom((string) $request->query('type', 'news'));

        if ($type === null || in_array($type, [ContentType::ResultTemplate, ContentType::LoaTemplate], true)) {
            return ApiResponse::error('Tipe konten tidak dikenal.', status: 404);
        }

        $items = $this->publishedContents($type)
            ->when($request->query('search'), fn ($c, $s) => $c->filter(fn ($i) => str_contains(mb_strtolower($i->title.' '.strip_tags((string) $i->body)), mb_strtolower((string) $s))))
            ->when($request->query('category'), fn ($c, $v) => $c->where('category', $v));

        return ApiResponse::success($items->map(fn (Content $c) => [
            'id' => $c->getKey(), 'type' => $c->type->value, 'slug' => $c->slug, 'title' => $c->title, 'category' => $c->category,
            'excerpt' => mb_substr(trim(strip_tags((string) $c->body)), 0, 200), 'body' => in_array($type, [ContentType::Faq, ContentType::Banner], true) ? $c->body : null,
            'published_at' => $c->publish_at?->toIso8601String(), 'seo_title' => $c->seo_title, 'seo_description' => $c->seo_description,
        ])->values());
    }

    public function content(string $type, string $slug): JsonResponse
    {
        $contentType = ContentType::tryFrom($type);
        $item = $contentType ? $this->publishedContents($contentType)->firstWhere('slug', $slug) : null;

        if ($item === null) {
            return ApiResponse::error('Data tidak ditemukan.', status: 404);
        }

        return ApiResponse::success($item->only(['id', 'type', 'slug', 'title', 'body', 'category', 'version', 'publish_at', 'seo_title', 'seo_description', 'file_id']));
    }

    public function sitemap(Request $request): Response
    {
        $base = rtrim((string) config('pmb.frontend_url'), '/').'/pmb';
        $urls = ['', '/prodi', '/jalur', '/biaya', '/jadwal', '/panduan', '/faq', '/berita', '/cek-status', '/daftar-ulang', '/kontak'];
        $urls = [...$urls, ...ProgramProfile::query()->where('is_published', true)->pluck('slug')->map(fn ($s) => '/prodi/'.$s)->all()];
        $urls = [...$urls, ...AdmissionPath::query()->where('is_active', true)->pluck('code')->map(fn ($c) => '/jalur/'.strtolower($c))->all()];
        $urls = [...$urls, ...$this->publishedContents(ContentType::News)->pluck('slug')->map(fn ($s) => '/berita/'.$s)->all()];
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url><loc>'.htmlspecialchars($base.$url, ENT_XML1).'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml', 'Cache-Control' => 'public, max-age=300']);
    }

    public function robots(): Response
    {
        $base = rtrim((string) config('pmb.frontend_url'), '/');

        return response("User-agent: *\nAllow: /pmb\nDisallow: /pmb/portal\nDisallow: /admin\nSitemap: {$base}/api/v1/pmb/public/sitemap.xml\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Cek status/hasil tanpa login (FR-WEB-08, KD-12): nomor + tanggal lahir +
     * CAPTCHA; respons identik untuk kombinasi salah; waktu respons dinormalkan.
     */
    public function statusCheck(Request $request, CaptchaVerifier $captcha, PublicationService $publication): JsonResponse
    {
        $started = microtime(true);
        $data = $request->validate([
            'registration_number' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date'],
            'captcha_token' => ['nullable', 'string', 'max:4096'],
        ]);

        if (! $captcha->verify($data['captcha_token'] ?? null, $request->ip())) {
            throw new DomainRuleException('Verifikasi CAPTCHA gagal.', 'CAPTCHA_FAILED', 422);
        }

        rescue(fn () => $publication->publishDue(), report: false);
        $application = Application::query()->with(['account.profile', 'currentDecision.studyProgram', 'currentDecision.studySystem', 'reRegistration'])
            ->where('registration_number', strtoupper(trim($data['registration_number'])))->first();
        $match = $application !== null && $application->account->profile?->birth_date?->toDateString() === CarbonImmutable::parse($data['birth_date'])->toDateString();

        $this->normalizeTiming($started);

        if (! $match) {
            return ApiResponse::error('Data tidak ditemukan.', status: 404);
        }

        $stage = ApplicationStageResolver::resolve($application);
        $decision = $application->currentDecision;
        $published = $decision !== null && $decision->publication_status->value === 'published';

        return ApiResponse::success([
            'stage' => $stage,
            'stage_label' => ApplicationStageResolver::label($stage, (array) $this->settings->get('pmb.status_labels')),
            'decision_summary' => $published ? [
                'decision' => $decision->decision->value,
                'program' => $decision->studyProgram?->name,
                'study_system' => $decision->studySystem?->name,
            ] : null,
        ]);
    }

    public function verifyLetter(string $code, AcceptanceLetterService $letters): JsonResponse
    {
        $result = $letters->verify($code);

        return $result === null ? ApiResponse::error('Data tidak ditemukan.', status: 404) : ApiResponse::success($result);
    }

    /** @return \Illuminate\Support\Collection<int, Content> */
    private function publishedContents(ContentType $type): \Illuminate\Support\Collection
    {
        return Content::query()->where('type', $type->value)->where('status', ContentStatus::Published->value)
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now()))
            ->orderBy('sort_order')->orderByDesc('publish_at')->get()
            ->groupBy(fn ($c) => $c->slug ?? $c->getKey())->map(fn ($versions) => $versions->sortByDesc('version')->first())->values();
    }

    private function quotaStatus(Quota $quota): ?string
    {
        if (! $this->settings->bool('pmb.show_quota_status')) {
            return null;
        }

        $remaining = $quota->capacity - $quota->accepted_count;

        return $remaining <= 0 ? 'penuh' : ($remaining <= max(1, (int) ceil($quota->capacity * 0.1)) ? 'terbatas' : 'tersedia');
    }

    private function cached(string $key, callable $resolver, int $seconds = 300): JsonResponse
    {
        $data = Cache::remember('pmb:public:'.$this->tenant->universityId().':'.$key, $seconds, $resolver);

        return ApiResponse::success($data)->header('Cache-Control', 'public, max-age=60');
    }

    private function normalizeTiming(float $started): void
    {
        $elapsed = (microtime(true) - $started) * 1_000_000;
        $target = 300_000;

        if ($elapsed < $target) {
            usleep((int) ($target - $elapsed));
        }
    }
}

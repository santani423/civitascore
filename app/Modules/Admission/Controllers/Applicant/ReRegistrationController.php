<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\Admission\Services\ReRegistration\ReRegistrationService;
use Modules\Admission\Support\PmbFileStorage;
use Modules\FileManagement\Models\FileUpload;

/** Daftar ulang pendaftar (docs/pmb/11 §A.13, 07 §K, POR-09). */
class ReRegistrationController extends ApplicantController
{
    public function __construct(private readonly ReRegistrationService $reRegistrations) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $reRegistration = $this->reRegistration($request, $id);
        $checks = $reRegistration->status->isActive() || $reRegistration->status->value === 'enrolled' ? $this->reRegistrations->evaluateChecks($reRegistration) : [];
        $invoice = PmbInvoice::query()->where('application_id', $reRegistration->application_id)->where('type', 'initial_tuition')->whereNotIn('status', ['cancelled'])->with(['items', 'transactions.manualProof'])->latest()->first();
        $profile = $reRegistration->application->account->profile;
        $orientation = ContentService::activeVersion(ContentType::OrientationInfo);

        return ApiResponse::success([
            'id' => $reRegistration->getKey(),
            'status' => $reRegistration->status->value,
            'deadline_at' => $reRegistration->effectiveDeadline()->toIso8601String(),
            'opens_at' => $reRegistration->application->wave?->re_registration_opens_at?->toIso8601String(),
            'program' => $reRegistration->decision?->studyProgram?->name,
            'study_system' => $reRegistration->decision?->studySystem?->name,
            'checks' => $checks,
            'invoice' => $invoice ? PaymentController::presentInvoice($invoice) : null,
            'data' => [
                'religion' => $profile?->religion, 'mother_name' => $profile?->mother_name, 'nationality' => $profile?->nationality,
                'address_line' => $profile?->address_line, 'village' => $profile?->village, 'district' => $profile?->district,
                'city' => $profile?->city, 'province' => $profile?->province, 'region_code' => $profile?->region_code,
                ...array_intersect_key((array) ($profile?->pddikti_extra ?? []), array_flip(ReRegistrationService::PDDIKTI_EXTRA_FIELDS)),
                'almamater_size' => $reRegistration->almamater_size,
            ],
            'revision_targets' => $reRegistration->revision_targets,
            'declarations' => $this->academicTerms(),
            'conversion' => $reRegistration->application->conversion ? ['status' => $reRegistration->application->conversion->status->value, 'nim' => $reRegistration->application->conversion->nim] : null,
            'orientation' => $reRegistration->status->value === 'enrolled' ? $orientation?->only(['title', 'body']) : null,
            'lock_version' => $reRegistration->lock_version,
        ]);
    }

    public function accept(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['declarations' => ['present', 'array'], 'lock_version' => ['required', 'integer']]);
        $result = $this->reRegistrations->accept($this->reRegistration($request, $id), $data['declarations'], (int) $data['lock_version'], $request->ip(), $request->userAgent(), $this->actor($request));

        return ApiResponse::success(['status' => $result['re_registration']->status->value, 'withdrawn_application_ids' => $result['withdrawn_application_ids']], 'Tawaran diterima. Lanjutkan daftar ulang.');
    }

    public function decline(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255'], 'confirm' => ['accepted']]);
        $reRegistration = $this->reRegistrations->decline($this->reRegistration($request, $id), $data['reason'] ?? null, $this->actor($request));

        return ApiResponse::success(['status' => $reRegistration->status->value], 'Tawaran ditolak.');
    }

    public function saveData(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'religion' => ['sometimes', 'nullable', 'in:islam,kristen,katolik,hindu,buddha,konghucu,lainnya'],
            'mother_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'nationality' => ['sometimes', 'nullable', 'string', 'size:2'],
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
            'village' => ['sometimes', 'nullable', 'string', 'max:100'], 'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'], 'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'region_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'residence_type' => ['sometimes', 'nullable', 'in:orang_tua,wali,kos,asrama,panti,lainnya'],
            'transportation' => ['sometimes', 'nullable', 'in:jalan_kaki,sepeda,motor,mobil,angkutan_umum,ojek,lainnya'],
            'kps_recipient' => ['sometimes', 'nullable', 'boolean'],
            'almamater_size' => ['sometimes', 'nullable', 'in:S,M,L,XL,XXL,XXXL'],
            'answers' => ['sometimes', 'array'],
            'lock_version' => ['required', 'integer'],
        ]);
        $reRegistration = $this->reRegistrations->saveData($this->reRegistration($request, $id), collect($data)->except('lock_version')->all(), (int) $data['lock_version'], $this->actor($request));

        return ApiResponse::success(['status' => $reRegistration->status->value, 'lock_version' => $reRegistration->lock_version], 'Data disimpan.');
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['lock_version' => ['required', 'integer']]);
        $reRegistration = $this->reRegistrations->submit($this->reRegistration($request, $id), (int) $data['lock_version'], $this->actor($request));

        return ApiResponse::success(['status' => $reRegistration->status->value], 'Daftar ulang dikirim untuk divalidasi.');
    }

    public function proof(Request $request, string $id, PmbFileStorage $storage): JsonResponse
    {
        $reRegistration = $this->reRegistration($request, $id);
        $file = FileUpload::query()->where('fileable_type', $reRegistration->getMorphClass())->where('fileable_id', $reRegistration->getKey())->latest()->first();

        if ($reRegistration->status->value !== 'enrolled' || $file === null) {
            throw new DomainRuleException('Bukti registrasi tersedia setelah Anda resmi menjadi mahasiswa.', 'NOT_ENROLLED', 409);
        }

        return ApiResponse::success(['url' => $storage->temporaryUrl($file)]);
    }

    private function reRegistration(Request $request, string $id): ReRegistration
    {
        return ReRegistration::query()->with(['application.account.profile', 'application.wave', 'application.conversion', 'decision.studyProgram', 'decision.studySystem'])
            ->where('application_id', $this->application($request, $id)->getKey())->firstOrFail();
    }

    /** @return array<int, array<string, mixed>> */
    private function academicTerms(): array
    {
        return Content::query()->where('type', 'declaration')->where('status', 'published')->where('slug', 'like', 'daftar-ulang%')
            ->get()->map->only(['id', 'title', 'body', 'version'])->values()->all();
    }
}

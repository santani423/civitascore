<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Modules\Admission\Models\ApplicantAchievement;
use Modules\Admission\Models\ApplicantEducation;
use Modules\Admission\Models\ApplicantGuardian;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Account\ProfileService;
use Modules\Admission\Services\Privacy\RetentionService;
use Modules\Admission\Support\ApplicationStageResolver;
use Modules\Admission\Support\SensitiveAccessLogger;

/** Akun & profil pendaftar (docs/pmb/11 §A.4, 04 §C–D.2). */
class ProfileController extends ApplicantController
{
    public function __construct(private readonly ProfileService $profiles, private readonly AccountService $accounts) {}

    public function me(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $applications = $account->applications()->with(['admissionPath', 'wave', 'currentDecision', 'reRegistration'])->latest()->get();

        return ApiResponse::success([
            'account' => AuthController::presentAccount($account),
            'applications' => $applications->map(fn ($a) => [
                'id' => $a->getKey(), 'registration_number' => $a->registration_number, 'path' => $a->admissionPath?->name,
                'wave' => $a->wave?->name, 'current_stage' => ApplicationStageResolver::resolve($a),
            ])->values(),
            'marketing_consent' => $account->consents()->where('consent_type', 'marketing_optional')->whereNull('revoked_at')->exists(),
            'unread_notifications' => $account->unreadNotifications()->count(),
        ]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['sometimes', 'string', 'min:3', 'max:150'], 'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9\s\-]{8,20}$/']]);

        return ApiResponse::success(AuthController::presentAccount($this->accounts->updateAccount($this->account($request), $data)), 'Akun diperbarui.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]]);
        $this->accounts->changePassword($this->account($request), $data['current_password'], $data['password']);

        return response()->json(null, 204);
    }

    public function requestEmailChange(Request $request): JsonResponse
    {
        $data = $request->validate(['new_email' => ['required', 'email', 'max:191'], 'password' => ['required', 'string']]);
        $this->accounts->requestEmailChange($this->account($request), $data['new_email'], $data['password']);

        return ApiResponse::success(null, 'Jika email dapat dipakai, tautan verifikasi telah dikirim ke email baru.', status: 202);
    }

    public function marketingConsent(Request $request): JsonResponse
    {
        $granted = (bool) $request->validate(['granted' => ['required', 'boolean']])['granted'];
        $this->accounts->setMarketingConsent($this->account($request), $granted, $request->ip(), $request->userAgent());

        return ApiResponse::success(['granted' => $granted], 'Preferensi disimpan.');
    }

    public function dataExport(Request $request, RetentionService $retention): JsonResponse
    {
        return ApiResponse::success($retention->exportFor($this->account($request)));
    }

    public function deactivate(Request $request): JsonResponse
    {
        $this->accounts->deactivateSelf($this->account($request), (string) $request->validate(['password' => ['required', 'string']])['password']);

        return ApiResponse::success(null, 'Akun dinonaktifkan.');
    }

    public function profile(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $profile = $this->profiles->profileFor($account);
        $reveal = $request->query('reveal') === 'identity';

        return ApiResponse::success([
            ...$profile->attributesToArray(),
            'nik' => $reveal ? $profile->nik : SensitiveAccessLogger::mask($profile->nik),
            'passport_number' => $reveal ? $profile->passport_number : SensitiveAccessLogger::mask($profile->passport_number),
            'mother_name' => $profile->mother_name,
            'special_needs' => $profile->special_needs,
            'special_needs_note' => $profile->special_needs_note,
            'pddikti_extra' => $profile->pddikti_extra,
            'identity_locked' => $this->profiles->identityLocked($account),
            'locked_fields' => $this->profiles->identityLocked($account) ? ProfileService::LOCKED_IDENTITY_FIELDS : [],
            'nik_warning' => ProfileService::nikBirthDateWarning($profile->nik, $profile->birth_date?->toDateString()),
        ]);
    }

    public function saveProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'nationality' => ['sometimes', 'nullable', 'string', 'size:2'],
            'nik' => ['sometimes', 'nullable', 'string', 'max:20'],
            'passport_number' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'passport_country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'birth_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', 'in:male,female'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:20'],
            'mother_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rt' => ['sometimes', 'nullable', 'string', 'max:5'], 'rw' => ['sometimes', 'nullable', 'string', 'max:5'],
            'village' => ['sometimes', 'nullable', 'string', 'max:100'], 'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'], 'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'region_code' => ['sometimes', 'nullable', 'string', 'max:20'], 'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'special_needs' => ['sometimes', 'nullable', 'array'], 'special_needs.*' => ['string', 'max:50'],
            'special_needs_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'lock_version' => ['nullable', 'integer'],
        ]);

        $profile = $this->profiles->saveProfile($this->account($request), collect($data)->except('lock_version')->all(), isset($data['lock_version']) ? (int) $data['lock_version'] : null);

        return ApiResponse::success([
            'lock_version' => $profile->lock_version,
            'nik_warning' => ProfileService::nikBirthDateWarning($profile->nik, $profile->birth_date?->toDateString()),
        ], 'Profil disimpan.');
    }

    public function claimIdentity(Request $request): JsonResponse
    {
        $data = $request->validate(['identity_number' => ['required', 'digits:16'], 'note' => ['nullable', 'string', 'max:1000']]);
        $this->profiles->claimIdentity($this->account($request), $data['identity_number'], $data['note'] ?? null);

        return ApiResponse::success(null, 'Permintaan klaim identitas dikirim ke panitia.', status: 202);
    }

    public function educations(Request $request): JsonResponse
    {
        return ApiResponse::success($this->account($request)->educations()->with('reportScores')->get());
    }

    public function storeEducation(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profiles->saveEducation($this->account($request), null, $this->educationData($request)), 'Riwayat pendidikan ditambahkan.', status: 201);
    }

    public function updateEducation(Request $request, string $id): JsonResponse
    {
        $education = $this->account($request)->educations()->whereKey($id)->firstOrFail();

        return ApiResponse::success($this->profiles->saveEducation($this->account($request), $education, $this->educationData($request, partial: true)), 'Riwayat pendidikan diperbarui.');
    }

    public function destroyEducation(Request $request, string $id): JsonResponse
    {
        $this->profiles->deleteEducation($this->account($request)->educations()->whereKey($id)->firstOrFail());

        return response()->json(null, 204);
    }

    public function reportScores(Request $request, string $id): JsonResponse
    {
        $education = $this->account($request)->educations()->whereKey($id)->firstOrFail();
        $data = $request->validate([
            'scores' => ['present', 'array', 'max:200'],
            'scores.*.semester' => ['required', 'integer', 'between:1,6'],
            'scores.*.subject_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'scores.*.subject_name' => ['nullable', 'string', 'max:100'],
            'scores.*.score' => ['required', 'numeric', 'between:0,100'],
        ]);
        $this->profiles->replaceReportScores($education, $data['scores']);

        return ApiResponse::success(ApplicantEducation::query()->with('reportScores')->find($education->getKey()), 'Nilai rapor disimpan.');
    }

    public function guardians(Request $request): JsonResponse
    {
        return ApiResponse::success($this->account($request)->guardians()->get()->map(fn (ApplicantGuardian $g) => [...$g->attributesToArray(), 'nik' => SensitiveAccessLogger::mask($g->nik)]));
    }

    public function storeGuardian(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profiles->saveGuardian($this->account($request), null, $this->guardianData($request)), 'Data ditambahkan.', status: 201);
    }

    public function updateGuardian(Request $request, string $id): JsonResponse
    {
        $guardian = $this->account($request)->guardians()->whereKey($id)->firstOrFail();

        return ApiResponse::success($this->profiles->saveGuardian($this->account($request), $guardian, $this->guardianData($request, true)), 'Data diperbarui.');
    }

    public function destroyGuardian(Request $request, string $id): JsonResponse
    {
        $this->account($request)->guardians()->whereKey($id)->firstOrFail()->delete();

        return response()->json(null, 204);
    }

    public function achievements(Request $request): JsonResponse
    {
        return ApiResponse::success($this->account($request)->achievements()->with('document')->get());
    }

    public function storeAchievement(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profiles->saveAchievement($this->account($request), null, $this->achievementData($request)), 'Prestasi ditambahkan.', status: 201);
    }

    public function updateAchievement(Request $request, string $id): JsonResponse
    {
        $achievement = $this->account($request)->achievements()->whereKey($id)->firstOrFail();

        return ApiResponse::success($this->profiles->saveAchievement($this->account($request), $achievement, $this->achievementData($request, true)), 'Prestasi diperbarui.');
    }

    public function destroyAchievement(Request $request, string $id): JsonResponse
    {
        $this->profiles->deleteAchievement($this->account($request)->achievements()->whereKey($id)->firstOrFail());

        return response()->json(null, 204);
    }

    public function notifications(Request $request): JsonResponse
    {
        $paginator = $this->account($request)->notifications()->latest()->paginate(min(50, (int) $request->query('per_page', 20)));

        return response()->json([
            'success' => true, 'message' => 'Berhasil.',
            'data' => collect($paginator->items())->map(fn ($n) => ['id' => $n->id, 'title' => $n->data['title'] ?? null, 'message' => $n->data['message'] ?? null, 'event_key' => $n->data['event_key'] ?? null, 'link' => $n->data['link'] ?? null, 'read_at' => $n->read_at?->toIso8601String(), 'created_at' => $n->created_at?->toIso8601String()]),
            'meta' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()],
        ]);
    }

    public function readNotification(Request $request, string $id): JsonResponse
    {
        $id === 'all'
            ? $this->account($request)->unreadNotifications->markAsRead()
            : $this->account($request)->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(null, 204);
    }

    /** @return array<string, mixed> */
    private function educationData(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'level' => [$req, 'in:secondary,diploma,bachelor,master'],
            'institution_name' => [$req, 'string', 'max:200'],
            'institution_npsn' => ['nullable', 'digits:8'],
            'school_type' => ['nullable', 'in:sma,smk,ma,paket_c,luar_negeri,lainnya'],
            'major' => ['nullable', 'string', 'max:100'],
            'graduation_year' => ['nullable', 'integer', 'between:1960,'.(now()->year + 1)],
            'is_graduating' => ['nullable', 'boolean'],
            'nisn' => ['nullable', 'digits:10'],
            'previous_student_id' => ['nullable', 'string', 'max:30'],
            'gpa' => ['nullable', 'numeric', 'between:0,4'],
            'credits_earned' => ['nullable', 'integer', 'between:0,400'],
            'previous_accreditation' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'country' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /** @return array<string, mixed> */
    private function guardianData(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'relationship' => [$req, 'in:father,mother,guardian,emergency_contact'],
            'full_name' => [$req, 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:191'],
            'nik' => ['nullable', 'digits:16'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'education_level' => ['nullable', 'string', 'max:30'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'income_range' => ['nullable', 'in:lt_1m,1m_3m,3m_5m,5m_10m,gt_10m,none'],
            'is_deceased' => ['nullable', 'boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function achievementData(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$req, 'string', 'max:200'],
            'category' => [$req, 'in:academic,non_academic'],
            'level' => [$req, 'in:school,district,province,national,international'],
            'rank' => [$req, 'in:1,2,3,finalist,participant'],
            'year' => [$req, 'integer', 'between:2000,'.now()->year],
            'organizer' => ['nullable', 'string', 'max:200'],
        ]);
    }
}

<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\ProgramProfile;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbPermission;

/** CMS website PMB, dokumen legal berversi, profil prodi (FR-CMS, ADM-11/12). */
class ContentController extends AdminController
{
    public function __construct(private readonly ContentService $contents) {}

    public function index(Request $request): JsonResponse
    {
        $query = Content::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->orderBy('type')->orderBy('sort_order')->orderByDesc('updated_at')
            ->select(['id', 'type', 'period_id', 'slug', 'title', 'category', 'version', 'status', 'publish_at', 'unpublish_at', 'sort_order', 'updated_at']);

        return $this->paginate($query, $request, default: 50);
    }

    public function show(Content $content, PmbFileStorage $storage): JsonResponse
    {
        $content->load('file');

        return ApiResponse::success([
            ...$content->toArray(),
            'file_url' => $content->file && $content->file->status->value === 'clean' ? $storage->publicUrl($content->file) : null,
            'versioned' => in_array($content->type, ContentService::VERSIONED_TYPES, true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return ApiResponse::success($this->contents->save(null, $this->validateContent($request, true), (string) $this->user($request)->getKey()), 'Konten dibuat.', status: 201);
    }

    public function update(Request $request, Content $content): JsonResponse
    {
        return ApiResponse::success($this->contents->save($content, $this->validateContent($request, false), (string) $this->user($request)->getKey()), 'Konten diperbarui.');
    }

    public function destroy(Content $content): JsonResponse
    {
        $this->contents->delete($content);

        return ApiResponse::success(null, 'Konten dihapus.');
    }

    public function publish(Request $request, Content $content): JsonResponse
    {
        return ApiResponse::success($this->contents->publish($content, (string) $this->user($request)->getKey()), 'Konten dipublikasikan.');
    }

    public function unpublish(Request $request, Content $content): JsonResponse
    {
        return ApiResponse::success($this->contents->unpublish($content, (string) $this->user($request)->getKey()), 'Konten diturunkan.');
    }

    public function newVersion(Request $request, Content $content): JsonResponse
    {
        return ApiResponse::success($this->contents->newVersion($content, (string) $this->user($request)->getKey()), 'Versi baru dibuat.', status: 201);
    }

    /** Unggah gambar/berkas konten (sampul, brosur, gambar artikel). */
    public function uploadImage(Request $request, PmbFileStorage $storage): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:5120'], 'kind' => ['nullable', Rule::in(['image', 'document'])]]);
        $mimes = $request->input('kind') === 'document' ? ['application/pdf'] : ['image/jpeg', 'image/png'];
        $file = $storage->store($request->file('file'), $mimes, 5120, ['content'], uploadedByUserId: (string) $this->user($request)->getKey());

        return ApiResponse::success([
            'id' => $file->getKey(),
            'url' => $file->status->value === 'clean' ? $storage->publicUrl($file) : null,
            'status' => $file->status->value,
        ], 'Berkas diunggah.', status: 201);
    }

    public function programProfiles(Request $request): JsonResponse
    {
        $scope = $this->programScope($request, PmbPermission::CONFIG_READ);
        $profiles = ProgramProfile::query()->get()->keyBy('study_program_id');

        return ApiResponse::success(StudyProgram::query()->with('faculty:id,name')->when($scope !== null, fn ($q) => $q->whereIn('id', $scope ?: ['-']))
            ->orderBy('name')->get(['id', 'faculty_id', 'code', 'name', 'degree_level', 'is_active'])
            ->map(fn (StudyProgram $p) => [...$p->toArray(), 'profile' => $profiles->get($p->getKey())]));
    }

    public function saveProgramProfile(Request $request, StudyProgram $studyProgram): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:50000'],
            'accreditation_label' => ['nullable', 'string', 'max:50'],
            'accreditation_valid_until' => ['nullable', 'date'],
            'degree_title' => ['nullable', 'string', 'max:50'],
            'study_duration' => ['nullable', 'string', 'max:50'],
            'career_prospects' => ['nullable', 'string', 'max:5000'],
            'cover_file_id' => ['nullable', 'string', Rule::exists('file_uploads', 'id')],
            'brochure_file_id' => ['nullable', 'string', Rule::exists('file_uploads', 'id')],
        ]);

        return ApiResponse::success($this->contents->saveProgramProfile($studyProgram, $data, $this->user($request)), 'Profil prodi disimpan.');
    }

    public function publishProgramProfile(Request $request, StudyProgram $studyProgram): JsonResponse
    {
        $this->assertProgramScope($request, PmbPermission::PROGRAM_PROFILE_MANAGE, (string) $studyProgram->getKey());
        $data = $request->validate(['is_published' => ['required', 'boolean']]);
        $profile = ProgramProfile::query()->where('study_program_id', $studyProgram->getKey())->firstOrFail();

        return ApiResponse::success($this->contents->publishProgramProfile($profile, (bool) $data['is_published'], $this->user($request)), $data['is_published'] ? 'Profil dipublikasikan.' : 'Profil disembunyikan.');
    }

    /** @return array<string, mixed> */
    private function validateContent(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'type' => [$creating ? 'required' : 'prohibited', Rule::in(ContentType::values())],
            'period_id' => ['nullable', 'string', Rule::exists('pmb_periods', 'id')->where('university_id', app(TenantContext::class)->universityId())],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9-]+$/'],
            'title' => [$req, 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:200000'],
            'category' => ['nullable', 'string', 'max:50'],
            'file_id' => ['nullable', 'string', Rule::exists('file_uploads', 'id')],
            'publish_at' => ['nullable', 'date'],
            'unpublish_at' => ['nullable', 'date', 'after:publish_at'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}

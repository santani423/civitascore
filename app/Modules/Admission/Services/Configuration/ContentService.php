<?php

namespace Modules\Admission\Services\Configuration;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\ProgramProfile;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\HtmlSanitizer;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\ScopeResolver;
use Modules\AuditLog\Enums\AuditAction;

/**
 * CMS website PMB (FR-WEB-07/10) dan profil prodi publik (FR-CFG-05).
 * Dokumen legal, pernyataan, & template LoA/hasil berversi: versi
 * terpublikasi tidak diubah — "versi baru" (BR-09 mencatat versi persetujuan).
 */
class ContentService
{
    public const VERSIONED_TYPES = [ContentType::Declaration, ContentType::PrivacyPolicy, ContentType::Terms, ContentType::ResultTemplate, ContentType::LoaTemplate];

    public function __construct(private readonly HistoryRecorder $history, private readonly ScopeResolver $scope) {}

    /** @param  array<string, mixed>  $data */
    public function save(?Content $content, array $data, string $userId): Content
    {
        $values = Arr::only($data, ['type', 'period_id', 'slug', 'title', 'body', 'category', 'file_id', 'publish_at', 'unpublish_at', 'seo_title', 'seo_description', 'sort_order']);
        $type = ContentType::from($values['type'] ?? $content?->type->value);

        if ($content !== null && in_array($type, self::VERSIONED_TYPES, true) && $content->status === ContentStatus::Published) {
            throw new ConflictException('Versi terpublikasi tidak dapat diubah; buat versi baru.', 'CONFIG_IN_USE');
        }

        if (isset($values['body'])) {
            $values['body'] = in_array($type, [ContentType::ResultTemplate, ContentType::LoaTemplate], true)
                ? $this->sanitizeTemplate($values['body'])
                : HtmlSanitizer::clean($values['body']);
        }

        if (empty($values['slug']) && $content === null) {
            $values['slug'] = in_array($type, self::VERSIONED_TYPES, true) ? $type->value : Str::slug($values['title'] ?? 'konten').'-'.Str::lower(Str::random(4));
        }

        return DB::transaction(function () use ($content, $values, $userId): Content {
            if ($content === null) {
                $content = Content::query()->create([...$values, 'version' => 1, 'status' => ContentStatus::Draft, 'created_by' => $userId, 'updated_by' => $userId]);
                $this->history->change($content, [], Arr::only($values, ['type', 'title', 'slug']), null, AuditAction::Created);

                return $content;
            }

            $old = $content->only(array_keys($values));
            $content->forceFill([...$values, 'updated_by' => $userId])->save();
            $this->history->change($content, Arr::except($old, ['body']), Arr::except($values, ['body']));

            return $content->refresh();
        });
    }

    public function publish(Content $content, string $userId): Content
    {
        return DB::transaction(function () use ($content, $userId): Content {
            if (in_array($content->type, self::VERSIONED_TYPES, true)) {
                // Satu versi aktif per tipe (+slug): versi lama diarsipkan.
                Content::query()->where('type', $content->type->value)->where('slug', $content->slug)
                    ->where('status', ContentStatus::Published->value)->whereKeyNot($content->getKey())
                    ->update(['status' => ContentStatus::Archived->value]);
            }

            $content->forceFill(['status' => ContentStatus::Published, 'publish_at' => $content->publish_at ?? now(), 'updated_by' => $userId])->save();
            $this->history->action($content, AuditAction::Updated, ['status' => 'published', 'version' => $content->version]);

            return $content->refresh();
        });
    }

    public function unpublish(Content $content, string $userId): Content
    {
        $content->forceFill(['status' => ContentStatus::Archived, 'updated_by' => $userId])->save();
        $this->history->action($content, AuditAction::Updated, ['status' => 'archived']);

        return $content;
    }

    public function newVersion(Content $content, string $userId): Content
    {
        $copy = Content::query()->create([
            ...Arr::only($content->getAttributes(), ['type', 'period_id', 'slug', 'title', 'body', 'category', 'file_id', 'seo_title', 'seo_description', 'sort_order']),
            'version' => (int) Content::query()->where('type', $content->type->value)->where('slug', $content->slug)->max('version') + 1,
            'status' => ContentStatus::Draft,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $this->history->change($copy, [], ['type' => $copy->type->value, 'version' => $copy->version], null, AuditAction::Created);

        return $copy;
    }

    public function delete(Content $content): void
    {
        if (in_array($content->type, self::VERSIONED_TYPES, true) && $content->status !== ContentStatus::Draft) {
            throw new ConflictException('Dokumen berversi yang pernah terbit tidak dapat dihapus (jejak persetujuan).', 'CONFIG_IN_USE');
        }

        $this->history->change($content, $content->only(['type', 'title']), [], null, AuditAction::Deleted);
        $content->delete();
    }

    /**
     * Versi aktif dokumen (kebijakan privasi, ketentuan, pernyataan) untuk consent.
     */
    public static function activeVersion(ContentType $type, ?string $slug = null): ?Content
    {
        return Content::query()->where('type', $type->value)->where('status', ContentStatus::Published->value)
            ->when($slug, fn ($q) => $q->where('slug', $slug))
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->orderByDesc('version')->first();
    }

    /** @param  array<string, mixed>  $data */
    public function saveProgramProfile(StudyProgram $program, array $data, User $actor): ProgramProfile
    {
        if (! $this->scope->canAccessProgram($actor, PmbPermission::PROGRAM_PROFILE_MANAGE, (string) $program->getKey())) {
            abort(404);
        }

        $values = Arr::only($data, ['slug', 'summary', 'description', 'accreditation_label', 'accreditation_valid_until', 'degree_title', 'study_duration', 'career_prospects', 'cover_file_id', 'brochure_file_id']);
        $values['description'] = HtmlSanitizer::clean($values['description'] ?? null);
        $values['slug'] = Str::slug($values['slug'] ?? $program->name);

        $duplicate = ProgramProfile::query()->where('slug', $values['slug'])->where('study_program_id', '!=', $program->getKey())->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['slug' => 'Slug sudah dipakai prodi lain.']);
        }

        return DB::transaction(function () use ($program, $values, $actor): ProgramProfile {
            $profile = ProgramProfile::query()->firstOrNew(['study_program_id' => $program->getKey()]);
            $old = $profile->exists ? $profile->only(array_keys($values)) : [];
            $profile->forceFill([...$values, 'updated_by' => $actor->getKey()])->save();
            $this->history->change($profile, Arr::except($old, ['description']), Arr::except($values, ['description']));

            return $profile->refresh();
        });
    }

    public function publishProgramProfile(ProgramProfile $profile, bool $published, User $actor): ProgramProfile
    {
        $profile->forceFill(['is_published' => $published, 'updated_by' => $actor->getKey()])->save();
        $this->history->action($profile, AuditAction::Updated, ['is_published' => $published]);

        return $profile;
    }

    /**
     * Template LoA/hasil: placeholder terbatas, HTML disanitasi (FR-CFG-12).
     */
    private function sanitizeTemplate(string $body): string
    {
        $allowed = ['applicant_name', 'registration_number', 'program_name', 'study_system', 'deadline', 'letter_number', 'university_name', 'period_name', 'path_name', 'issued_date', 'verification_url'];

        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', $body, $matches);

        foreach ($matches[1] as $placeholder) {
            if (! in_array($placeholder, $allowed, true)) {
                throw ValidationException::withMessages(['body' => "Placeholder {{{$placeholder}}} tidak diizinkan."]);
            }
        }

        return (string) HtmlSanitizer::clean($body);
    }
}

<?php

namespace Modules\Admission\Services\Assessment;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Enums\AssessmentDomain;
use Modules\Admission\Enums\AssessmentPurpose;
use Modules\Admission\Enums\AssessmentVersionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AssessmentDimension;
use Modules\Admission\Models\AssessmentItem;
use Modules\Admission\Models\AssessmentType;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Services\Assessment\Scoring\RiasecScorer;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\StateTransition;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Konfigurasi asesmen: jenis, versi (buat, salin, ajukan, terbitkan, arsipkan), dan dimensi.
 * Aturan siklus versi ada di sini, bukan di controller (docs/one-atma-talent/05).
 * Butir (bank soal) dikelola di AssessmentQuestionBankService.
 */
final class AssessmentConfigurationService
{
    public const SCALE_MIN = 1;

    public const SCALE_MAX = 5;

    /** Status yang belum selesai. Hanya satu versi terbuka per jenis (lihat assertNoOpenVersion). */
    public const OPEN_STATUSES = ['draft', 'in_review', 'approved'];

    public function __construct(private readonly HistoryRecorder $history) {}

    /** @param  array<string, mixed>  $data */
    public function createType(array $data, User $actor): AssessmentType
    {
        $type = AssessmentType::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'domain' => $data['domain'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $actor->getKey(),
        ]);

        $this->history->action($type, AuditAction::Created, ['event' => 'assessment_type_created']);

        return $type;
    }

    /** @param  array<string, mixed>  $data */
    public function updateType(AssessmentType $type, array $data): AssessmentType
    {
        $changes = array_intersect_key($data, array_flip(['name', 'description', 'is_active']));
        $old = array_intersect_key($type->getAttributes(), $changes);

        $type->forceFill($changes)->save();

        if ($old !== array_intersect_key($type->getAttributes(), $changes)) {
            $this->history->change($type, $old, array_intersect_key($type->getAttributes(), $changes), $data['reason'] ?? null);
        }

        return $type->refresh();
    }

    /** @param  array<string, mixed>  $data */
    public function createVersion(AssessmentType $type, array $data, User $actor): AssessmentVersion
    {
        return DB::transaction(function () use ($type, $data, $actor): AssessmentVersion {
            // Kunci baris jenis agar dua pembuatan versi bersamaan tidak mendapat nomor yang sama.
            AssessmentType::query()->whereKey($type->getKey())->lockForUpdate()->firstOrFail();
            $this->assertNoOpenVersion($type);

            $version = AssessmentVersion::query()->create([
                'assessment_type_id' => $type->getKey(),
                'version_no' => $this->nextVersionNo($type),
                'purpose' => $data['purpose'] ?? AssessmentPurpose::Exploratory->value,
                'status' => AssessmentVersionStatus::Draft->value,
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'available_from' => $data['available_from'] ?? null,
                'available_until' => $data['available_until'] ?? null,
                'period_id' => $data['period_id'] ?? null,
                'scoring_config' => $this->scoringConfig($type->domain),
                'created_by' => $actor->getKey(),
            ]);

            $this->history->action($version, AuditAction::Created, ['event' => 'version_created', 'type' => $type->code]);

            return $version;
        });
    }

    /**
     * Versi baru dari versi yang sudah ada (mis. revisi dari versi yang sudah dipublikasikan).
     * Dimensi dan butir disalin; versi asal tidak berubah sama sekali.
     */
    public function cloneVersion(AssessmentVersion $source, User $actor, ?string $note = null): AssessmentVersion
    {
        return DB::transaction(function () use ($source, $actor, $note): AssessmentVersion {
            $type = AssessmentType::query()->whereKey($source->assessment_type_id)->lockForUpdate()->firstOrFail();
            $this->assertNoOpenVersion($type);

            $copy = AssessmentVersion::query()->create([
                'assessment_type_id' => $type->getKey(),
                'parent_version_id' => $source->getKey(),
                'version_no' => $this->nextVersionNo($type),
                'purpose' => $source->purpose->value,
                'status' => AssessmentVersionStatus::Draft->value,
                'title' => $source->title,
                'instructions' => $source->instructions,
                'duration_minutes' => $source->duration_minutes,
                'available_from' => $source->available_from,
                'available_until' => $source->available_until,
                'period_id' => $source->period_id,
                'scoring_config' => $source->scoring_config,
                'change_note' => $note,
                'created_by' => $actor->getKey(),
            ]);

            $dimensionIds = [];

            foreach ($source->dimensions as $dimension) {
                $dimensionIds[$dimension->getKey()] = AssessmentDimension::query()->create([
                    'version_id' => $copy->getKey(),
                    'code' => $dimension->code,
                    'name' => $dimension->name,
                    'description' => $dimension->description,
                    'interpretation' => $dimension->interpretation,
                    'sort_order' => $dimension->sort_order,
                ])->getKey();
            }

            foreach ($source->items as $item) {
                AssessmentItem::query()->create([
                    'version_id' => $copy->getKey(),
                    'dimension_id' => $dimensionIds[$item->dimension_id],
                    'code' => $item->code,
                    'question_type' => $item->question_type->value,
                    'status' => 'draft',
                    'stem' => $item->stem,
                    'is_reversed' => $item->is_reversed,
                    'sort_order' => $item->sort_order,
                    'source_item_id' => $item->getKey(),
                ]);
            }

            $this->history->action($copy, AuditAction::Created, ['event' => 'version_cloned', 'source_version_id' => $source->getKey()], $note);

            return $copy;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function updateVersion(AssessmentVersion $version, array $data): AssessmentVersion
    {
        $this->assertDraft($version);

        // `purpose` tidak ikut diubah di sini: tujuan seleksi harus lewat keputusan terpisah (docs/one-atma-talent/03 §4).
        $changes = array_intersect_key($data, array_flip(['title', 'instructions', 'duration_minutes', 'available_from', 'available_until', 'period_id']));
        $old = array_intersect_key($version->getAttributes(), $changes);

        $version->forceFill($changes)->save();
        $this->history->change($version, $old, array_intersect_key($version->getAttributes(), $changes));

        return $version->refresh();
    }

    /** @param  array<string, mixed>  $data */
    public function addDimension(AssessmentVersion $version, array $data): AssessmentDimension
    {
        $this->assertDraft($version);
        $code = $this->normalizeDimensionCode($version, (string) $data['code']);

        $dimension = AssessmentDimension::query()->create([
            'version_id' => $version->getKey(),
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'interpretation' => $data['interpretation'] ?? null,
            'sort_order' => $data['sort_order'] ?? $this->nextSortOrder(AssessmentDimension::class, $version),
        ]);

        $this->history->action($dimension, AuditAction::Created, ['event' => 'dimension_added', 'version_id' => $version->getKey()]);

        return $dimension;
    }

    /** @param  array<string, mixed>  $data */
    public function updateDimension(AssessmentDimension $dimension, array $data): AssessmentDimension
    {
        $version = $dimension->version;
        $this->assertDraft($version);

        if (array_key_exists('code', $data)) {
            $data['code'] = $this->normalizeDimensionCode($version, (string) $data['code'], $dimension);
        }

        $changes = array_intersect_key($data, array_flip(['code', 'name', 'description', 'interpretation', 'sort_order']));
        $old = array_intersect_key($dimension->getAttributes(), $changes);

        $dimension->forceFill($changes)->save();
        $this->history->change($dimension, $old, array_intersect_key($dimension->getAttributes(), $changes));

        return $dimension->refresh();
    }

    public function deleteDimension(AssessmentDimension $dimension): void
    {
        $this->assertDraft($dimension->version);

        if ($dimension->items()->exists()) {
            throw new DomainRuleException('Dimensi yang masih memiliki butir tidak dapat dihapus.', 'DIMENSION_HAS_ITEMS', 409);
        }

        $this->history->action($dimension, AuditAction::Deleted, ['event' => 'dimension_deleted']);
        $dimension->delete();
    }

    /**
     * Pemeriksaan kelengkapan sebelum versi diajukan untuk ditelaah: tujuan seleksi ditolak,
     * domain harus punya scoring engine, dimensi lengkap, dan setiap dimensi memiliki butir.
     */
    public function assertReadyForReview(AssessmentVersion $version): void
    {
        if ($version->purpose !== AssessmentPurpose::Exploratory) {
            throw new DomainRuleException('Versi untuk seleksi belum dapat diajukan sebelum validasi dan persetujuan fakultas.', 'ADMISSIONS_NOT_APPROVED', 422);
        }

        $type = $version->type()->firstOrFail();

        if (! $type->domain->supportsScoring()) {
            throw new DomainRuleException('Domain '.$type->domain->value.' belum memiliki scoring engine.', 'DOMAIN_NOT_SUPPORTED', 422);
        }

        $required = $type->domain === AssessmentDomain::Interest ? RiasecScorer::DIMENSIONS : [];
        $dimensions = $version->dimensions()->get();
        $missing = array_values(array_diff($required, $dimensions->pluck('code')->all()));

        if ($missing !== []) {
            throw new DomainRuleException('Dimensi belum lengkap: '.implode(', ', $missing).'.', 'DIMENSIONS_INCOMPLETE', 422);
        }

        $empty = $dimensions->filter(fn (AssessmentDimension $d) => $d->items()->doesntExist())->pluck('code')->values()->all();

        if ($empty !== []) {
            throw new DomainRuleException('Dimensi berikut belum memiliki butir: '.implode(', ', $empty).'.', 'ITEMS_INCOMPLETE', 422);
        }
    }

    /**
     * Terbitkan versi yang sudah disetujui. Versi terbit sebelumnya untuk jenis yang sama
     * diarsipkan; sesi lamanya tetap dapat dibaca.
     */
    public function publish(AssessmentVersion $version, User $actor): AssessmentVersion
    {
        return DB::transaction(function () use ($version, $actor): AssessmentVersion {
            $type = AssessmentType::query()->whereKey($version->assessment_type_id)->lockForUpdate()->firstOrFail();
            $version = AssessmentVersion::query()->whereKey($version->getKey())->lockForUpdate()->firstOrFail();

            if ($version->status !== AssessmentVersionStatus::Approved) {
                throw new DomainRuleException('Versi hanya dapat diterbitkan setelah disetujui.', 'VERSION_NOT_APPROVED', 409);
            }

            StateTransition::assert($version->status, AssessmentVersionStatus::Published, 'assessment_version');

            AssessmentVersion::query()
                ->where('assessment_type_id', $type->getKey())
                ->where('status', AssessmentVersionStatus::Published->value)
                ->get()
                ->each(fn (AssessmentVersion $previous) => $this->archiveVersion($previous, 'superseded_by:'.$version->getKey()));

            $old = $version->status->value;
            $version->forceFill([
                'status' => AssessmentVersionStatus::Published->value,
                'published_at' => now(),
                'published_by' => $actor->getKey(),
            ])->save();

            $this->history->change($version, ['status' => $old], ['status' => AssessmentVersionStatus::Published->value]);

            return $version->refresh();
        });
    }

    /** Arsipkan versi yang sudah terbit. Tidak menghapus sesi maupun respons. */
    public function archive(AssessmentVersion $version, ?string $reason = null): AssessmentVersion
    {
        if ($version->status !== AssessmentVersionStatus::Published) {
            throw new DomainRuleException('Hanya versi yang sudah terbit yang dapat diarsipkan.', 'VERSION_NOT_PUBLISHED', 409);
        }

        return DB::transaction(function () use ($version, $reason): AssessmentVersion {
            $this->archiveVersion($version, $reason);

            return $version->refresh();
        });
    }

    /** Hapus draft yang belum pernah dipakai. Versi yang pernah diajukan tidak dapat dihapus, hanya diarsipkan atau ditolak. */
    public function deleteDraft(AssessmentVersion $version): void
    {
        $this->assertDraft($version);

        if ($version->sessions()->exists() || $version->submitted_at !== null) {
            throw new DomainRuleException('Versi yang sudah pernah diajukan atau dipakai tidak dapat dihapus.', 'VERSION_IN_USE', 409);
        }

        $this->history->action($version, AuditAction::Deleted, ['event' => 'draft_deleted']);
        $version->delete();
    }

    /**
     * Perbedaan antara dua versi: dimensi (kode, nama, interpretasi) dan butir (dikunci per kode).
     * Butir tanpa kode dibandingkan memakai id. Perubahan tanpa kode adalah satu baris hilang dan satu baris baru.
     *
     * @return array{dimensions: array<string, mixed>, items: array<string, mixed>}
     */
    public function diff(AssessmentVersion $from, AssessmentVersion $to): array
    {
        $fromDims = $from->dimensions()->get()->keyBy('code');
        $toDims = $to->dimensions()->get()->keyBy('code');
        $dimChanged = [];

        foreach ($fromDims as $code => $dim) {
            if ($toDims->has($code)) {
                $fields = array_keys(array_filter(['name' => $dim->name !== $toDims[$code]->name, 'interpretation' => $dim->interpretation !== $toDims[$code]->interpretation, 'description' => $dim->description !== $toDims[$code]->description]));
                if ($fields !== []) {
                    $dimChanged[] = ['code' => $code, 'fields' => $fields];
                }
            }
        }

        $key = fn (AssessmentItem $item): string => $item->code ?? 'id:'.$item->getKey();
        $fromItems = $from->items()->with('dimension')->get()->keyBy($key);
        $toItems = $to->items()->with('dimension')->get()->keyBy($key);
        $itemChanged = [];

        foreach ($fromItems as $k => $item) {
            if (! $toItems->has($k)) {
                continue;
            }

            $other = $toItems[$k];
            $fields = [];

            if ($item->stem !== $other->stem) {
                $fields[] = 'stem';
            }

            if ($item->is_reversed !== $other->is_reversed) {
                $fields[] = 'is_reversed';
            }

            if ($item->dimension->code !== $other->dimension->code) {
                $fields[] = 'dimension';
            }

            if ($fields !== []) {
                $itemChanged[] = ['key' => $k, 'fields' => $fields, 'from' => $item->stem, 'to' => $other->stem];
            }
        }

        return [
            'dimensions' => [
                'added' => array_values($toDims->keys()->diff($fromDims->keys())->all()),
                'removed' => array_values($fromDims->keys()->diff($toDims->keys())->all()),
                'changed' => $dimChanged,
            ],
            'items' => [
                'added' => $toItems->keys()->diff($fromItems->keys())->values()->map(fn ($k) => ['key' => $k, 'stem' => $toItems[$k]->stem])->all(),
                'removed' => $fromItems->keys()->diff($toItems->keys())->values()->map(fn ($k) => ['key' => $k, 'stem' => $fromItems[$k]->stem])->all(),
                'changed' => $itemChanged,
            ],
        ];
    }

    /** Pastikan versi masih boleh diubah (draft). Versi yang sudah diajukan atau terbit dikunci. */
    public function assertDraft(AssessmentVersion $version): void
    {
        if (! $version->status->isEditable()) {
            throw new DomainRuleException('Versi yang sudah diajukan atau diterbitkan tidak dapat diubah. Salin menjadi versi baru.', 'VERSION_LOCKED', 409);
        }
    }

    private function archiveVersion(AssessmentVersion $version, ?string $reason): void
    {
        StateTransition::assert($version->status, AssessmentVersionStatus::Archived, 'assessment_version');

        $old = $version->status->value;
        $version->forceFill(['status' => AssessmentVersionStatus::Archived->value, 'archived_at' => now()])->save();

        $this->history->change($version, ['status' => $old], ['status' => AssessmentVersionStatus::Archived->value], $reason);
    }

    private function assertNoOpenVersion(AssessmentType $type): void
    {
        $open = AssessmentVersion::query()->where('assessment_type_id', $type->getKey())->whereIn('status', self::OPEN_STATUSES)->exists();

        if ($open) {
            throw new DomainRuleException('Masih ada versi yang belum selesai (draft, ditelaah, atau disetujui). Selesaikan atau hapus versi tersebut terlebih dahulu.', 'VERSION_OPEN', 409);
        }
    }

    private function nextVersionNo(AssessmentType $type): int
    {
        return (int) AssessmentVersion::query()->where('assessment_type_id', $type->getKey())->max('version_no') + 1;
    }

    /** @return array<string, mixed> */
    private function scoringConfig(AssessmentDomain $domain): array
    {
        return array_filter([
            'engine' => $domain->supportsScoring() ? RiasecScorer::ENGINE : null,
            'scale_min' => self::SCALE_MIN,
            'scale_max' => self::SCALE_MAX,
        ], fn ($value) => $value !== null);
    }

    private function normalizeDimensionCode(AssessmentVersion $version, string $code, ?AssessmentDimension $current = null): string
    {
        $code = strtoupper(trim($code));
        $type = $version->type()->firstOrFail();

        if ($type->domain === AssessmentDomain::Interest && ! in_array($code, RiasecScorer::DIMENSIONS, true)) {
            throw new DomainRuleException('Kode dimensi minat harus salah satu dari R, I, A, S, E, C.', 'INVALID_DIMENSION', 422);
        }

        $exists = AssessmentDimension::query()
            ->where('version_id', $version->getKey())
            ->where('code', $code)
            ->when($current !== null, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->exists();

        if ($exists) {
            throw new DomainRuleException("Dimensi {$code} sudah ada pada versi ini.", 'DIMENSION_DUPLICATE', 409);
        }

        return $code;
    }

    /** @param  class-string<AssessmentDimension|AssessmentItem>  $model */
    private function nextSortOrder(string $model, AssessmentVersion $version): int
    {
        return (int) $model::query()->where('version_id', $version->getKey())->max('sort_order') + 1;
    }
}

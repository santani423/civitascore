<?php

namespace Modules\Admission\Services\Assessment;

use Illuminate\Database\Eloquent\Builder;
use Modules\Admission\Enums\AssessmentItemStatus;
use Modules\Admission\Enums\AssessmentQuestionType;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AssessmentDimension;
use Modules\Admission\Models\AssessmentItem;
use Modules\Admission\Models\AssessmentType;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Support\HistoryRecorder;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Bank soal One Atma Talent. Butir hidup di dalam versi draft; butir yang sudah dipakai
 * sesi tidak dapat diubah atau dihapus. Untuk menggunakan ulang butir di versi lain, salin
 * (copy) ke draft lain — salinan mencatat asalnya lewat source_item_id.
 */
final class AssessmentQuestionBankService
{
    /** Kolom pengurutan yang diizinkan (whitelist, tidak pernah dari input mentah). */
    private const SORTS = ['code' => 'code', 'stem' => 'stem', 'created_at' => 'created_at', 'sort_order' => 'sort_order'];

    public function __construct(
        private readonly AssessmentConfigurationService $config,
        private readonly HistoryRecorder $history,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AssessmentItem>
     */
    public function query(?AssessmentType $type, array $filters): Builder
    {
        $sort = self::SORTS[$filters['sort'] ?? 'sort_order'] ?? 'sort_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return AssessmentItem::query()
            ->with(['dimension:id,code,name', 'version:id,version_no,title,status,assessment_type_id'])
            ->when($type !== null, fn (Builder $q) => $q->whereHas('version', fn (Builder $v) => $v->where('assessment_type_id', $type->getKey())))
            ->when(! empty($filters['version_id']), fn (Builder $q) => $q->where('version_id', $filters['version_id']))
            ->when(! empty($filters['dimension']), fn (Builder $q) => $q->whereHas('dimension', fn (Builder $d) => $d->where('code', strtoupper((string) $filters['dimension']))))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $term = '%'.addcslashes((string) $filters['search'], '%_\\').'%';
                $q->where(fn (Builder $w) => $w->where('stem', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->orderBy($sort, $direction)
            ->orderBy('id');
    }

    /** @param  array<string, mixed>  $data */
    public function store(AssessmentVersion $version, array $data): AssessmentItem
    {
        $this->config->assertDraft($version);
        $dimension = $this->dimensionInVersion($version, (string) $data['dimension_id']);

        $item = AssessmentItem::query()->create([
            'version_id' => $version->getKey(),
            'dimension_id' => $dimension->getKey(),
            'code' => $this->normalizeCode($version, $data['code'] ?? null),
            'question_type' => $data['question_type'] ?? AssessmentQuestionType::Likert->value,
            'status' => AssessmentItemStatus::Draft->value,
            'stem' => trim((string) $data['stem']),
            'is_reversed' => (bool) ($data['is_reversed'] ?? false),
            'sort_order' => $data['sort_order'] ?? ((int) AssessmentItem::query()->where('version_id', $version->getKey())->max('sort_order') + 1),
        ]);

        $this->history->action($item, AuditAction::Created, ['event' => 'item_created', 'version_id' => $version->getKey()]);

        return $item;
    }

    /** @param  array<string, mixed>  $data */
    public function update(AssessmentItem $item, array $data): AssessmentItem
    {
        $version = $item->version;
        $this->config->assertDraft($version);
        $this->assertNotInUse($item);

        $changes = [];

        if (array_key_exists('dimension_id', $data)) {
            $changes['dimension_id'] = $this->dimensionInVersion($version, (string) $data['dimension_id'])->getKey();
        }

        if (array_key_exists('code', $data)) {
            $changes['code'] = $this->normalizeCode($version, $data['code'], $item);
        }

        if (array_key_exists('stem', $data)) {
            $changes['stem'] = trim((string) $data['stem']);
        }

        foreach (['is_reversed', 'sort_order'] as $field) {
            if (array_key_exists($field, $data)) {
                $changes[$field] = $data[$field];
            }
        }

        $old = array_intersect_key($item->getAttributes(), $changes);
        $item->forceFill($changes)->save();
        $this->history->change($item, $old, array_intersect_key($item->getAttributes(), $changes));

        return $item->refresh();
    }

    public function delete(AssessmentItem $item): void
    {
        $this->config->assertDraft($item->version);
        $this->assertNotInUse($item);

        $this->history->action($item, AuditAction::Deleted, ['event' => 'item_deleted']);
        $item->delete();
    }

    /** Salin butir ke versi draft lain untuk jenis asesmen yang sama. */
    public function copy(AssessmentItem $item, AssessmentVersion $target): AssessmentItem
    {
        $this->config->assertDraft($target);

        if ($item->version->assessment_type_id !== $target->assessment_type_id) {
            throw new DomainRuleException('Butir hanya dapat disalin ke versi dari jenis asesmen yang sama.', 'TYPE_MISMATCH', 422);
        }

        $dimension = $target->dimensions()->where('code', $item->dimension->code)->first();

        if ($dimension === null) {
            throw new DomainRuleException('Versi tujuan tidak memiliki dimensi '.$item->dimension->code.'.', 'DIMENSION_NOT_IN_VERSION', 422);
        }

        $copy = AssessmentItem::query()->create([
            'version_id' => $target->getKey(),
            'dimension_id' => $dimension->getKey(),
            'code' => $this->normalizeCode($target, $item->code !== null && ! $target->items()->where('code', $item->code)->exists() ? $item->code : null),
            'question_type' => $item->question_type->value,
            'status' => AssessmentItemStatus::Draft->value,
            'stem' => $item->stem,
            'is_reversed' => $item->is_reversed,
            'sort_order' => (int) AssessmentItem::query()->where('version_id', $target->getKey())->max('sort_order') + 1,
            'source_item_id' => $item->getKey(),
        ]);

        $this->history->action($copy, AuditAction::Created, ['event' => 'item_copied', 'source_item_id' => $item->getKey()]);

        return $copy;
    }

    /**
     * Pratinjau sebagaimana peserta melihatnya: hanya teks dan skala. Dimensi dan arah
     * butir tidak ditampilkan, sama seperti respons peserta.
     *
     * @return array{id: string, stem: string, scale: array{min: int, max: int}}
     */
    public function preview(AssessmentItem $item): array
    {
        $config = $item->version->scoring_config ?? [];

        return [
            'id' => $item->getKey(),
            'stem' => $item->stem,
            'scale' => [
                'min' => (int) ($config['scale_min'] ?? AssessmentConfigurationService::SCALE_MIN),
                'max' => (int) ($config['scale_max'] ?? AssessmentConfigurationService::SCALE_MAX),
            ],
        ];
    }

    private function assertNotInUse(AssessmentItem $item): void
    {
        if ($item->responses()->exists()) {
            throw new DomainRuleException('Butir ini sudah dijawab peserta dan tidak dapat diubah.', 'ITEM_IN_USE', 409);
        }
    }

    private function dimensionInVersion(AssessmentVersion $version, string $dimensionId): AssessmentDimension
    {
        $dimension = $version->dimensions()->whereKey($dimensionId)->first();

        if ($dimension === null) {
            throw new DomainRuleException('Dimensi tidak termasuk dalam versi ini.', 'DIMENSION_NOT_IN_VERSION', 422);
        }

        return $dimension;
    }

    /** Kode butir unik per versi. Kosong → dibuat berurutan (Q-001, Q-002, ...). */
    private function normalizeCode(AssessmentVersion $version, ?string $code, ?AssessmentItem $current = null): string
    {
        if ($code === null || trim($code) === '') {
            $next = (int) AssessmentItem::query()->where('version_id', $version->getKey())->count() + 1;
            $code = 'Q-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        }

        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z0-9][A-Z0-9._-]{0,29}$/', $code)) {
            throw new DomainRuleException('Kode pertanyaan hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung.', 'INVALID_CODE', 422);
        }

        $exists = AssessmentItem::query()
            ->where('version_id', $version->getKey())
            ->where('code', $code)
            ->when($current !== null, fn (Builder $q) => $q->whereKeyNot($current->getKey()))
            ->exists();

        if ($exists) {
            throw new DomainRuleException("Kode pertanyaan {$code} sudah dipakai pada versi ini.", 'CODE_DUPLICATE', 409);
        }

        return $code;
    }
}

<?php

namespace Modules\Admission\Services\Configuration;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\SchemeStatus;
use Modules\Admission\Models\SelectionComponent;
use Modules\Admission\Models\SelectionScheme;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Selection\ComponentDefinition;
use Modules\Admission\Selection\SelectionAllocator;
use Modules\Admission\Selection\SelectionCalculator;
use Modules\Admission\Support\HistoryRecorder;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Skema seleksi berversi (FR-CFG-11, FR-SEL-01, 06 §H.3): draft → locked →
 * retired; terkunci = immutable (BR-06), perubahan = versi baru.
 */
class SelectionSchemeService
{
    private const COMPONENT_FIELDS = ['code', 'name', 'source', 'score_type', 'raw_min', 'raw_max', 'weight', 'minimum_normalized', 'is_mandatory', 'tie_break_priority', 'normalization', 'rubric', 'config', 'sort_order'];

    public function __construct(private readonly HistoryRecorder $history) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, string $userId): SelectionScheme
    {
        $scheme = SelectionScheme::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => $data['name'],
            'version' => 1,
            'status' => SchemeStatus::Draft,
            'passing_score' => $data['passing_score'] ?? null,
            'allocation_method' => $data['allocation_method'] ?? 'score_priority',
            'tie_break_rules' => $data['tie_break_rules'] ?? ['use_verified_at' => true],
            'rounding_scale' => $data['rounding_scale'] ?? 2,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $this->history->change($scheme, [], ['code' => $scheme->code, 'version' => 1], null, AuditAction::Created);

        return $scheme;
    }

    /** @param  array<string, mixed>  $data */
    public function update(SelectionScheme $scheme, array $data, string $userId): SelectionScheme
    {
        $this->assertDraft($scheme);
        $values = Arr::only($data, ['name', 'passing_score', 'allocation_method', 'tie_break_rules', 'rounding_scale']);
        $old = $scheme->only(array_keys($values));
        $scheme->forceFill([...$values, 'updated_by' => $userId])->save();
        $this->history->change($scheme, $old, $values);

        return $scheme->refresh();
    }

    /** @param  array<string, mixed>  $data */
    public function saveComponent(SelectionScheme $scheme, ?SelectionComponent $component, array $data): SelectionComponent
    {
        $this->assertDraft($scheme);
        $values = Arr::only($data, self::COMPONENT_FIELDS);

        if (isset($values['code'])) {
            $values['code'] = strtoupper($values['code']);

            $duplicate = SelectionComponent::query()->where('selection_scheme_id', $scheme->getKey())->where('code', $values['code'])
                ->when($component, fn ($q) => $q->whereKeyNot($component->getKey()))->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'Kode komponen sudah dipakai di skema ini.']);
            }
        }

        if (($values['score_type'] ?? $component?->score_type?->value) === 'pass_fail') {
            $values['weight'] = 0;
        }

        return DB::transaction(function () use ($scheme, $component, $values): SelectionComponent {
            if ($component === null) {
                $component = SelectionComponent::query()->create([...$values, 'selection_scheme_id' => $scheme->getKey()]);
                $this->history->change($component, [], $values, null, AuditAction::Created);

                return $component;
            }

            $old = $component->only(array_keys($values));
            $component->forceFill($values)->save();
            $this->history->change($component, $old, $values);

            return $component->refresh();
        });
    }

    public function deleteComponent(SelectionScheme $scheme, SelectionComponent $component): void
    {
        $this->assertDraft($scheme);
        $this->history->change($component, $component->only(['code', 'weight']), [], null, AuditAction::Deleted);
        $component->delete();
    }

    /**
     * Validasi saat lock (06 §H.3): Σ bobot > 0; komponen numerik raw_max >
     * raw_min; passing_score 0–100; tie-break hanya merujuk komponen skema;
     * zscore_session memerlukan persetujuan panitia (`config.zscore_approved`).
     */
    public function lock(SelectionScheme $scheme, string $userId): SelectionScheme
    {
        $this->assertDraft($scheme);
        $components = $scheme->components()->get();
        $errors = $this->lockErrors($scheme, $components->all());

        if ($errors !== []) {
            throw ValidationException::withMessages(['scheme' => $errors]);
        }

        return DB::transaction(function () use ($scheme, $userId): SelectionScheme {
            $scheme->forceFill(['status' => SchemeStatus::Locked, 'locked_at' => now(), 'locked_by' => $userId, 'updated_by' => $userId])->save();
            $this->history->change($scheme, ['status' => 'draft'], ['status' => 'locked']);

            return $scheme->refresh();
        });
    }

    /**
     * @param  array<int, SelectionComponent>  $components
     * @return array<int, string>
     */
    public function lockErrors(SelectionScheme $scheme, array $components): array
    {
        $errors = [];

        if ($components === []) {
            $errors[] = 'Skema belum memiliki komponen.';
        }

        $weights = array_sum(array_map(fn ($c) => (float) $c->weight, $components));

        if ($weights <= 0) {
            $errors[] = 'Total bobot komponen harus lebih dari 0.';
        }

        foreach ($components as $component) {
            if ($component->score_type->value === 'numeric' && ((float) $component->raw_max <= (float) $component->raw_min)) {
                $errors[] = "Komponen {$component->code}: nilai maksimum harus lebih besar dari minimum.";
            }

            if ($component->normalization->value === 'zscore_session' && empty($component->config['zscore_approved'])) {
                $errors[] = "Komponen {$component->code}: normalisasi z-score antarsesi memerlukan persetujuan panitia (config.zscore_approved).";
            }

            if (in_array($component->source, [ComponentSource::Interview, ComponentSource::Portfolio], true) && ! empty($component->rubric)) {
                $criteria = $component->rubric['criteria'] ?? [];

                if ($criteria === [] || collect($criteria)->contains(fn ($c) => empty($c['key']) || ! isset($c['weight']))) {
                    $errors[] = "Komponen {$component->code}: rubrik harus berisi kriteria {key, weight}.";
                }
            }
        }

        if ($scheme->passing_score !== null && ((float) $scheme->passing_score < 0 || (float) $scheme->passing_score > 100)) {
            $errors[] = 'Ambang skor akhir harus 0–100.';
        }

        $codes = array_map(fn ($c) => $c->code, $components);

        foreach ((array) ($scheme->tie_break_rules['components'] ?? []) as $code) {
            if (! in_array($code, $codes, true)) {
                $errors[] = "Aturan tie-break merujuk komponen {$code} yang tidak ada di skema.";
            }
        }

        return $errors;
    }

    public function newVersion(SelectionScheme $scheme, string $userId): SelectionScheme
    {
        return DB::transaction(function () use ($scheme, $userId): SelectionScheme {
            $copy = SelectionScheme::query()->create([
                ...Arr::only($scheme->getAttributes(), ['code', 'name', 'passing_score', 'allocation_method', 'rounding_scale']),
                'tie_break_rules' => $scheme->tie_break_rules,
                'version' => (int) SelectionScheme::query()->where('code', $scheme->code)->max('version') + 1,
                'status' => SchemeStatus::Draft,
                'based_on_id' => $scheme->getKey(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($scheme->components as $component) {
                SelectionComponent::query()->create([
                    ...Arr::except($component->getAttributes(), ['id', 'selection_scheme_id', 'created_at', 'updated_at']),
                    'selection_scheme_id' => $copy->getKey(),
                ]);
            }

            $this->history->change($copy, [], ['code' => $copy->code, 'version' => $copy->version, 'based_on' => $scheme->getKey()], null, AuditAction::Created);

            return $copy->load('components');
        });
    }

    public function retire(SelectionScheme $scheme): SelectionScheme
    {
        if (WavePath::query()->where('selection_scheme_id', $scheme->getKey())->whereHas('wave', fn ($q) => $q->whereNotIn('status', ['completed']))->exists()) {
            throw new ConflictException('Skema masih dipakai gelombang aktif.', 'CONFIG_IN_USE');
        }

        $scheme->forceFill(['status' => SchemeStatus::Retired])->save();

        return $scheme;
    }

    /**
     * Simulasi dengan data contoh (FR-SEL-01, ADM-17) — tidak disimpan.
     *
     * @param  array<int, array{id: string, scores: array<string, mixed>, choices: array<int, array<string, mixed>>}>  $applicants
     * @param  array<string, array{capacity: int, min_final_score?: float|null, waitlist_capacity?: int}>  $quotas
     * @return array<string, mixed>
     */
    public function simulate(SelectionScheme $scheme, array $applicants, array $quotas): array
    {
        $definitions = $scheme->components()->get()->map(fn ($c) => ComponentDefinition::fromModel($c))->all();
        $calculator = new SelectionCalculator;
        $prepared = [];

        foreach ($applicants as $index => $row) {
            $scores = [];

            foreach ($row['scores'] as $code => $value) {
                $scores[$code] = is_array($value) ? $value : ['raw' => $value, 'passed' => is_bool($value) ? $value : null];
            }

            $evaluation = $calculator->evaluate($definitions, $scores, (int) $scheme->rounding_scale);
            $prepared[] = [
                'id' => (string) ($row['id'] ?? 'P'.($index + 1)),
                'eligible' => $evaluation['eligible'],
                'reason' => $evaluation['reason'],
                'final' => $evaluation['final'],
                'rounded' => $evaluation['rounded'],
                'normalized' => array_map(fn ($c) => $c['normalized'], $evaluation['components']),
                'components' => $evaluation['components'],
                'verified_at' => null,
                'choices' => array_map(fn ($c, $i) => ['choice_id' => ($row['id'] ?? $index).'-'.$i, 'quota_id' => (string) $c['quota'], 'priority' => (int) ($c['priority'] ?? $i + 1)], $row['choices'], array_keys($row['choices'])),
            ];
        }

        $quotaInput = [];

        foreach ($quotas as $key => $quota) {
            $quotaInput[(string) $key] = [
                'remaining' => (int) $quota['capacity'],
                'threshold' => (float) ($quota['min_final_score'] ?? $scheme->passing_score ?? 0),
                'waitlist_capacity' => (int) ($quota['waitlist_capacity'] ?? 0),
            ];
        }

        $tieComponents = $this->tieBreakComponents($scheme);
        $allocation = (new SelectionAllocator)->allocate($prepared, $quotaInput, $tieComponents, false, [], $scheme->allocation_method->value);

        return [
            'applicants' => array_map(fn ($p) => [
                'id' => $p['id'],
                'components' => $p['components'],
                'final_score' => $p['final'],
                'final_score_rounded' => $p['rounded'],
                'eligible' => $p['eligible'],
                ...$allocation['results'][$p['id']],
            ], $prepared),
            'has_unresolved_ties' => $allocation['has_unresolved_ties'],
        ];
    }

    /** @return array<int, string> */
    public function tieBreakComponents(SelectionScheme $scheme): array
    {
        $explicit = (array) ($scheme->tie_break_rules['components'] ?? []);

        if ($explicit !== []) {
            return array_values($explicit);
        }

        return $scheme->components()->whereNotNull('tie_break_priority')->orderBy('tie_break_priority')->pluck('code')->all();
    }

    private function assertDraft(SelectionScheme $scheme): void
    {
        if ($scheme->status !== SchemeStatus::Draft) {
            throw new ConflictException('Skema yang sudah dikunci tidak dapat diubah; buat versi baru (BR-06).', 'CONFIG_IN_USE');
        }
    }
}

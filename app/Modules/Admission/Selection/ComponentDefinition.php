<?php

namespace Modules\Admission\Selection;

use Modules\Admission\Models\SelectionComponent;

/** Definisi komponen skema yang dibekukan untuk satu perhitungan (snapshot). */
final class ComponentDefinition
{
    public function __construct(
        public readonly string $code,
        public readonly string $source,
        public readonly string $scoreType,
        public readonly ?float $rawMin,
        public readonly ?float $rawMax,
        public readonly float $weight,
        public readonly ?float $minimumNormalized,
        public readonly bool $isMandatory,
        public readonly ?int $tieBreakPriority,
        public readonly string $normalization = 'minmax',
        public readonly ?string $id = null,
        public readonly array $config = [],
    ) {}

    public static function fromModel(SelectionComponent $component): self
    {
        return new self(
            code: $component->code,
            source: $component->source->value,
            scoreType: $component->score_type->value,
            rawMin: $component->raw_min !== null ? (float) $component->raw_min : null,
            rawMax: $component->raw_max !== null ? (float) $component->raw_max : null,
            weight: (float) $component->weight,
            minimumNormalized: $component->minimum_normalized !== null ? (float) $component->minimum_normalized : null,
            isMandatory: $component->is_mandatory,
            tieBreakPriority: $component->tie_break_priority,
            normalization: $component->normalization->value,
            id: (string) $component->getKey(),
            config: $component->config ?? [],
        );
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) $data['code'],
            source: (string) ($data['source'] ?? 'written_exam'),
            scoreType: (string) ($data['score_type'] ?? 'numeric'),
            rawMin: isset($data['raw_min']) ? (float) $data['raw_min'] : null,
            rawMax: isset($data['raw_max']) ? (float) $data['raw_max'] : null,
            weight: (float) ($data['weight'] ?? 0),
            minimumNormalized: isset($data['minimum_normalized']) ? (float) $data['minimum_normalized'] : null,
            isMandatory: (bool) ($data['is_mandatory'] ?? true),
            tieBreakPriority: isset($data['tie_break_priority']) ? (int) $data['tie_break_priority'] : null,
            normalization: (string) ($data['normalization'] ?? 'minmax'),
            id: $data['id'] ?? null,
            config: $data['config'] ?? [],
        );
    }

    public function isNumeric(): bool
    {
        return $this->scoreType === 'numeric';
    }
}

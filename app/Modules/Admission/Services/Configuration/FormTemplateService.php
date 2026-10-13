<?php

namespace Modules\Admission\Services\Configuration;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\TemplateStatus;
use Modules\Admission\Models\FormField;
use Modules\Admission\Models\FormTemplate;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Support\CoreFormFields;
use Modules\Admission\Support\FormRuleEvaluator;
use Modules\Admission\Support\HistoryRecorder;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Template formulir berversi (FR-CFG-10, BR-06): template `published` tidak
 * dapat diedit; "versi baru" menyalin ke draft versi+1. Mesin field dinamis
 * 04 §D.3 (validasi allowlist, regex aman ReDoS).
 */
class FormTemplateService
{
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'date', 'select', 'multiselect', 'radio', 'checkbox', 'email', 'phone', 'url', 'file_ref'];

    private const VALIDATION_KEYS = ['min', 'max', 'min_length', 'max_length', 'regex', 'before', 'after'];

    public function __construct(private readonly HistoryRecorder $history) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, string $userId): FormTemplate
    {
        $rules = $this->validateCoreRules($data['core_field_rules'] ?? []);

        $template = FormTemplate::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => $data['name'],
            'version' => 1,
            'status' => TemplateStatus::Draft,
            'core_field_rules' => $rules,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->history->change($template, [], ['code' => $template->code, 'version' => 1], null, AuditAction::Created);

        return $template;
    }

    /** @param  array<string, mixed>  $data */
    public function update(FormTemplate $template, array $data, string $userId): FormTemplate
    {
        $this->assertDraft($template);
        $values = Arr::only($data, ['name']);

        if (array_key_exists('core_field_rules', $data)) {
            $values['core_field_rules'] = $this->validateCoreRules($data['core_field_rules']);
        }

        $old = $template->only(array_keys($values));
        $template->forceFill([...$values, 'updated_by' => $userId])->save();
        $this->history->change($template, $old, $values);

        return $template->refresh();
    }

    /** @param  array<string, mixed>  $data */
    public function saveField(FormTemplate $template, ?FormField $field, array $data): FormField
    {
        $this->assertDraft($template);
        $values = $this->validateField($template, $data, $field);

        return DB::transaction(function () use ($template, $field, $values): FormField {
            if ($field === null) {
                $field = FormField::query()->create([...$values, 'form_template_id' => $template->getKey()]);
                $this->history->change($field, [], $values, null, AuditAction::Created);

                return $field;
            }

            $old = $field->only(array_keys($values));
            $field->forceFill($values)->save();
            $this->history->change($field, $old, $values);

            return $field->refresh();
        });
    }

    public function deleteField(FormTemplate $template, FormField $field): void
    {
        $this->assertDraft($template);
        DB::transaction(function () use ($field): void {
            $this->history->change($field, $field->only(['key', 'label']), [], null, AuditAction::Deleted);
            $field->delete();
        });
    }

    public function publish(FormTemplate $template, string $userId): FormTemplate
    {
        $this->assertDraft($template);

        return DB::transaction(function () use ($template, $userId): FormTemplate {
            $template->forceFill(['status' => TemplateStatus::Published, 'published_at' => now(), 'updated_by' => $userId])->save();
            $this->history->change($template, ['status' => 'draft'], ['status' => 'published']);

            return $template->refresh();
        });
    }

    public function retire(FormTemplate $template, string $userId): FormTemplate
    {
        if (WavePath::query()->where('form_template_id', $template->getKey())->whereHas('wave', fn ($q) => $q->whereIn('status', ['scheduled', 'open']))->exists()) {
            throw new ConflictException('Template masih dipakai gelombang yang dibuka.', 'CONFIG_IN_USE');
        }

        $template->forceFill(['status' => TemplateStatus::Retired, 'updated_by' => $userId])->save();
        $this->history->change($template, ['status' => 'published'], ['status' => 'retired']);

        return $template;
    }

    public function newVersion(FormTemplate $template, string $userId): FormTemplate
    {
        return DB::transaction(function () use ($template, $userId): FormTemplate {
            $maxVersion = (int) FormTemplate::query()->where('code', $template->code)->max('version');
            $copy = FormTemplate::query()->create([
                'code' => $template->code,
                'name' => $template->name,
                'version' => $maxVersion + 1,
                'status' => TemplateStatus::Draft,
                'core_field_rules' => $template->core_field_rules,
                'based_on_id' => $template->getKey(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($template->fields as $field) {
                FormField::query()->create([...Arr::except($field->getAttributes(), ['id', 'form_template_id', 'created_at', 'updated_at']), 'form_template_id' => $copy->getKey()]);
            }

            $this->history->change($copy, [], ['code' => $copy->code, 'version' => $copy->version, 'based_on' => $template->getKey()], null, AuditAction::Created);

            return $copy->load('fields');
        });
    }

    /**
     * Skema yang dikirim ke FormRenderer pendaftar (ADM-16 pratinjau, POR-04).
     *
     * @return array<string, mixed>
     */
    public function schema(FormTemplate $template, string $stage = 'application'): array
    {
        $rules = $template->core_field_rules ?? [];
        $core = [];

        foreach (CoreFormFields::FIELDS as $section => $fields) {
            foreach (array_keys($fields) as $key) {
                $core[$section][$key] = CoreFormFields::requirement($key, $rules);
            }
        }

        return [
            'template' => ['id' => $template->getKey(), 'code' => $template->code, 'version' => $template->version, 'name' => $template->name],
            'core' => $core,
            'fields' => $template->fields()->where('stage', $stage)->get()->map(fn (FormField $f) => [
                'id' => $f->getKey(),
                'section' => $f->section,
                'key' => $f->key,
                'label' => $f->label,
                'help_text' => $f->help_text,
                'placeholder' => $f->placeholder,
                'type' => $f->type,
                'options' => $f->options,
                'is_required' => $f->is_required,
                'validation' => $f->validation,
                'visibility_rule' => $f->visibility_rule,
                'is_sensitive' => $f->is_sensitive,
                'sort_order' => $f->sort_order,
            ])->values()->all(),
        ];
    }

    private function assertDraft(FormTemplate $template): void
    {
        if ($template->status !== TemplateStatus::Draft) {
            throw new ConflictException('Template yang sudah dipublikasikan tidak dapat diedit; buat versi baru (BR-06).', 'CONFIG_IN_USE');
        }
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, string>
     */
    private function validateCoreRules(array $rules): array
    {
        $clean = [];

        foreach ($rules as $field => $requirement) {
            if (! in_array($field, CoreFormFields::keys(), true) || ! in_array($requirement, ['required', 'optional', 'hidden'], true)) {
                throw ValidationException::withMessages(['core_field_rules' => "Aturan field inti '{$field}' tidak valid."]);
            }

            if (in_array($field, ['full_name', 'nationality', 'birth_date', 'choices', 'primary_education_id'], true) && $requirement !== 'required') {
                throw ValidationException::withMessages(['core_field_rules' => "Field '{$field}' selalu wajib."]);
            }

            $clean[$field] = $requirement;
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateField(FormTemplate $template, array $data, ?FormField $current): array
    {
        $key = (string) ($data['key'] ?? $current?->key);
        $errors = [];

        if (! preg_match('/^[a-z][a-z0-9_]{1,59}$/', $key)) {
            $errors['key'] = 'Kunci field harus snake_case (huruf kecil, angka, garis bawah).';
        } elseif (in_array($key, CoreFormFields::keys(), true)) {
            $errors['key'] = 'Kunci field tidak boleh sama dengan field inti.';
        } elseif (FormField::query()->where('form_template_id', $template->getKey())->where('key', $key)->when($current, fn ($q) => $q->whereKeyNot($current->getKey()))->exists()) {
            $errors['key'] = 'Kunci field sudah dipakai di template ini.';
        }

        $type = (string) ($data['type'] ?? $current?->type);

        if (! in_array($type, self::FIELD_TYPES, true)) {
            $errors['type'] = 'Tipe field tidak dikenal.';
        }

        $section = (string) ($data['section'] ?? $current?->section);

        if (! in_array($section, ['identity', 'education', 'choices', 'supporting'], true) && ! preg_match('/^custom_[a-z0-9_]{1,30}$/', $section)) {
            $errors['section'] = 'Seksi harus identity/education/choices/supporting atau custom_<nama>.';
        }

        $options = $data['options'] ?? $current?->options;

        if (in_array($type, ['select', 'multiselect', 'radio'], true)) {
            if (! is_array($options) || $options === [] || collect($options)->contains(fn ($o) => ! isset($o['value'], $o['label']))) {
                $errors['options'] = 'Field pilihan wajib memiliki opsi {value, label}.';
            }
        }

        $validation = $data['validation'] ?? $current?->validation;

        if ($validation !== null) {
            foreach ((array) $validation as $rule => $value) {
                if (! in_array($rule, self::VALIDATION_KEYS, true)) {
                    $errors['validation'] = "Aturan validasi '{$rule}' tidak diizinkan.";
                }
            }

            if (isset($validation['regex']) && ! $this->isSafeRegex((string) $validation['regex'])) {
                $errors['validation'] = 'Regex tidak valid atau berisiko (panjang ≤ 200, tanpa quantifier bersarang).';
            }
        }

        $visibility = $data['visibility_rule'] ?? $current?->visibility_rule;

        if (! FormRuleEvaluator::isValid($visibility)) {
            $errors['visibility_rule'] = 'Aturan tampil tidak valid.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'section' => $section,
            'key' => $key,
            'label' => (string) ($data['label'] ?? $current?->label),
            'help_text' => $data['help_text'] ?? $current?->help_text,
            'placeholder' => $data['placeholder'] ?? $current?->placeholder,
            'type' => $type,
            'options' => in_array($type, ['select', 'multiselect', 'radio'], true) ? array_values($options) : null,
            'is_required' => (bool) ($data['is_required'] ?? $current?->is_required ?? false),
            'validation' => $validation ?: null,
            'visibility_rule' => $visibility ?: null,
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? $current?->is_sensitive ?? false),
            'is_reportable' => (bool) ($data['is_reportable'] ?? $current?->is_reportable ?? false),
            'stage' => $data['stage'] ?? $current?->stage?->value ?? 'application',
            'sort_order' => (int) ($data['sort_order'] ?? $current?->sort_order ?? 0),
        ];
    }

    private function isSafeRegex(string $pattern): bool
    {
        if ($pattern === '' || mb_strlen($pattern) > 200) {
            return false;
        }

        // Tolak quantifier bersarang yang menjadi sumber ReDoS klasik: (a+)+, (a*)*, (a|aa)+.
        if (preg_match('/\([^)]*[+*][^)]*\)\s*[+*{]/', $pattern) || preg_match('/\([^)]*\|[^)]*\)\s*[+*]/', $pattern)) {
            return false;
        }

        return @preg_match('/'.str_replace('/', '\/', $pattern).'/u', '') !== false;
    }
}

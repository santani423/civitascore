<?php

namespace Modules\Admission\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Models\Voucher;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\NimFormatter;
use Modules\Admission\Support\PmbSettings;
use Modules\AuditLog\Enums\AuditAction;
use Modules\SystemSetting\Enums\SettingValueType;

/** Pengaturan PMB per tenant (FR-CFG-12), format NIM (BR-36), voucher. */
class SettingsController extends AdminController
{
    /** Kunci yang hanya boleh diubah lewat endpoint khusus (izin berbeda). */
    private const DEDICATED_KEYS = ['pmb.nim_format'];

    public function __construct(private readonly PmbSettings $settings, private readonly HistoryRecorder $history) {}

    public function show(): JsonResponse
    {
        $values = $this->settings->all();

        return ApiResponse::success(collect(PmbSettings::DEFINITIONS)->except(self::DEDICATED_KEYS)->map(fn ($def, $key) => [
            'key' => $key,
            'type' => $def[0]->value,
            'default' => $def[1],
            'description' => $def[2],
            'value' => $values[$key],
        ])->values());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['values' => ['required', 'array', 'min:1']]);
        $values = $data['values'];
        $unknown = array_diff(array_keys($values), array_keys(PmbSettings::DEFINITIONS));

        if ($unknown !== [] || array_intersect(array_keys($values), self::DEDICATED_KEYS) !== []) {
            throw ValidationException::withMessages(['values' => 'Kunci pengaturan tidak dikenal atau harus diubah lewat menu khusus: '.implode(', ', [...$unknown, ...array_intersect(array_keys($values), self::DEDICATED_KEYS)])]);
        }

        // Kunci berisi titik (pmb.x.y) di-escape agar validator membacanya sebagai kunci literal.
        $rules = [];

        foreach (array_keys($values) as $key) {
            $rules[str_replace('.', '\.', (string) $key)] = $this->ruleFor((string) $key);
        }

        Validator::make($values, $rules)->validate();

        $old = array_intersect_key($this->settings->all(), $values);
        $this->settings->update($values, (string) $this->user($request)->getKey());
        $this->history->recordSettings($old, $values);

        return $this->show();
    }

    public function nim(): JsonResponse
    {
        return ApiResponse::success(['format' => $this->settings->get('pmb.nim_format'), 'tokens' => [...NimFormatter::TOKENS, 'SEQ:n']]);
    }

    public function updateNim(Request $request): JsonResponse
    {
        $data = $request->validate(['format' => ['required', 'string', 'max:60']]);
        NimFormatter::validate($data['format']);
        $old = ['pmb.nim_format' => $this->settings->get('pmb.nim_format')];
        $this->settings->update(['pmb.nim_format' => $data['format']], (string) $this->user($request)->getKey());
        $this->history->recordSettings($old, ['pmb.nim_format' => $data['format']]);

        return $this->nim();
    }

    public function previewNim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'format' => ['required', 'string', 'max:60'],
            'program' => ['nullable', 'string', 'max:20'],
            'path' => ['nullable', 'string', 'max:20'],
            'system' => ['nullable', 'string', 'max:20'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        NimFormatter::validate($data['format']);
        $values = ['year' => $data['year'] ?? (int) now()->format('Y'), 'program' => $data['program'] ?? 'TI', 'path' => $data['path'] ?? 'REG', 'system' => $data['system'] ?? 'R'];

        return ApiResponse::success([
            'samples' => [NimFormatter::render($data['format'], $values, 1), NimFormatter::render($data['format'], $values, 2), NimFormatter::render($data['format'], $values, 125)],
            'sequence_key' => NimFormatter::sequenceKey($data['format'], $values),
        ]);
    }

    // ------------------------------------------------------------------ Voucher

    public function vouchers(Request $request): JsonResponse
    {
        return $this->paginate(Voucher::query()->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.strtoupper($request->string('search')).'%'))->orderByDesc('created_at'), $request);
    }

    public function storeVoucher(Request $request): JsonResponse
    {
        $data = $this->validateVoucher($request, null);
        $voucher = Voucher::query()->create([...$data, 'code' => strtoupper($data['code']), 'used_count' => 0, 'created_by' => $this->user($request)->getKey()]);
        $this->history->change($voucher, [], $voucher->only(['code', 'discount_type', 'discount_value']), null, AuditAction::Created);

        return ApiResponse::success($voucher, 'Voucher dibuat.', status: 201);
    }

    public function updateVoucher(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $this->validateVoucher($request, $voucher);

        if ($voucher->used_count > 0 && array_intersect(array_keys($data), ['code', 'discount_type', 'discount_value', 'applies_to']) !== []) {
            throw new ConflictException('Voucher yang sudah dipakai hanya boleh diubah masa berlaku/kuota/status.', 'CONFIG_IN_USE');
        }

        $old = $voucher->only(array_keys($data));
        $voucher->forceFill(isset($data['code']) ? [...$data, 'code' => strtoupper($data['code'])] : $data)->save();
        $this->history->change($voucher, $old, $data);

        return ApiResponse::success($voucher->refresh(), 'Voucher diperbarui.');
    }

    public function destroyVoucher(Voucher $voucher): JsonResponse
    {
        if ($voucher->used_count > 0) {
            throw new ConflictException('Voucher sudah dipakai; nonaktifkan saja.', 'CONFIG_IN_USE');
        }

        $voucher->delete();

        return ApiResponse::success(null, 'Voucher dihapus.');
    }

    /** @return array<string, mixed> */
    private function validateVoucher(Request $request, ?Voucher $current): array
    {
        $req = $current ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$req, 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('pmb_vouchers', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->getKey())],
            'name' => [$req, 'string', 'max:150'],
            'applies_to' => [$req, Rule::in(['registration_fee', 'initial_tuition'])],
            'discount_type' => [$req, Rule::in(['percent', 'fixed'])],
            'discount_value' => [$req, 'numeric', 'min:0.01', $request->input('discount_type', $current?->discount_type) === 'percent' ? 'max:100' : 'max:999999999'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'scope' => ['nullable', 'array'],
            'scope.wave_path_ids' => ['nullable', 'array'],
            'scope.admission_path_ids' => ['nullable', 'array'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /** @return array<int, mixed> */
    private function ruleFor(string $key): array
    {
        $type = PmbSettings::DEFINITIONS[$key][0];

        return match (true) {
            $key === 'pmb.enrollment_min_payment' => ['required', Rule::in(['full', 'first_installment'])],
            $key === 'pmb.otp_channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
            $key === 'pmb.verification_mode' => ['required', Rule::in(['pull', 'push'])],
            $key === 'pmb.cbt.provider' => ['required', Rule::in(['internal', 'external'])],
            $key === 'pmb.institution_email_domain' => ['nullable', 'string', 'max:100', 'regex:/^@?[a-z0-9.-]*$/i'],
            $type === SettingValueType::Integer => ['required', 'integer', 'min:0', 'max:100000'],
            $type === SettingValueType::Boolean => ['required', 'boolean'],
            $type === SettingValueType::Json => ['present', 'array'],
            default => ['nullable', 'string', 'max:500'],
        };
    }
}

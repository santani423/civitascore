<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Student;
use Modules\FileManagement\Support\FileAttacher;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\AidCalcType;
use Modules\Finance\Enums\AidKind;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\VoucherStatus;
use Modules\Finance\Models\DiscountPolicy;
use Modules\Finance\Models\FinancialAidAward;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Voucher;
use Modules\Finance\Models\VoucherRedemption;
use Modules\Finance\Services\AidService;
use Modules\Finance\Services\VoucherService;
use Modules\Finance\Support\Money;
use Modules\Finance\Support\Presenter;

/** Beasiswa, subsidi, potongan, KIP (F-05) & voucher (F-06). */
class AidController extends KeuController
{
    public function __construct(private readonly AidService $aid, private readonly VoucherService $vouchers) {}

    public function policies(): JsonResponse
    {
        return ApiResponse::success(DiscountPolicy::query()->with('scholarship')->withCount(['awards as active_awards' => fn ($q) => $q->where('status', 'active')])->orderBy('priority')->get()->map(fn (DiscountPolicy $p) => [
            ...$p->only(['id', 'code', 'name', 'funder_name', 'scholarship_id', 'fee_component_ids', 'priority', 'stackable', 'description', 'is_active', 'active_awards']),
            'kind' => $p->kind->value, 'kind_label' => $p->kind->label(), 'calc_type' => $p->calc_type->value, 'calc_type_label' => $p->calc_type->label(),
            'value' => (string) $p->value, 'max_amount' => $p->max_amount !== null ? (string) $p->max_amount : null, 'scholarship' => $p->scholarship?->name,
        ]));
    }

    public function storePolicy(Request $request): JsonResponse
    {
        $policy = DiscountPolicy::query()->create([...$this->validatePolicy($request), 'created_by' => $this->user($request)->id]);

        return ApiResponse::success($policy, 'Jenis beasiswa/potongan dibuat.', status: 201);
    }

    public function updatePolicy(Request $request, DiscountPolicy $discountPolicy): JsonResponse
    {
        $discountPolicy->update($this->validatePolicy($request, $discountPolicy));

        return ApiResponse::success($discountPolicy->fresh(), 'Jenis beasiswa/potongan diperbarui. Perubahan berlaku untuk penetapan & tagihan baru.');
    }

    public function awards(Request $request): JsonResponse
    {
        $query = FinancialAidAward::query()->with(['student.studyProgram', 'policy', 'startTerm', 'endTerm'])->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('discount_policy_id'), fn ($q) => $q->where('discount_policy_id', $request->string('discount_policy_id')))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->string('student_id')))
            ->when($request->filled('search'), fn ($q) => $q->whereIn('student_id', Student::query()->select('id')->where(fn ($s) => $s->where('nim', 'like', '%'.$request->string('search').'%')->orWhere('name', 'like', '%'.$request->string('search').'%'))));

        return $this->paginate($query, $request, fn (FinancialAidAward $a) => Presenter::award($a));
    }

    public function storeAward(Request $request, FileAttacher $files): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
            'discount_policy_id' => ['required', 'string'],
            'calc_type' => ['nullable', Rule::enum(AidCalcType::class)],
            'value' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_component_ids' => ['nullable', 'array'],
            'start_term_id' => ['nullable', 'string', Rule::exists('academic_terms', 'id')],
            'end_term_id' => ['nullable', 'string', Rule::exists('academic_terms', 'id')],
            'basis' => ['required', 'string', 'min:5', 'max:2000'],
            'evidence_file_id' => ['nullable', 'string'],
        ]);
        $this->assertPercent($data['calc_type'] ?? null, $data['value'] ?? null);
        $file = $files->resolve($data['evidence_file_id'] ?? null, 'evidence_file_id', $this->user($request), null, ['pdf', 'jpg', 'jpeg', 'png'], 5);
        $award = $this->aid->request(Student::query()->findOrFail($data['student_id']), DiscountPolicy::query()->findOrFail($data['discount_policy_id']),
            array_filter($data, fn ($v, $k) => in_array($k, ['calc_type', 'value', 'max_amount', 'fee_component_ids', 'start_term_id', 'end_term_id'], true) && $v !== null, ARRAY_FILTER_USE_BOTH),
            $data['basis'], $this->user($request), $file?->id);
        $files->attach($file, $award);

        return ApiResponse::success(Presenter::award($award->load(['student', 'policy'])), 'Penetapan diajukan dan menunggu persetujuan.', status: 201);
    }

    public function approveAward(Request $request, FinancialAidAward $financialAidAward): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return ApiResponse::success(Presenter::award($this->aid->approve($financialAidAward, $this->user($request), $data['note'] ?? null)), 'Penetapan disetujui.');
    }

    public function rejectAward(Request $request, FinancialAidAward $financialAidAward): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']]);

        return ApiResponse::success(Presenter::award($this->aid->reject($financialAidAward, $this->user($request), $data['note'])), 'Penetapan ditolak.');
    }

    public function revokeAward(Request $request, FinancialAidAward $financialAidAward): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        return ApiResponse::success(Presenter::award($this->aid->revoke($financialAidAward, $this->user($request), $data['reason'])), 'Penetapan dicabut.');
    }

    public function applyAward(Request $request, FinancialAidAward $financialAidAward): JsonResponse
    {
        $data = $request->validate(['invoice_id' => ['required', 'string']]);
        $adjustment = $this->aid->applyToInvoice($financialAidAward, Invoice::query()->findOrFail($data['invoice_id']), $this->user($request));

        return ApiResponse::success(Presenter::adjustment($adjustment), 'Penyesuaian potongan diajukan (menunggu persetujuan).', status: 201);
    }

    public function vouchers(Request $request): JsonResponse
    {
        $query = Voucher::query()->latest()->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.strtoupper($request->string('search')).'%'));

        return $this->paginate($query, $request, fn (Voucher $v) => $this->presentVoucher($v));
    }

    public function storeVoucher(Request $request): JsonResponse
    {
        $data = $this->validateVoucher($request);
        $voucher = Voucher::query()->create([...$data, 'code' => strtoupper($data['code']), 'created_by' => $this->user($request)->id]);

        return ApiResponse::success($this->presentVoucher($voucher), 'Voucher dibuat.', status: 201);
    }

    public function updateVoucher(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $this->validateVoucher($request, $voucher);

        if ($voucher->quota_used > 0) {
            // Ketentuan potongan tidak boleh berubah setelah dipakai; hanya status/masa berlaku/kuota.
            $data = array_intersect_key($data, array_flip(['name', 'status', 'valid_until', 'quota_total']));
        }

        $voucher->update(isset($data['code']) ? [...$data, 'code' => strtoupper($data['code'])] : $data);

        return ApiResponse::success($this->presentVoucher($voucher->fresh()), 'Voucher diperbarui.');
    }

    public function redemptions(Voucher $voucher): JsonResponse
    {
        return ApiResponse::success(VoucherRedemption::query()->where('voucher_id', $voucher->id)->with(['student', 'invoice'])->latest()->limit(500)->get()->map(fn (VoucherRedemption $r) => [
            'id' => $r->id, 'student' => Presenter::student($r->student), 'invoice_number' => $r->invoice?->invoice_number, 'invoice_id' => $r->invoice_id,
            'discount_amount' => (string) $r->discount_amount, 'status' => $r->status->value, 'status_label' => $r->status->label(), 'created_at' => $r->created_at?->toIso8601String(),
        ]));
    }

    public function redeemForStudent(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'between:4,30']]);
        $redemption = $this->vouchers->redeem($invoice, $data['code'], $this->user($request));

        return ApiResponse::success(['id' => $redemption->id, 'discount_amount' => (string) $redemption->discount_amount], 'Voucher diterapkan: potongan '.Money::format($redemption->discount_amount).'.');
    }

    public function reverseRedemption(Request $request, VoucherRedemption $voucherRedemption): JsonResponse
    {
        $this->vouchers->reverse($voucherRedemption, $this->user($request));

        return ApiResponse::success(null, 'Penukaran voucher dibatalkan.');
    }

    /** @return array<string, mixed> */
    private function presentVoucher(Voucher $v): array
    {
        $now = now();
        $state = match (true) {
            $v->status !== VoucherStatus::Active => $v->status->label(),
            $now->greaterThan($v->valid_until) => 'Kedaluwarsa',
            $now->lessThan($v->valid_from) => 'Belum berlaku',
            $v->quota_total !== null && $v->quota_used >= $v->quota_total => 'Kuota habis',
            default => 'Aktif',
        };

        return [
            ...$v->only(['id', 'code', 'name', 'bill_groups', 'scope', 'quota_total', 'quota_used', 'per_student_limit']),
            'discount_type' => $v->discount_type->value, 'discount_type_label' => $v->discount_type->label(), 'discount_value' => (string) $v->discount_value,
            'max_discount' => $v->max_discount !== null ? (string) $v->max_discount : null, 'min_invoice_amount' => $v->min_invoice_amount !== null ? (string) $v->min_invoice_amount : null,
            'valid_from' => $v->valid_from?->toIso8601String(), 'valid_until' => $v->valid_until?->toIso8601String(), 'status' => $v->status->value, 'state_label' => $state,
        ];
    }

    /** @return array<string, mixed> */
    private function validatePolicy(Request $request, ?DiscountPolicy $policy = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('discount_policies', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($policy?->id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'kind' => ['required', Rule::enum(AidKind::class)],
            'funder_name' => ['nullable', 'string', 'max:150'],
            'scholarship_id' => ['nullable', 'string', Rule::exists('scholarships', 'id')],
            'calc_type' => ['required', Rule::enum(AidCalcType::class)],
            'value' => ['required_unless:calc_type,full', 'nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'fee_component_ids' => ['nullable', 'array'],
            'priority' => ['required', 'integer', 'between:1,9'],
            'stackable' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);
        $this->assertPercent($data['calc_type'], $data['value'] ?? null);
        $data['code'] = strtoupper($data['code']);
        $data['value'] = $data['value'] ?? 0;

        return $data;
    }

    /** @return array<string, mixed> */
    private function validateVoucher(Request $request, ?Voucher $voucher = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'between:4,30', 'regex:/^[A-Za-z0-9\-]+$/', Rule::unique('vouchers', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($voucher?->id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', Rule::enum(AidCalcType::class)],
            'discount_value' => ['required_unless:discount_type,full', 'nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'bill_groups' => ['nullable', 'array'],
            'bill_groups.*' => [Rule::enum(BillGroup::class)],
            'scope' => ['nullable', 'array'],
            'quota_total' => ['nullable', 'integer', 'min:1'],
            'per_student_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'min_invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'status' => ['required', Rule::enum(VoucherStatus::class)],
        ]);
        $this->assertPercent($data['discount_type'], $data['discount_value'] ?? null);
        $data['discount_value'] = $data['discount_value'] ?? 0;

        return $data;
    }

    private function assertPercent(?string $type, mixed $value): void
    {
        if ($type === AidCalcType::Percent->value && ($value === null || (float) $value <= 0 || (float) $value > 100)) {
            abort(422, 'Persentase potongan harus antara 0 dan 100.');
        }
    }
}

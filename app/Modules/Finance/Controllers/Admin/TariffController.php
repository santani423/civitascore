<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\CalcBasis;
use Modules\Finance\Enums\FeeCategory;
use Modules\Finance\Enums\RateTableStatus;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\FeeRate;
use Modules\Finance\Models\FeeRateTable;
use Modules\Finance\Services\BillingService;
use Modules\Finance\Services\TariffResolver;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\Money;

/** Komponen biaya & tabel tarif berversi (F-01/F-02, BR-KEU-01..05). */
class TariffController extends KeuController
{
    public function __construct(private readonly TariffResolver $tariffs, private readonly AuditLogService $audit) {}

    public function components(Request $request): JsonResponse
    {
        return ApiResponse::success(FeeComponent::query()->orderBy('code')->get()->map(fn (FeeComponent $c) => [
            ...$c->only(['id', 'code', 'name', 'billing_frequency', 'is_mandatory', 'installable', 'discountable', 'refundable', 'counts_for_clearance', 'pddikti_category', 'gl_account_code', 'description', 'is_active']),
            'category' => $c->category->value, 'category_label' => $c->category->label(),
        ]));
    }

    public function storeComponent(Request $request): JsonResponse
    {
        $data = $this->validateComponent($request);
        $component = FeeComponent::query()->create([...$data, 'code' => strtoupper($data['code']), 'created_by' => $this->user($request)->id]);

        return ApiResponse::success($component, 'Komponen biaya dibuat.', status: 201);
    }

    public function updateComponent(Request $request, FeeComponent $feeComponent): JsonResponse
    {
        $data = $this->validateComponent($request, $feeComponent);
        $before = $feeComponent->toArray();
        $feeComponent->update([...$data, 'code' => strtoupper($data['code'])]);
        $this->audit->record($feeComponent, AuditAction::Updated, $before, $feeComponent->fresh()->toArray());

        return ApiResponse::success($feeComponent->fresh(), 'Komponen biaya diperbarui.');
    }

    public function destroyComponent(FeeComponent $feeComponent): JsonResponse
    {
        if (FeeRate::query()->where('fee_component_id', $feeComponent->id)->exists() || DB::table('invoice_items')->where('fee_component_id', $feeComponent->id)->exists()) {
            throw new ConflictException('Komponen sudah dipakai tarif/tagihan; nonaktifkan saja.');
        }

        $feeComponent->delete();

        return ApiResponse::success(null, 'Komponen biaya dihapus.');
    }

    public function tables(): JsonResponse
    {
        return ApiResponse::success(FeeRateTable::query()->withCount('rates')->with('effectiveTerm')->orderByDesc('academic_year')->orderByDesc('version')->get()->map(fn (FeeRateTable $t) => $this->presentTable($t)));
    }

    public function showTable(FeeRateTable $feeRateTable): JsonResponse
    {
        $feeRateTable->load(['rates.component', 'rates.studyProgram', 'rates.uktGroup', 'effectiveTerm']);

        return ApiResponse::success([
            ...$this->presentTable($feeRateTable),
            'rates' => $feeRateTable->rates->sortBy(fn (FeeRate $r) => [$r->component?->code, -$r->specificity_score])->values()->map(fn (FeeRate $r) => [
                ...$r->only(['id', 'fee_component_id', 'study_program_id', 'admission_year_from', 'admission_year_to', 'admission_path_code', 'study_system_code', 'ukt_group_id', 'semester_level_from', 'semester_level_to', 'min_credits', 'max_credits', 'specificity_score']),
                'component' => $r->component?->name, 'study_program' => $r->studyProgram?->name, 'ukt_group' => $r->uktGroup?->name,
                'calc_basis' => $r->calc_basis->value, 'calc_basis_label' => $r->calc_basis->label(), 'amount' => (string) $r->amount,
            ]),
            'conflicts' => $this->tariffs->conflicts($feeRateTable),
        ]);
    }

    public function storeTable(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:150'],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'effective_term_id' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'copy_from_id' => ['nullable', 'string'],
            'increase_percent' => ['nullable', 'numeric', 'min:-50', 'max:100'],
        ]);

        $table = DB::transaction(function () use ($data, $request): FeeRateTable {
            $version = (int) FeeRateTable::query()->where('code', $data['code'])->max('version') + 1;
            $table = FeeRateTable::query()->create([
                'code' => strtoupper($data['code']), 'name' => $data['name'], 'academic_year' => $data['academic_year'], 'effective_term_id' => $data['effective_term_id'] ?? null,
                'version' => $version, 'status' => RateTableStatus::Draft, 'notes' => $data['notes'] ?? null, 'created_by' => $this->user($request)->id,
            ]);

            if (! empty($data['copy_from_id'])) {
                $source = FeeRateTable::query()->with('rates')->findOrFail($data['copy_from_id']);
                $factor = 1 + ((float) ($data['increase_percent'] ?? 0)) / 100;

                foreach ($source->rates as $rate) {
                    FeeRate::query()->create([...$rate->only(['fee_component_id', 'study_program_id', 'admission_year_from', 'admission_year_to', 'admission_path_code', 'study_system_code', 'ukt_group_id', 'semester_level_from', 'semester_level_to', 'calc_basis', 'min_credits', 'max_credits', 'specificity_score']),
                        'fee_rate_table_id' => $table->id, 'amount' => Money::fromCents(Money::roundToUnit(Money::toCents($rate->amount) * $factor, 100))]);
                }
            }

            return $table;
        });

        return ApiResponse::success($this->presentTable($table), 'Tabel tarif dibuat.', status: 201);
    }

    public function storeRate(Request $request, FeeRateTable $feeRateTable): JsonResponse
    {
        $this->assertDraft($feeRateTable);
        $data = $this->validateRate($request);
        $rate = FeeRate::query()->create([...$data, 'fee_rate_table_id' => $feeRateTable->id, 'specificity_score' => TariffResolver::score($data)]);

        return ApiResponse::success($rate, 'Baris tarif ditambahkan.', status: 201);
    }

    public function updateRate(Request $request, FeeRate $feeRate): JsonResponse
    {
        $this->assertDraft($feeRate->rateTable);
        $data = $this->validateRate($request);
        $feeRate->update([...$data, 'specificity_score' => TariffResolver::score($data)]);

        return ApiResponse::success($feeRate->fresh(), 'Baris tarif diperbarui.');
    }

    public function destroyRate(FeeRate $feeRate): JsonResponse
    {
        $this->assertDraft($feeRate->rateTable);
        $feeRate->delete();

        return ApiResponse::success(null, 'Baris tarif dihapus.');
    }

    public function submit(Request $request, FeeRateTable $feeRateTable): JsonResponse
    {
        $this->assertDraft($feeRateTable);

        if (! $feeRateTable->rates()->exists()) {
            throw new ConflictException('Tabel tarif belum memiliki baris tarif.');
        }

        if ($conflicts = $this->tariffs->conflicts($feeRateTable)) {
            throw new ConflictException('Terdapat '.count($conflicts).' pasangan baris tarif yang bertabrakan (BR-KEU-02). Perbaiki sebelum diajukan.', 'RATE_CONFLICT', ['conflicts' => $conflicts]);
        }

        $feeRateTable->forceFill(['status' => RateTableStatus::PendingApproval, 'submitted_by' => $this->user($request)->id, 'submitted_at' => now(), 'rejection_reason' => null])->save();

        return ApiResponse::success($this->presentTable($feeRateTable), 'Tabel tarif diajukan untuk persetujuan.');
    }

    public function approve(Request $request, FeeRateTable $feeRateTable): JsonResponse
    {
        if ($feeRateTable->status !== RateTableStatus::PendingApproval) {
            throw new ConflictException('Tabel tarif tidak sedang menunggu persetujuan.');
        }

        $user = $this->user($request);
        Approvals::assertDistinct($user, [$feeRateTable->created_by, $feeRateTable->submitted_by], 'Penyusun tabel tarif tidak dapat menyetujuinya sendiri (pemisahan tugas).');

        DB::transaction(function () use ($feeRateTable, $user): void {
            // Versi lama dengan kode sama diarsipkan; tagihan historis tetap memakai snapshot.
            FeeRateTable::query()->where('code', $feeRateTable->code)->where('status', RateTableStatus::Published)->update(['status' => RateTableStatus::Archived->value]);
            $feeRateTable->forceFill(['status' => RateTableStatus::Published, 'approved_by' => $user->id, 'approved_at' => now(), 'published_at' => now()])->save();
        });

        $this->audit->recordAction($feeRateTable, AuditAction::Approved, ['code' => $feeRateTable->code, 'version' => $feeRateTable->version]);

        return ApiResponse::success($this->presentTable($feeRateTable->fresh()), 'Tabel tarif disetujui dan berlaku.');
    }

    public function reject(Request $request, FeeRateTable $feeRateTable): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:255']]);

        if ($feeRateTable->status !== RateTableStatus::PendingApproval) {
            throw new ConflictException('Tabel tarif tidak sedang menunggu persetujuan.');
        }

        Approvals::assertDistinct($this->user($request), [$feeRateTable->created_by, $feeRateTable->submitted_by]);
        $feeRateTable->forceFill(['status' => RateTableStatus::Draft, 'rejection_reason' => $data['reason']])->save();
        $this->audit->recordAction($feeRateTable, AuditAction::Rejected, [], $data['reason']);

        return ApiResponse::success($this->presentTable($feeRateTable), 'Tabel tarif dikembalikan ke draf.');
    }

    /** Pratinjau perhitungan tagihan satu mahasiswa sebelum diterbitkan (FR-TRF-04). */
    public function preview(Request $request, BillingService $billing): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
            'academic_term_id' => ['required', 'string'],
            'fee_component_ids' => ['required', 'array', 'min:1'],
            'bill_group' => ['nullable', Rule::enum(BillGroup::class)],
        ]);
        $student = Student::query()->findOrFail($data['student_id']);
        $term = AcademicTerm::query()->findOrFail($data['academic_term_id']);
        $components = FeeComponent::query()->whereIn('id', $data['fee_component_ids'])->get()->all();
        $result = $billing->compute($student, $term, $components, BillGroup::tryFrom($data['bill_group'] ?? '') ?? BillGroup::Registration, ignoreExisting: true);

        return ApiResponse::success([
            'lines' => array_map(fn ($l) => ['type' => $l['line_type']->value, 'type_label' => $l['line_type']->label(), 'description' => $l['description'], 'amount' => Money::fromCents($l['amount_cents'])], $result['lines']),
            'gross' => Money::fromCents($result['gross']), 'discount' => Money::fromCents($result['discount']), 'net' => Money::fromCents($result['net']),
            'skip' => $result['skip'], 'skip_detail' => $result['skip_detail'], 'snapshot' => $result['snapshot'],
            'semester_level' => $this->tariffs->semesterLevel($student, $term),
        ]);
    }

    /** @return array<string, mixed> */
    private function presentTable(FeeRateTable $t): array
    {
        return [
            ...$t->only(['id', 'code', 'name', 'academic_year', 'effective_term_id', 'version', 'notes', 'rejection_reason', 'created_by', 'submitted_by', 'approved_by']),
            'status' => $t->status->value, 'status_label' => $t->status->label(),
            'effective_term' => $t->relationLoaded('effectiveTerm') ? $t->effectiveTerm?->label() : null,
            'rates_count' => $t->rates_count ?? $t->rates()->count(),
            'published_at' => $t->published_at?->toIso8601String(),
        ];
    }

    private function assertDraft(FeeRateTable $table): void
    {
        if ($table->status !== RateTableStatus::Draft) {
            throw new ConflictException('Tabel tarif yang sudah diajukan/berlaku tidak dapat diubah; buat versi baru (BR-KEU-01).', 'RATE_TABLE_IMMUTABLE');
        }
    }

    /** @return array<string, mixed> */
    private function validateComponent(Request $request, ?FeeComponent $component = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('fee_components', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($component?->id)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(FeeCategory::class)],
            'billing_frequency' => ['required', Rule::in(['once', 'per_term', 'per_year', 'on_demand'])],
            'is_mandatory' => ['boolean'], 'installable' => ['boolean'], 'discountable' => ['boolean'], 'refundable' => ['boolean'], 'counts_for_clearance' => ['boolean'],
            'pddikti_category' => ['nullable', Rule::in(['entry_fee', 'term_fee', 'none'])],
            'gl_account_code' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateRate(Request $request): array
    {
        $data = $request->validate([
            'fee_component_id' => ['required', 'string', Rule::exists('fee_components', 'id')],
            'study_program_id' => ['nullable', 'string', Rule::exists('study_programs', 'id')],
            'admission_year_from' => ['nullable', 'integer', 'between:1990,2100'],
            'admission_year_to' => ['nullable', 'integer', 'between:1990,2100', 'gte:admission_year_from'],
            'admission_path_code' => ['nullable', 'string', 'max:30'],
            'study_system_code' => ['nullable', 'string', 'max:20'],
            'ukt_group_id' => ['nullable', 'string', Rule::exists('ukt_groups', 'id')],
            'semester_level_from' => ['nullable', 'integer', 'between:1,20'],
            'semester_level_to' => ['nullable', 'integer', 'between:1,20', 'gte:semester_level_from'],
            'calc_basis' => ['required', Rule::enum(CalcBasis::class)],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'min_credits' => ['nullable', 'integer', 'between:0,60'],
            'max_credits' => ['nullable', 'integer', 'between:0,60'],
        ]);
        $data['amount'] = Money::normalize((string) $data['amount']);

        return $data;
    }
}

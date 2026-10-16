<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Academic\Services\AcademicBlockService;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\AidCalcType;
use Modules\Finance\Enums\AidKind;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\CalcBasis;
use Modules\Finance\Enums\ClearanceRequirement;
use Modules\Finance\Enums\FeeCategory;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\PenaltyCalcType;
use Modules\Finance\Enums\PenaltyPeriodUnit;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\DiscountPolicy;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\InstallmentPlan;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Models\UktGroup;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Finance\Services\FinanceReportService;
use Modules\Finance\Services\ManualPaymentService;
use Modules\Finance\Services\ReconciliationService;
use Modules\Finance\Services\RefundService;

class DashboardController extends KeuController
{
    public function dashboard(Request $request, FinanceDashboardService $dashboard): JsonResponse
    {
        return ApiResponse::success($dashboard->build($this->filters($request)));
    }

    /** Data rujukan form & filter KEU (di-cache per sesi halaman di frontend). */
    public function lookups(): JsonResponse
    {
        return ApiResponse::success([
            'academic_terms' => AcademicTerm::query()->orderByDesc('start_date')->get()->map(fn (AcademicTerm $t) => ['id' => $t->id, 'label' => $t->label(), 'academic_year' => $t->academic_year, 'is_current' => $t->is_current])->values(),
            'faculties' => Faculty::query()->orderBy('name')->get(['id', 'code', 'name']),
            'study_programs' => StudyProgram::query()->orderBy('name')->get(['id', 'faculty_id', 'code', 'name']),
            'fee_components' => FeeComponent::query()->orderBy('code')->get(['id', 'code', 'name', 'category', 'is_active', 'discountable', 'installable']),
            'ukt_groups' => UktGroup::query()->orderBy('level')->get(['id', 'code', 'name', 'level', 'is_active']),
            'discount_policies' => DiscountPolicy::query()->orderBy('priority')->get(['id', 'code', 'name', 'kind', 'calc_type', 'value', 'is_active']),
            'installment_plans' => InstallmentPlan::query()->orderBy('code')->get(['id', 'code', 'name', 'bill_groups', 'terms', 'is_active', 'auto_approve']),
            'payment_channels' => PaymentChannel::query()->orderBy('sort_order')->get(['id', 'code', 'name', 'category', 'method', 'status']),
            'enums' => [
                'invoice_status' => InvoiceStatus::options(),
                'bill_group' => BillGroup::options(),
                'fee_category' => FeeCategory::options(),
                'calc_basis' => CalcBasis::options(),
                'aid_kind' => AidKind::options(),
                'aid_calc_type' => AidCalcType::options(),
                'adjustment_type' => AdjustmentType::options(),
                'payment_method' => PaymentMethod::options(),
                'transaction_status' => TransactionStatus::options(),
                'penalty_calc_type' => PenaltyCalcType::options(),
                'penalty_period_unit' => PenaltyPeriodUnit::options(),
                'clearance_requirement' => ClearanceRequirement::options(),
                'services' => collect(AcademicBlockService::ENFORCED_SERVICES)->map(fn ($value) => ['value' => $value, 'label' => AcademicBlockService::SERVICES[$value]])->values(),
                'proof_reject_reasons' => collect(ManualPaymentService::REJECT_REASONS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
                'refund_reasons' => collect(RefundService::REASONS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
                'reconciliation_resolutions' => collect(ReconciliationService::RESOLUTIONS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
                'report_types' => collect(FinanceReportService::TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            ],
        ]);
    }
}

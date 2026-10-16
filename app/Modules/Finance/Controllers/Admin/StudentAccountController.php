<?php

namespace Modules\Finance\Controllers\Admin;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Student;
use Modules\Finance\Controllers\KeuController;
use Modules\Finance\Models\CollectionNote;
use Modules\Finance\Models\FinancialAidAward;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\StudentUktAssignment;
use Modules\Finance\Services\ClearanceService;
use Modules\Finance\Services\DepositService;
use Modules\Finance\Services\StatementService;
use Modules\Finance\Support\Money;
use Modules\Finance\Support\Presenter;

/** Profil keuangan mahasiswa & kartu piutang (F-13/F-15), catatan penagihan. */
class StudentAccountController extends KeuController
{
    public function search(Request $request): JsonResponse
    {
        $term = '%'.$request->string('q').'%';
        $students = Student::query()->with('studyProgram')->where(fn ($q) => $q->where('nim', 'like', $term)->orWhere('name', 'like', $term))->orderBy('nim')->limit(20)->get();

        return ApiResponse::success($students->map(fn (Student $s) => Presenter::student($s)));
    }

    public function show(Student $student, ClearanceService $clearance, DepositService $deposits): JsonResponse
    {
        $student->load('studyProgram');
        $invoices = Invoice::query()->where('student_id', $student->id)->with(['installments'])->orderByDesc('due_date')->get();

        return ApiResponse::success([
            'student' => Presenter::student($student),
            'summary' => [
                'outstanding' => Money::fromCents($invoices->filter(fn ($i) => $i->status->isOpen())->sum(fn ($i) => Money::toCents($i->outstanding()))),
                'arrears' => Money::fromCents($invoices->filter(fn ($i) => $i->status->isOpen() && $i->is_overdue)->sum(fn ($i) => Money::toCents($i->outstanding()))),
                'deposit_available' => Money::fromCents($deposits->available($student)),
            ],
            'clearance' => $clearance->clearance($student),
            'invoices' => $invoices->map(fn (Invoice $i) => [...Presenter::invoice($i), 'installments' => $i->installments->map(fn ($x) => Presenter::installment($x))]),
            'transactions' => PaymentTransaction::query()->where('student_id', $student->id)->latest()->limit(50)->get()->map(fn ($t) => Presenter::transaction($t)),
            'awards' => FinancialAidAward::query()->where('student_id', $student->id)->with(['policy', 'startTerm', 'endTerm'])->latest()->get()->map(fn ($a) => Presenter::award($a)),
            'ukt' => StudentUktAssignment::query()->where('student_id', $student->id)->with('group')->latest()->get()->map(fn ($a) => ['group' => $a->group?->name, 'status' => $a->status, 'basis' => $a->basis, 'created_at' => $a->created_at?->toIso8601String()]),
            'notes' => CollectionNote::query()->where('student_id', $student->id)->with('creator')->latest()->limit(50)->get()->map(fn (CollectionNote $n) => [
                'id' => $n->id, 'channel' => $n->channel, 'note' => $n->note, 'promise_amount' => $n->promise_amount !== null ? (string) $n->promise_amount : null,
                'promise_date' => $n->promise_date?->toDateString(), 'created_by' => $n->creator?->name, 'created_at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function statement(Student $student, StatementService $statements): JsonResponse
    {
        return ApiResponse::success($statements->forStudent($student));
    }

    public function storeNote(Request $request, Student $student): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['phone', 'email', 'whatsapp', 'visit', 'letter', 'other'])],
            'note' => ['required', 'string', 'min:5', 'max:2000'],
            'promise_amount' => ['nullable', 'numeric', 'gt:0'],
            'promise_date' => ['nullable', 'date'],
        ]);
        $note = CollectionNote::query()->create([...$data, 'student_id' => $student->id, 'created_by' => $this->user($request)->id]);

        return ApiResponse::success($note, 'Catatan penagihan disimpan.', status: 201);
    }
}

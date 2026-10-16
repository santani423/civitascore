<?php

use Carbon\CarbonImmutable;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Models\AcademicTerm;
use Modules\Finance\Enums\FeeCategory;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\RateTableStatus;
use Modules\Finance\Models\BillingRunItem;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\FeeRateTable;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\StudentDepositAccount;
use Modules\Finance\Models\UktGroup;

/*
| Tarif berversi, penerbitan tagihan massal (simulasi → persetujuan),
| tagihan manual/draf, dan penyesuaian tagihan dengan maker-checker.
*/

function keuComponent(array $world, string $code = 'BPP', FeeCategory $category = FeeCategory::TuitionFixed): FeeComponent
{
    return portalAsTenant($world['university'], fn () => FeeComponent::query()->create([
        'code' => $code, 'name' => "Komponen {$code}", 'category' => $category, 'billing_frequency' => 'per_term',
        'is_mandatory' => true, 'installable' => true, 'discountable' => true, 'refundable' => false, 'counts_for_clearance' => true, 'is_active' => true,
    ]));
}

/** Tabel tarif terbit dengan satu baris tarif umum. */
function keuPublishedTable(array $world, FeeComponent $component, int $amount): FeeRateTable
{
    return portalAsTenant($world['university'], function () use ($world, $component, $amount): FeeRateTable {
        $table = FeeRateTable::query()->create(['code' => 'TRF', 'name' => 'Tarif', 'academic_year' => $world['term']->academic_year, 'version' => 1, 'status' => RateTableStatus::Published, 'published_at' => now()]);
        $table->rates()->create(['university_id' => $world['university']->id, 'fee_component_id' => $component->id, 'calc_basis' => 'flat', 'amount' => $amount, 'specificity_score' => 0]);

        return $table;
    });
}

test('the most specific tariff row wins and a published table cannot be edited or self-approved', function () {
    $world = keuWorld();
    $component = keuComponent($world);
    $maker = keuStaff($world, ['fee_rates.read', 'fee_rates.create', 'fee_rates.update', 'fee_rates.approve']);
    $checker = keuStaff($world, ['fee_rates.read', 'fee_rates.approve']);
    $headers = portalHeaders($world);

    $table = $this->actingAs($maker)->withHeaders($headers)
        ->postJson('/api/v1/finance/fee-rate-tables', ['code' => 'trf-2026', 'name' => 'Tarif 2026/2027', 'academic_year' => '2026/2027'])
        ->assertApiSuccess(201)->assertJsonPath('data.status', 'draft')->json('data.id');
    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/rates", ['fee_component_id' => $component->id, 'calc_basis' => 'flat', 'amount' => 5000000])->assertApiSuccess(201);
    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/rates", ['fee_component_id' => $component->id, 'study_program_id' => $world['program']->id, 'calc_basis' => 'flat', 'amount' => 4000000])->assertApiSuccess(201);
    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/rates", ['fee_component_id' => $component->id, 'calc_basis' => 'flat', 'amount' => -1])->assertApiError(422);

    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/submit")->assertApiSuccess();
    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/approve")->assertApiError(409)->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');

    $this->actingAs($checker)->withHeaders($headers)->postJson("/api/v1/finance/fee-rate-tables/{$table}/approve")
        ->assertApiSuccess()->assertJsonPath('data.status', 'published');

    $this->actingAs($maker)->withHeaders($headers)
        ->postJson("/api/v1/finance/fee-rate-tables/{$table}/rates", ['fee_component_id' => $component->id, 'calc_basis' => 'flat', 'amount' => 1])
        ->assertApiError(409)->assertJsonPath('errors.code', 'RATE_TABLE_IMMUTABLE');

    $this->postJson('/api/v1/finance/tariff-preview', ['student_id' => $world['student']->id, 'academic_term_id' => $world['term']->id, 'fee_component_ids' => [$component->id]])
        ->assertApiSuccess()->assertJsonPath('data.net', '4000000.00')->assertJsonPath('data.skip', null);
});

test('overlapping tariff rows with identical dimensions block submission', function () {
    $world = keuWorld();
    $component = keuComponent($world);
    $maker = keuStaff($world, ['fee_rates.create', 'fee_rates.update']);

    $table = $this->actingAs($maker)->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/finance/fee-rate-tables', ['code' => 'TRF', 'name' => 'Tarif', 'academic_year' => '2026/2027'])->json('data.id');
    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/rates", ['fee_component_id' => $component->id, 'calc_basis' => 'flat', 'amount' => 100, 'admission_year_from' => 2020, 'admission_year_to' => 2025])->assertApiSuccess(201);
    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/rates", ['fee_component_id' => $component->id, 'calc_basis' => 'flat', 'amount' => 200, 'admission_year_from' => 2024, 'admission_year_to' => 2030])->assertApiSuccess(201);

    $this->postJson("/api/v1/finance/fee-rate-tables/{$table}/submit")->assertApiError(409)->assertJsonPath('errors.code', 'RATE_CONFLICT');
    expect(FeeRateTable::query()->find($table)->status)->toBe(RateTableStatus::Draft);
});

test('a billing run is simulated, approved by someone else, and never bills the same component twice', function () {
    $world = keuWorld();
    $component = keuComponent($world);
    keuPublishedTable($world, $component, 3500000);
    $maker = keuStaff($world, ['billing_runs.read', 'billing_runs.create', 'billing_runs.approve']);
    $checker = keuStaff($world, ['billing_runs.read', 'billing_runs.approve']);
    $headers = portalHeaders($world);
    $payload = [
        'academic_term_id' => $world['term']->id, 'bill_group' => 'registration', 'fee_component_ids' => [$component->id],
        'due_date' => CarbonImmutable::today()->addDays(30)->toDateString(), 'student_ids' => [$world['student']->id],
    ];

    $run = $this->actingAs($maker)->withHeaders($headers)->postJson('/api/v1/finance/billing-runs', $payload)
        ->assertApiSuccess(201)->assertJsonPath('data.totals.ready', 1)->assertJsonPath('data.totals.net', '3500000.00')->json('data.id');
    expect(Invoice::query()->count())->toBe(0);

    $this->postJson("/api/v1/finance/billing-runs/{$run}/submit")->assertApiSuccess();
    $this->postJson("/api/v1/finance/billing-runs/{$run}/approve")->assertApiError(409)->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');

    $this->actingAs($checker)->withHeaders($headers)->postJson("/api/v1/finance/billing-runs/{$run}/approve")
        ->assertApiSuccess()->assertJsonPath('data.status', 'posted')->assertJsonPath('data.posted_totals.invoices', 1);
    $this->postJson("/api/v1/finance/billing-runs/{$run}/approve")->assertApiError(409);

    $invoice = Invoice::query()->sole();
    expect($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and((string) $invoice->amount)->toBe('3500000.00')
        ->and($invoice->rate_snapshot['table']['code'])->toBe('TRF')
        ->and($invoice->invoice_number)->not->toBeNull();

    // Simulasi kedua untuk mahasiswa & komponen yang sama → dilewati, tidak dapat diajukan.
    $second = $this->actingAs($maker)->withHeaders($headers)->postJson('/api/v1/finance/billing-runs', $payload)
        ->assertApiSuccess(201)->assertJsonPath('data.totals.ready', 0)->json('data.id');
    expect(BillingRunItem::query()->where('billing_run_id', $second)->value('skip_reason'))->toBe('ALREADY_BILLED');
    $this->postJson("/api/v1/finance/billing-runs/{$second}/submit")->assertApiError(409);
    expect(Invoice::query()->count())->toBe(1)
        ->and(InvoiceItem::query()->whereNotNull('active_key')->count())->toBe(1);
});

test('students without an applicable tariff are skipped with a reason instead of failing the run', function () {
    $world = keuWorld();
    $component = keuComponent($world);
    $maker = keuStaff($world, ['billing_runs.create']);

    $this->actingAs($maker)->withHeaders(portalHeaders($world))->postJson('/api/v1/finance/billing-runs', [
        'academic_term_id' => $world['term']->id, 'bill_group' => 'registration', 'fee_component_ids' => [$component->id],
        'due_date' => CarbonImmutable::today()->addDays(7)->toDateString(), 'student_ids' => [$world['student']->id],
    ])->assertApiSuccess(201)->assertJsonPath('data.totals.skipped', 1)->assertJsonPath('data.totals.skip_reasons.NO_RATE_TABLE', 1);

    $this->postJson('/api/v1/finance/billing-runs', [
        'academic_term_id' => $world['term']->id, 'bill_group' => 'registration', 'fee_component_ids' => [$component->id],
        'due_date' => CarbonImmutable::yesterday()->toDateString(),
    ])->assertApiError(409);
});

test('manual invoices validate input, stay hidden from the student while draft, and are issued explicitly', function () {
    $world = keuWorld();
    $staff = keuStaff($world, ['invoices.read', 'invoices.create', 'invoices.update']);
    $headers = portalHeaders($world);
    $base = ['student_id' => $world['student']->id, 'academic_term_id' => $world['term']->id, 'bill_group' => 'incidental', 'lines' => [['description' => 'Denda perpustakaan', 'amount' => 75000]]];

    $this->actingAs($staff)->withHeaders($headers)->postJson('/api/v1/finance/invoices', [...$base, 'due_date' => CarbonImmutable::yesterday()->toDateString()])->assertApiError(422);
    $this->postJson('/api/v1/finance/invoices', [...$base, 'due_date' => now()->addWeek()->toDateString(), 'bill_group' => 'penalty'])->assertApiError(422);
    $this->postJson('/api/v1/finance/invoices', [...$base, 'due_date' => now()->addWeek()->toDateString(), 'lines' => [['description' => 'X', 'amount' => 0]]])->assertApiError(422);

    $draft = $this->postJson('/api/v1/finance/invoices', [...$base, 'due_date' => now()->addWeek()->toDateString(), 'draft' => true])
        ->assertApiSuccess(201)->assertJsonPath('data.status', 'draft')->json('data.id');

    $this->actingAs($world['user'])->withHeaders($headers)->getJson("/api/v1/student/finance/invoices/{$draft}")->assertApiError(404);

    $this->actingAs($staff)->withHeaders($headers)->postJson("/api/v1/finance/invoices/{$draft}/issue")
        ->assertApiSuccess()->assertJsonPath('data.status', 'unpaid')->assertJsonPath('data.amount', '75000.00');
    $this->postJson("/api/v1/finance/invoices/{$draft}/issue")->assertStatus(409);

    $this->actingAs($world['user'])->withHeaders($headers)->getJson("/api/v1/student/finance/invoices/{$draft}")
        ->assertApiSuccess()->assertJsonPath('data.outstanding', '75000.00');
});

test('issued invoices change only through approved adjustments with separation of duties', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1000000');
    $maker = keuStaff($world, ['invoice_adjustments.create', 'invoice_adjustments.approve']);
    $checker = keuStaff($world, ['invoice_adjustments.approve']);
    $headers = portalHeaders($world);

    $adjustment = $this->actingAs($maker)->withHeaders($headers)->postJson("/api/v1/finance/invoices/{$invoice->id}/adjustments", [
        'type' => 'decrease', 'amount' => 250000, 'reason_code' => 'KOREKSI_TARIF', 'reason_note' => 'Salah tarif pada penerbitan awal',
    ])->assertApiSuccess(201)->json('data.id');
    expect((string) $invoice->fresh()->amount)->toBe('1000000.00');

    $this->postJson("/api/v1/finance/invoices/{$invoice->id}/adjustments", [
        'type' => 'increase', 'amount' => 1, 'reason_code' => 'X', 'reason_note' => 'Penyesuaian kedua bersamaan',
    ])->assertApiError(409);
    $this->postJson("/api/v1/finance/adjustments/{$adjustment}/approve")->assertApiError(409)->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');

    $this->actingAs($checker)->withHeaders($headers)->postJson("/api/v1/finance/adjustments/{$adjustment}/approve")
        ->assertApiSuccess()->assertJsonPath('data.status', 'applied');

    expect((string) $invoice->fresh()->amount)->toBe('750000.00')
        ->and((string) $invoice->fresh()->adjustment_amount)->toBe('-250000.00')
        ->and((string) $invoice->fresh()->gross_amount)->toBe('1000000.00');
});

test('void is blocked once paid while a decrease after payment moves the excess to deposit', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1000000');
    $cashier = keuCashier($world);
    keuPayCash($world, $cashier, keuOpenSession($world, $cashier), $invoice->id, 1000000)->assertApiSuccess(201);
    $maker = keuStaff($world, ['invoice_adjustments.create']);
    $checker = keuStaff($world, ['invoice_adjustments.approve']);
    $headers = portalHeaders($world);

    $this->actingAs($maker)->withHeaders($headers)->postJson("/api/v1/finance/invoices/{$invoice->id}/adjustments", [
        'type' => 'void', 'reason_code' => 'BATAL', 'reason_note' => 'Mahasiswa mengundurkan diri',
    ])->assertApiError(409)->assertJsonPath('errors.code', 'INVOICE_HAS_PAYMENTS');

    $adjustment = $this->postJson("/api/v1/finance/invoices/{$invoice->id}/adjustments", [
        'type' => 'decrease', 'amount' => 200000, 'reason_code' => 'BEASISWA_SUSULAN', 'reason_note' => 'Beasiswa terbit setelah pembayaran',
    ])->assertApiSuccess(201)->json('data.id');
    $this->actingAs($checker)->withHeaders($headers)->postJson("/api/v1/finance/adjustments/{$adjustment}/approve")->assertApiSuccess();

    $fresh = $invoice->fresh();
    expect((string) $fresh->amount)->toBe('800000.00')
        ->and((string) $fresh->paid_amount)->toBe('800000.00')
        ->and($fresh->status)->toBe(InvoiceStatus::Paid)
        ->and((string) StudentDepositAccount::query()->where('student_id', $world['student']->id)->value('balance'))->toBe('200000.00');
});

test('an unpaid invoice can be voided and is then excluded from receivables', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '500000');
    $maker = keuStaff($world, ['invoice_adjustments.create']);
    $checker = keuStaff($world, ['invoice_adjustments.approve']);
    $headers = portalHeaders($world);

    $adjustment = $this->actingAs($maker)->withHeaders($headers)->postJson("/api/v1/finance/invoices/{$invoice->id}/adjustments", [
        'type' => 'void', 'reason_code' => 'SALAH_TERBIT', 'reason_note' => 'Diterbitkan untuk mahasiswa yang salah',
    ])->assertApiSuccess(201)->json('data.id');
    $this->actingAs($checker)->withHeaders($headers)->postJson("/api/v1/finance/adjustments/{$adjustment}/approve")->assertApiSuccess();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Cancelled)
        ->and($invoice->fresh()->cancelled_at)->not->toBeNull();

    $this->actingAs($world['user'])->withHeaders($headers)->getJson('/api/v1/student/finance/summary')
        ->assertApiSuccess()->assertJsonPath('data.outstanding', '0.00')->assertJsonPath('data.open_invoices', 0);
});

test('a UKT group assigned from a later semester does not change the current semester tariff', function () {
    $world = keuWorld();
    $ukt = keuComponent($world, 'UKT', FeeCategory::Ukt);
    [$low, $high, $nextTerm] = portalAsTenant($world['university'], function () use ($world, $ukt) {
        $low = UktGroup::query()->create(['code' => 'G1', 'name' => 'Golongan 1', 'level' => 1, 'is_active' => true]);
        $high = UktGroup::query()->create(['code' => 'G5', 'name' => 'Golongan 5', 'level' => 5, 'is_active' => true]);
        $table = FeeRateTable::query()->create(['code' => 'TRF', 'name' => 'Tarif', 'academic_year' => '2026/2027', 'version' => 1, 'status' => RateTableStatus::Published, 'published_at' => now()]);
        foreach ([[$low, 1000000], [$high, 5000000]] as [$group, $amount]) {
            $table->rates()->create(['university_id' => $world['university']->id, 'fee_component_id' => $ukt->id, 'ukt_group_id' => $group->id, 'calc_basis' => 'flat', 'amount' => $amount, 'specificity_score' => 32]);
        }
        $nextTerm = AcademicTerm::factory()->create([
            'university_id' => $world['university']->id, 'academic_year' => '2026/2027', 'semester' => AcademicSemester::Genap,
            'is_current' => false, 'start_date' => now()->addMonths(5)->toDateString(), 'end_date' => now()->addMonths(10)->toDateString(),
        ]);

        return [$low, $high, $nextTerm];
    });
    $staff = keuStaff($world, ['ukt.create', 'fee_rates.read']);
    $headers = portalHeaders($world);
    $assign = fn (string $groupId, ?string $termId) => $this->actingAs($staff)->withHeaders($headers)->postJson('/api/v1/finance/ukt-assignments', [
        'student_ids' => [$world['student']->id], 'ukt_group_id' => $groupId, 'effective_term_id' => $termId, 'basis' => 'SK penetapan UKT',
    ])->assertApiSuccess();
    $preview = fn (string $termId) => $this->postJson('/api/v1/finance/tariff-preview', ['student_id' => $world['student']->id, 'academic_term_id' => $termId, 'fee_component_ids' => [$ukt->id]])->json('data.net');

    $assign($low->id, null);
    $assign($high->id, $nextTerm->id);

    expect($preview($world['term']->id))->toBe('1000000.00')
        ->and($preview($nextTerm->id))->toBe('5000000.00');
});

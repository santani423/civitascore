<?php

namespace Modules\Finance\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Modules\Tenancy\Models\University;

/**
 * One invoice per active student for the current period, weighted toward
 * "lunas" (~65%) with a meaningful slice partial (~20%) and unpaid (~15%)
 * so the invoice-status chart and "belum dibayar" stat aren't degenerate.
 * Payments are only created for paid/partial invoices, with `paid_at`
 * spread across the last ~9 months so the payment-trend chart varies.
 *
 * Bulk-inserted via DB::table() like AcademicSeeder — see its docblock for
 * why. Idempotent: skips entirely if this university already has invoices.
 */
class FinanceSeeder extends Seeder
{
    private const AMOUNT_POOL = [2_500_000, 5_000_000, 7_500_000, 10_000_000];

    /** @var array<string, int> */
    private const STATUS_WEIGHTS = ['paid' => 65, 'partial' => 20, 'unpaid' => 15];

    private const METHODS = ['transfer', 'virtual_account', 'cash'];

    /**
     * @param  Collection<int, Student>  $students
     */
    public function run(University $university, Collection $students): void
    {
        app(TenantContext::class)->setUniversityId($university->id);

        if (Invoice::query()->count() > 0) {
            return;
        }

        $activeStudents = $students->filter(fn (Student $student) => $student->status === StudentStatus::Active);
        $now = now();
        $invoiceRows = [];
        $paymentRows = [];

        foreach ($activeStudents as $student) {
            $amount = self::AMOUNT_POOL[array_rand(self::AMOUNT_POOL)];
            $status = InvoiceStatus::from($this->weightedPick(self::STATUS_WEIGHTS));

            $paidAmount = match ($status) {
                InvoiceStatus::Paid => $amount,
                InvoiceStatus::Partial => (int) round($amount * (random_int(30, 70) / 100)),
                InvoiceStatus::Unpaid => 0,
            };

            $invoiceId = (string) Str::ulid();

            $invoiceRows[] = [
                'id' => $invoiceId,
                'university_id' => $university->id,
                'student_id' => $student->id,
                'period' => '2026/2027 Ganjil',
                'amount' => $amount,
                'paid_amount' => $paidAmount,
                'status' => $status->value,
                'due_date' => now()->addMonth()->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($paidAmount > 0) {
                $paymentRows[] = [
                    'id' => (string) Str::ulid(),
                    'university_id' => $university->id,
                    'invoice_id' => $invoiceId,
                    'amount' => $paidAmount,
                    'paid_at' => fake()->dateTimeBetween('-9 months', 'now')->format('Y-m-d'),
                    'method' => self::METHODS[array_rand(self::METHODS)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($invoiceRows, 500) as $chunk) {
            DB::table('invoices')->insert($chunk);
        }

        foreach (array_chunk($paymentRows, 500) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weightedPick(array $weights): string
    {
        $total = array_sum($weights);
        $random = random_int(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;

            if ($random <= $cumulative) {
                return $key;
            }
        }

        return array_key_last($weights);
    }
}

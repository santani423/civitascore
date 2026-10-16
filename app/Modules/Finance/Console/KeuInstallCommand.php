<?php

namespace Modules\Finance\Console;

use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Modules\Finance\Enums\ChannelCategory;
use Modules\Finance\Enums\ChannelStatus;
use Modules\Finance\Enums\ClearanceRequirement;
use Modules\Finance\Enums\FeeCategory;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\FinanceClearanceRule;
use Modules\Finance\Models\InstallmentPlan;
use Modules\Finance\Models\PaymentChannel;
use Modules\Tenancy\Models\University;

/**
 * Data awal KEU per kampus: komponen biaya umum, kanal offline (tunai &
 * transfer manual), skema cicilan contoh, dan aturan cekal contoh dalam
 * keadaan NONAKTIF (kebijakan kampus harus ditetapkan dulu). Aman diulang.
 */
class KeuInstallCommand extends Command
{
    protected $signature = 'keu:install {university? : kode universitas (kosong = semua)}';

    protected $description = 'Menyiapkan data awal Modul KEU (komponen, kanal offline, contoh skema & aturan nonaktif).';

    public function handle(TenantContext $tenant): int
    {
        $universities = University::query()->when($this->argument('university'), fn ($q, $code) => $q->where('code', $code))->get();

        foreach ($universities as $university) {
            $tenant->setUniversityId($university->id);
            $this->seed();
            $this->info("KEU siap untuk {$university->name}.");
        }

        $tenant->setUniversityId(null);

        return self::SUCCESS;
    }

    private function seed(): void
    {
        $components = [
            ['BPP', 'BPP/SPP Tetap Semester', FeeCategory::TuitionFixed, 'per_term', true, true],
            ['UKT', 'Uang Kuliah Tunggal', FeeCategory::Ukt, 'per_term', true, true],
            ['SKS', 'Biaya per SKS', FeeCategory::CreditFee, 'per_term', false, true],
            ['DPP', 'Dana Pengembangan Pendidikan', FeeCategory::Development, 'once', true, true],
            ['PRAKTIKUM', 'Biaya Praktikum', FeeCategory::Practicum, 'per_term', false, true],
            ['WISUDA', 'Biaya Wisuda', FeeCategory::Graduation, 'once', false, false],
            ['CUTI', 'Biaya Cuti Akademik', FeeCategory::Leave, 'per_term', false, false],
            ['ADMIN', 'Biaya Administrasi', FeeCategory::Administrative, 'on_demand', false, false],
        ];

        foreach ($components as [$code, $name, $category, $frequency, $discountable, $installable]) {
            FeeComponent::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'category' => $category, 'billing_frequency' => $frequency, 'is_mandatory' => true,
                'discountable' => $discountable, 'installable' => $installable, 'counts_for_clearance' => $category !== FeeCategory::Graduation,
                'pddikti_category' => match ($category) {
                    FeeCategory::Development => 'entry_fee', FeeCategory::TuitionFixed, FeeCategory::Ukt, FeeCategory::CreditFee => 'term_fee', default => 'none'
                },
                'is_active' => $code !== 'UKT',
            ]);
        }

        PaymentChannel::query()->firstOrCreate(['code' => 'CASH'], [
            'name' => 'Tunai di Loket Keuangan', 'category' => ChannelCategory::Offline, 'provider' => 'cash', 'method' => 'cash',
            'status' => ChannelStatus::Active, 'counts_toward_quota' => false, 'sort_order' => 90,
        ]);
        PaymentChannel::query()->firstOrCreate(['code' => 'MANUAL_TRANSFER'], [
            'name' => 'Transfer Bank (unggah bukti)', 'category' => ChannelCategory::Offline, 'provider' => 'manual', 'method' => 'manual_transfer',
            'status' => ChannelStatus::Active, 'counts_toward_quota' => false, 'sort_order' => 91,
        ]);

        InstallmentPlan::query()->firstOrCreate(['code' => 'CICIL3'], [
            'name' => 'Cicilan 3 termin (40/30/30)', 'bill_groups' => ['registration', 'credit'],
            'terms' => [['percent' => 40, 'due_offset_days' => 14], ['percent' => 30, 'due_offset_days' => 45], ['percent' => 30, 'due_offset_days' => 75]],
            'auto_approve' => true, 'require_no_arrears' => true, 'is_active' => false,
        ]);

        $rules = [
            ['KEU-KRS', 'Syarat KRS: registrasi semester berjalan', ['krs'], ClearanceRequirement::FirstInstallmentPaid, ['registration'], 'current'],
            ['KEU-TUNGGAKAN', 'Tunggakan semester sebelumnya', ['krs'], ClearanceRequirement::FullyPaid, null, 'previous'],
            ['KEU-UJIAN', 'Syarat ujian: kewajiban jatuh tempo lunas', ['exam'], ClearanceRequirement::DueItemsPaid, ['registration', 'credit'], 'current'],
        ];

        foreach ($rules as [$code, $name, $services, $requirement, $groups, $terms]) {
            FinanceClearanceRule::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'services' => $services, 'requirement' => $requirement, 'bill_groups' => $groups, 'terms' => $terms,
                'grace_days' => 3, 'min_outstanding_amount' => 1, 'include_penalties' => false, 'is_active' => false,
            ]);
        }
    }
}

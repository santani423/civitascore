<?php

namespace Modules\Admission\Console;

use Illuminate\Console\Command;
use Modules\Admission\Database\Seeders\AdmissionSeeder;
use Modules\Tenancy\Models\University;

/**
 * Menyiapkan data awal PMB untuk tenant yang sudah ada (jenis dokumen
 * default, sistem kuliah, alur persetujuan). Aman dijalankan ulang; tidak
 * menimpa data yang sudah diubah tenant.
 */
class PmbInstallCommand extends Command
{
    protected $signature = 'pmb:install {university? : ID atau kode universitas (kosong = semua)}';

    protected $description = 'Menyiapkan data awal modul PMB per universitas.';

    public function handle(AdmissionSeeder $seeder): int
    {
        $target = $this->argument('university');
        $universities = University::query()
            ->when($target, fn ($q) => $q->where(fn ($w) => $w->whereKey($target)->orWhere('code', $target)))
            ->get();

        if ($universities->isEmpty()) {
            $this->error('Universitas tidak ditemukan.');

            return self::FAILURE;
        }

        foreach ($universities as $university) {
            $created = $seeder->run($university);
            $this->info(sprintf('%s: %d jenis dokumen, %d sistem kuliah, %d alur persetujuan baru.', $university->name, $created['document_types'], $created['study_systems'], $created['workflows']));
        }

        return self::SUCCESS;
    }
}

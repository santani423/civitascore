<?php

namespace Modules\Admission\Console;

use Illuminate\Console\Command;
use Modules\Admission\Database\Seeders\AdmissionDemoSeeder;
use Modules\Admission\Database\Seeders\AdmissionSeeder;
use Modules\Tenancy\Models\University;

/**
 * Menyiapkan data awal PMB untuk tenant yang sudah ada (jenis dokumen
 * default, sistem kuliah, alur persetujuan). Aman dijalankan ulang; tidak
 * menimpa data yang sudah diubah tenant. `--demo` menambahkan data demo
 * website PMB (AdmissionDemoSeeder) — bukan untuk tenant produksi.
 */
class PmbInstallCommand extends Command
{
    protected $signature = 'pmb:install {university? : ID atau kode universitas (kosong = semua)}
        {--demo : Tambahkan data demo website PMB (periode, jalur, kuota, biaya, profil prodi, konten)}';

    protected $description = 'Menyiapkan data awal modul PMB per universitas.';

    public function handle(AdmissionSeeder $seeder, AdmissionDemoSeeder $demo): int
    {
        if ($this->option('demo') && app()->isProduction() && ! $this->confirm('Lingkungan produksi. Tetap tambahkan data demo PMB?')) {
            return self::FAILURE;
        }

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

            if ($this->option('demo')) {
                $seeded = $demo->seedUniversity($university);
                $this->info(sprintf('  demo: %d prodi terpublikasi, %d kuota & %d komponen biaya baru.', $seeded['programs'], $seeded['quotas'], $seeded['fees']));
            }
        }

        return self::SUCCESS;
    }
}

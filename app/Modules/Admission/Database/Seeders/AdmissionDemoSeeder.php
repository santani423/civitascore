<?php

namespace Modules\Admission\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Models\AdmissionPath;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\DocumentType;
use Modules\Admission\Models\FeeComponent;
use Modules\Admission\Models\FormTemplate;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\ProgramProfile;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\SelectionComponent;
use Modules\Admission\Models\SelectionScheme;
use Modules\Admission\Models\StudySystem;
use Modules\Admission\Models\Wave;
use Modules\Admission\Models\WavePath;
use Modules\Tenancy\Models\University;

/**
 * Data DEMO website PMB per tenant — melengkapi AdmissionSeeder yang sengaja
 * tidak membuat data kebijakan. Hasilnya: satu periode terpublikasi
 * (gelombang 1 sedang dibuka, gelombang 2 terjadwal), jalur, template
 * formulir, skema seleksi terkunci, persyaratan dokumen, kuota, biaya, profil
 * prodi terpublikasi, dan konten (legal, pernyataan, FAQ, berita, panduan),
 * sehingga halaman publik Prodi/Jalur/Biaya/Jadwal dan pendaftaran dapat
 * dicoba. Bukan untuk tenant produksi.
 *
 * Jadwal dihitung relatif terhadap waktu seeding. Idempoten (firstOrCreate
 * per kode): menjalankan ulang tidak menggeser jadwal maupun menimpa ubahan
 * panitia. university_id selalu diisi eksplisit (lihat AdmissionSeeder).
 */
class AdmissionDemoSeeder extends Seeder
{
    /**
     * Katalog prodi per kode universitas. AtmaJayaStudentSeeder hanya membuat
     * Teknik Informatika (roster nyata), sehingga filter jenjang/fakultas di
     * halaman Prodi tidak dapat dicoba tanpa prodi tambahan. Tenant lain
     * memakai prodi hasil AcademicSeeder dengan profil generik.
     *
     * kode prodi => [kode fakultas, nama, jenjang, gelar, akreditasi, kelas karyawan, [uang pangkal, SPP/semester], ringkasan, prospek karier]
     */
    private const CATALOG = [
        'UAJ' => [
            'TI1' => ['TEK', 'Teknik Informatika', 'S1', 'S.Kom.', 'Unggul', true, [20_000_000, 9_000_000], 'Rekayasa perangkat lunak, kecerdasan artifisial, dan keamanan siber dengan proyek industri sejak tahun kedua.', 'Software engineer, data scientist, analis keamanan siber, konsultan TI'],
            'TIN' => ['TEK', 'Teknik Industri', 'S1', 'S.T.', 'Baik Sekali', false, [18_000_000, 8_500_000], 'Perancangan dan optimasi sistem produksi, rantai pasok, serta manajemen mutu.', 'Supply chain analyst, process engineer, quality manager, konsultan operasional'],
            'TME' => ['TEK', 'Teknik Mesin', 'S1', 'S.T.', 'Baik Sekali', false, [18_000_000, 8_500_000], 'Perancangan mesin, manufaktur, energi terbarukan, dan otomasi.', 'Design engineer, maintenance engineer, energy analyst'],
            'MNJ' => ['FEB', 'Manajemen', 'S1', 'S.M.', 'Unggul', true, [17_500_000, 8_000_000], 'Manajemen pemasaran, keuangan, SDM, dan kewirausahaan berbasis studi kasus.', 'Manajer pemasaran, analis keuangan, HR specialist, wirausahawan'],
            'AKT' => ['FEB', 'Akuntansi', 'S1', 'S.Ak.', 'Unggul', true, [17_500_000, 8_000_000], 'Akuntansi keuangan, audit, perpajakan, dan sistem informasi akuntansi.', 'Auditor, konsultan pajak, akuntan publik, financial controller'],
            'MM' => ['FEB', 'Magister Manajemen', 'S2', 'M.M.', 'Unggul', true, [10_000_000, 14_000_000], 'Program magister untuk profesional dengan konsentrasi strategi, keuangan, dan pemasaran.', 'Manajer senior, konsultan bisnis, pemimpin unit usaha'],
            'HKM' => ['FH', 'Ilmu Hukum', 'S1', 'S.H.', 'Unggul', false, [17_000_000, 7_500_000], 'Hukum bisnis, pidana, tata negara, dan hukum internasional dengan klinik hukum.', 'Advokat, notaris, legal officer, hakim, jaksa'],
            'PSI' => ['FPSI', 'Psikologi', 'S1', 'S.Psi.', 'Unggul', false, [18_500_000, 8_500_000], 'Psikologi klinis, industri-organisasi, pendidikan, dan sosial.', 'HR specialist, asesor, konselor, peneliti perilaku'],
            'KED' => ['FKIK', 'Kedokteran', 'S1', 'S.Ked.', 'Unggul', false, [250_000_000, 25_000_000], 'Pendidikan dokter dengan kurikulum berbasis kompetensi dan rumah sakit pendidikan.', 'Dokter umum, peneliti biomedis, dokter spesialis (lanjutan)'],
        ],
    ];

    /** kode fakultas => nama (dipakai bila fakultas katalog belum ada) */
    private const FACULTIES = [
        'TEK' => 'Fakultas Teknik',
        'FEB' => 'Fakultas Ekonomi dan Bisnis',
        'FH' => 'Fakultas Hukum',
        'FPSI' => 'Fakultas Psikologi',
        'FKIK' => 'Fakultas Kedokteran dan Ilmu Kesehatan',
    ];

    /** jenjang => [lama studi, uang pangkal, SPP/semester] untuk prodi di luar katalog */
    private const LEVEL_DEFAULTS = [
        'D3' => ['3 tahun (6 semester)', 10_000_000, 6_000_000],
        'D4' => ['4 tahun (8 semester)', 12_000_000, 7_000_000],
        'S1' => ['4 tahun (8 semester)', 15_000_000, 7_500_000],
        'S2' => ['2 tahun (4 semester)', 10_000_000, 12_000_000],
        'S3' => ['3 tahun (6 semester)', 15_000_000, 18_000_000],
    ];

    /** kode => [nama, kategori, jenjang, wajib ujian, deskripsi] */
    private const PATHS = [
        'REGULER' => ['Jalur Reguler (Tes)', 'regular', ['D3', 'D4', 'S1'], true, 'Seleksi melalui Tes Potensi Akademik berbasis komputer (CBT) dan nilai rapor.'],
        'PRESTASI' => ['Jalur Prestasi', 'achievement', ['D3', 'D4', 'S1'], false, 'Tanpa tes tulis untuk siswa berprestasi akademik maupun nonakademik.'],
        'KIPK' => ['Jalur KIP Kuliah', 'kip_affirmation', ['D3', 'D4', 'S1'], false, 'Afirmasi bagi penerima atau calon penerima KIP Kuliah; bebas biaya formulir.'],
        'PASCA' => ['Jalur Pascasarjana', 'postgraduate', ['S2', 'S3'], false, 'Seleksi berkas dan wawancara untuk program magister/doktor.'],
    ];

    /** kode jalur => [biaya formulir, maks pilihan, skema, gelombang, kelas karyawan, dokumen pendaftaran, catatan] */
    private const WAVE_PATHS = [
        'REGULER' => [350_000, 2, 'SKEMA-REGULER', ['G1', 'G2'], true, ['PHOTO', 'ID_CARD', 'REPORT_CARD', 'DIPLOMA_OR_SKL'], 'Tes CBT dilaksanakan daring; jadwal dipilih setelah pembayaran formulir.'],
        'PRESTASI' => [250_000, 2, 'SKEMA-PRESTASI', ['G1'], false, ['PHOTO', 'ID_CARD', 'REPORT_CARD', 'ACHIEVEMENT_CERT'], 'Unggah sertifikat prestasi tiga tahun terakhir.'],
        'KIPK' => [0, 1, 'SKEMA-KIPK', ['G1'], false, ['PHOTO', 'ID_CARD', 'FAMILY_CARD', 'REPORT_CARD', 'KIP_CARD', 'INCOME_STATEMENT'], 'Kuota terbatas; wawancara dilakukan setelah verifikasi berkas.'],
        'PASCA' => [500_000, 1, 'SKEMA-PASCA', ['G1', 'G2'], true, ['PHOTO', 'ID_CARD', 'DIPLOMA_OR_SKL', 'TRANSCRIPT', 'RECOMMENDATION'], 'Wawancara dapat dilakukan daring.'],
    ];

    /** kode jalur => kapasitas per prodi per sistem kuliah */
    private const CAPACITY = ['REGULER' => 60, 'PRESTASI' => 15, 'KIPK' => 5, 'PASCA' => 30];

    /** kode => [nama, ambang lulus, [kode komponen => [nama, sumber, bobot]]] */
    private const SCHEMES = [
        'SKEMA-REGULER' => ['Skema Jalur Reguler', 50, ['CBT' => ['Tes Potensi Akademik (CBT)', 'cbt', 0.7], 'RAPOR' => ['Nilai rapor', 'report_card', 0.3]]],
        'SKEMA-PRESTASI' => ['Skema Jalur Prestasi', 60, ['RAPOR' => ['Nilai rapor', 'report_card', 0.6], 'PRESTASI' => ['Portofolio prestasi', 'achievement', 0.4]]],
        'SKEMA-KIPK' => ['Skema Jalur KIP Kuliah', 55, ['RAPOR' => ['Nilai rapor', 'report_card', 0.5], 'WAWANCARA' => ['Wawancara', 'interview', 0.5]]],
        'SKEMA-PASCA' => ['Skema Pascasarjana', 60, ['BERKAS' => ['Seleksi berkas', 'administrative', 0.4], 'WAWANCARA' => ['Wawancara', 'interview', 0.6]]],
    ];

    /** [tipe, slug, judul, kategori, isi HTML] */
    private const CONTENTS = [
        ['privacy_policy', 'kebijakan-privasi', 'Kebijakan Privasi PMB', null, '<p>Data pribadi pendaftar hanya dipakai untuk proses penerimaan mahasiswa baru dan disimpan sesuai ketentuan pelindungan data pribadi.</p><h2>Data yang dikumpulkan</h2><ul><li>Identitas dan kontak</li><li>Riwayat pendidikan</li><li>Dokumen persyaratan</li></ul>'],
        ['terms', 'ketentuan-pendaftaran', 'Ketentuan Pendaftaran', null, '<p>Pendaftar wajib mengisi data dengan benar. Biaya formulir yang telah dibayar tidak dapat dikembalikan kecuali pendaftaran dibatalkan oleh panitia.</p>'],
        ['declaration', 'pernyataan-kebenaran-data', 'Pernyataan Kebenaran Data', null, '<p>Saya menyatakan bahwa seluruh data dan dokumen yang saya kirimkan benar. Saya bersedia menerima sanksi pembatalan bila terbukti tidak benar.</p>'],
        ['faq', 'cara-mendaftar', 'Bagaimana cara mendaftar?', 'Pendaftaran', '<p>Buat akun, verifikasi email, pilih gelombang dan jalur, lengkapi formulir, lalu bayar biaya formulir.</p>'],
        ['faq', 'pilihan-prodi', 'Berapa program studi yang dapat dipilih?', 'Pendaftaran', '<p>Jalur Reguler dan Prestasi dapat memilih hingga 2 program studi; jalur lain 1 program studi.</p>'],
        ['faq', 'metode-pembayaran', 'Bagaimana cara membayar biaya formulir?', 'Pembayaran', '<p>Melalui virtual account bank yang tertera pada tagihan. Pembayaran terverifikasi otomatis.</p>'],
        ['faq', 'cicilan-uang-pangkal', 'Apakah uang pangkal dapat dicicil?', 'Pembayaran', '<p>Ya, uang pangkal dapat dicicil hingga 3 kali sesuai ketentuan pada halaman Biaya.</p>'],
        ['faq', 'jadwal-ujian', 'Kapan ujian seleksi dilaksanakan?', 'Seleksi', '<p>Jadwal ujian setiap gelombang dapat dilihat pada halaman Jadwal.</p>'],
        ['faq', 'batas-daftar-ulang', 'Apa yang terjadi bila terlambat daftar ulang?', 'Daftar Ulang', '<p>Kelulusan dianggap gugur dan kursi dialihkan ke daftar tunggu.</p>'],
        ['news', 'pendaftaran-gelombang-1-dibuka', 'Pendaftaran Gelombang 1 Telah Dibuka', null, '<p>Pendaftaran mahasiswa baru Gelombang 1 resmi dibuka untuk jalur Reguler, Prestasi, KIP Kuliah, dan Pascasarjana.</p>'],
        ['news', 'beasiswa-prestasi', 'Beasiswa Bagi Pendaftar Jalur Prestasi', null, '<p>Pendaftar jalur Prestasi berkesempatan memperoleh potongan uang pangkal hingga 50%.</p>'],
        ['guide', 'cara-mendaftar', 'Panduan Pendaftaran Daring', null, '<ol><li>Buat akun dan verifikasi email.</li><li>Pilih gelombang, jalur, dan program studi.</li><li>Lengkapi formulir dan unggah dokumen.</li><li>Bayar biaya formulir.</li><li>Kirim formulir dan pantau status.</li></ol>'],
        ['guide', 'unggah-dokumen', 'Panduan Unggah Dokumen', null, '<p>Gunakan PDF atau JPG/PNG yang jelas terbaca, ukuran maksimal sesuai ketentuan setiap dokumen.</p>'],
        ['re_registration_info', 'alur-daftar-ulang', 'Alur Daftar Ulang', null, '<ol><li>Konfirmasi penerimaan.</li><li>Bayar uang pangkal dan SPP semester 1.</li><li>Unggah surat pernyataan bermeterai.</li></ol>'],
        ['banner', 'gelombang-1', 'Gelombang 1 Dibuka', null, '<p>Daftar sekarang dan dapatkan potongan biaya formulir untuk pendaftar awal.</p>'],
    ];

    private University $university;

    public function run(): void
    {
        foreach (University::query()->get() as $university) {
            $this->seedUniversity($university);
        }
    }

    /** @return array{programs: int, quotas: int, fees: int} */
    public function seedUniversity(University $university): array
    {
        (new AdmissionSeeder)->run($university);

        $tenant = app(TenantContext::class);
        $previous = $tenant->universityId();
        $tenant->setUniversityId($university->getKey());
        $this->university = $university;

        try {
            $programs = $this->programs();

            if ($programs->isEmpty()) {
                return ['programs' => 0, 'quotas' => 0, 'fees' => 0];
            }

            $this->contents();
            $period = $this->period();
            $waves = $this->waves($period);
            $form = $this->formTemplate();
            $systems = StudySystem::query()->pluck('id', 'code');
            $quotas = 0;

            foreach (self::PATHS as $code => [$name, $category, $levels, $requiresExam, $description]) {
                $eligible = $programs->filter(fn (array $p) => in_array($p['program']->degree_level, $levels, true));

                if ($eligible->isEmpty()) {
                    continue;
                }

                $path = AdmissionPath::query()->firstOrCreate(['code' => $code], [
                    'university_id' => $university->getKey(), 'name' => $name, 'category' => $category, 'description' => $description,
                    'eligible_degree_levels' => $levels, 'requires_exam' => $requiresExam, 'is_active' => true,
                ]);
                $quotas += $this->wavePathsAndQuotas($path, $period, $waves, $form, $eligible, $systems);
            }

            $fees = $this->fees($period, $programs, $systems);
        } finally {
            $tenant->setUniversityId($previous);
        }

        foreach (['site', 'periods', 'programs', 'paths'] as $key) {
            Cache::forget('pmb:public:'.$university->getKey().':'.$key);
        }

        return ['programs' => $programs->count(), 'quotas' => $quotas, 'fees' => $fees];
    }

    /**
     * Prodi aktif tenant (ditambah katalog bila ada) beserta profil PMB-nya.
     *
     * @return Collection<int, array{program: StudyProgram, karyawan: bool, fees: array{0: int, 1: int}}>
     */
    private function programs(): Collection
    {
        $catalog = self::CATALOG[$this->university->code] ?? [];

        foreach ($catalog as $code => [$facultyCode, $name, $level]) {
            $faculty = Faculty::query()->firstOrCreate(
                ['university_id' => $this->university->getKey(), 'code' => $facultyCode],
                ['name' => self::FACULTIES[$facultyCode], 'is_active' => true],
            );
            StudyProgram::query()->firstOrCreate(
                ['university_id' => $this->university->getKey(), 'code' => $code],
                ['faculty_id' => $faculty->getKey(), 'name' => $name, 'degree_level' => $level, 'is_active' => true],
            );
        }

        return StudyProgram::query()->where('is_active', true)->with('faculty')->orderBy('name')->get()->map(function (StudyProgram $program) use ($catalog): array {
            [$duration, $upfront, $tuition] = self::LEVEL_DEFAULTS[$program->degree_level] ?? self::LEVEL_DEFAULTS['S1'];
            [, , , $title, $accreditation, $karyawan, $fees, $summary, $careers] = $catalog[$program->code]
                ?? [null, null, null, null, 'Baik Sekali', in_array($program->degree_level, ['S2', 'S3'], true), [$upfront, $tuition], null, null];
            $slug = Str::slug($program->degree_level.' '.$program->name);

            if (ProgramProfile::query()->where('slug', $slug)->where('study_program_id', '!=', $program->getKey())->exists()) {
                $slug .= '-'.Str::slug($program->code);
            }

            ProgramProfile::query()->firstOrCreate(['study_program_id' => $program->getKey()], [
                'university_id' => $this->university->getKey(), 'slug' => $slug,
                'summary' => $summary ?? "Program {$program->degree_level} {$program->name} di {$program->faculty?->name}.",
                'description' => '<p>'.($summary ?? "Program studi {$program->name} menyiapkan lulusan yang kompeten dan siap bersaing.").'</p><p>Perkuliahan memadukan teori, praktikum, dan proyek bersama mitra industri.</p>',
                'accreditation_label' => $accreditation, 'accreditation_valid_until' => now()->addYears(3)->toDateString(),
                'degree_title' => $title, 'study_duration' => $duration, 'career_prospects' => $careers, 'is_published' => true,
            ]);

            return ['program' => $program, 'karyawan' => $karyawan, 'fees' => $fees];
        });
    }

    /** Periode untuk tahun masuk berikutnya (Agustus), terpublikasi. */
    private function period(): Period
    {
        $year = now()->month >= 8 ? now()->year + 1 : now()->year;
        $term = AcademicTerm::query()->firstOrCreate(
            ['university_id' => $this->university->getKey(), 'academic_year' => $year.'/'.($year + 1), 'semester' => 'ganjil'],
            ['is_current' => false, 'start_date' => "{$year}-08-01", 'end_date' => ($year + 1).'-01-31'],
        );

        return Period::query()->firstOrCreate(['code' => 'PMB'.$year], [
            'university_id' => $this->university->getKey(), 'entry_academic_term_id' => $term->getKey(),
            'name' => 'PMB Tahun Akademik '.$year.'/'.($year + 1), 'description' => 'Penerimaan mahasiswa baru program sarjana dan pascasarjana.',
            'starts_on' => now()->subDays(45)->toDateString(), 'ends_on' => now()->addDays(240)->toDateString(),
            'status' => 'published', 'published_at' => now(),
        ]);
    }

    /**
     * G1 dibuka 30 hari lalu s.d. 30 hari lagi; G2 terjadwal setelahnya.
     * Urutan tanggal mengikuti BR-02 (PeriodService::validateWaveDates).
     *
     * @return array<string, Wave>
     */
    private function waves(Period $period): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $waves = [];

        foreach (['G1' => ['Gelombang 1', 1, -30, 30], 'G2' => ['Gelombang 2', 2, 45, 105]] as $code => [$name, $sequence, $opensIn, $closesIn]) {
            $opens = $today->addDays($opensIn)->setTime(8, 0);
            $closes = $today->addDays($closesIn)->setTime(23, 59);
            $announce = $closes->addDays(15)->setTime(10, 0);

            $waves[$code] = Wave::query()->firstOrCreate(['period_id' => $period->getKey(), 'code' => $code], [
                'university_id' => $this->university->getKey(), 'name' => $name, 'sequence' => $sequence,
                'registration_opens_at' => $opens, 'registration_closes_at' => $closes,
                'payment_deadline_at' => $closes->addDays(2), 'form_lock_at' => $closes->addDays(3),
                'exam_starts_on' => $closes->addDays(7)->toDateString(), 'exam_ends_on' => $closes->addDays(8)->toDateString(),
                'announcement_at' => $announce, 're_registration_opens_at' => $announce, 're_registration_closes_at' => $announce->addDays(14),
                'status' => $opens->isPast() ? 'open' : 'scheduled',
            ]);
        }

        return $waves;
    }

    private function formTemplate(): FormTemplate
    {
        return FormTemplate::query()->firstOrCreate(['code' => 'FORM-UMUM', 'version' => 1], [
            'university_id' => $this->university->getKey(), 'name' => 'Formulir Pendaftaran Umum',
            'status' => 'published', 'core_field_rules' => [], 'published_at' => now(),
        ]);
    }

    private function scheme(string $code): SelectionScheme
    {
        [$name, $passing, $components] = self::SCHEMES[$code];
        $scheme = SelectionScheme::query()->firstOrCreate(['code' => $code, 'version' => 1], [
            'university_id' => $this->university->getKey(), 'name' => $name, 'status' => 'locked', 'passing_score' => $passing,
            'allocation_method' => 'score_priority', 'tie_break_rules' => ['use_verified_at' => true], 'rounding_scale' => 2, 'locked_at' => now(),
        ]);

        foreach (array_keys($components) as $i => $componentCode) {
            [$componentName, $source, $weight] = $components[$componentCode];
            SelectionComponent::query()->firstOrCreate(['selection_scheme_id' => $scheme->getKey(), 'code' => $componentCode], [
                'university_id' => $this->university->getKey(), 'name' => $componentName, 'source' => $source, 'score_type' => 'numeric',
                'raw_min' => 0, 'raw_max' => 100, 'weight' => $weight, 'is_mandatory' => true, 'normalization' => 'minmax', 'sort_order' => $i + 1,
            ]);
        }

        return $scheme;
    }

    /**
     * @param  array<string, Wave>  $waves
     * @param  Collection<int, array{program: StudyProgram, karyawan: bool, fees: array{0: int, 1: int}}>  $programs
     * @param  Collection<string, string>  $systems
     */
    private function wavePathsAndQuotas(AdmissionPath $path, Period $period, array $waves, FormTemplate $form, Collection $programs, Collection $systems): int
    {
        [$fee, $maxChoices, $schemeCode, $waveCodes, $allowsKaryawan, $documents, $notes] = self::WAVE_PATHS[$path->code];
        $scheme = $this->scheme($schemeCode);
        $documentTypes = DocumentType::query()->pluck('id', 'code');

        foreach ($waveCodes as $waveCode) {
            $wavePath = WavePath::query()->firstOrCreate(['wave_id' => $waves[$waveCode]->getKey(), 'admission_path_id' => $path->getKey()], [
                'university_id' => $this->university->getKey(), 'registration_fee' => $fee, 'payment_timing' => 'before_submit',
                'max_choices' => $maxChoices, 'form_template_id' => $form->getKey(), 'selection_scheme_id' => $scheme->getKey(),
                'quota_mode' => 'period', 'is_open' => true, 'public_notes' => $notes,
            ]);

            foreach ([...array_map(fn ($c) => [$c, 'application'], $documents), ['STATEMENT_LETTER', 're_registration']] as $i => [$documentCode, $stage]) {
                DocumentRequirement::query()->firstOrCreate(
                    ['wave_path_id' => $wavePath->getKey(), 'document_type_id' => $documentTypes[$documentCode], 'stage' => $stage],
                    ['university_id' => $this->university->getKey(), 'is_mandatory' => true, 'sort_order' => $i + 1],
                );
            }
        }

        $created = 0;

        foreach ($programs as ['program' => $program, 'karyawan' => $karyawan]) {
            foreach ($allowsKaryawan && $karyawan ? ['REG', 'KRY'] : ['REG'] as $systemCode) {
                $quota = Quota::query()->firstOrCreate([
                    'period_id' => $period->getKey(), 'admission_path_id' => $path->getKey(),
                    'study_program_id' => $program->getKey(), 'study_system_id' => $systems[$systemCode],
                ], [
                    'university_id' => $this->university->getKey(), 'capacity' => self::CAPACITY[$path->code],
                    'waitlist_capacity' => (int) ceil(self::CAPACITY[$path->code] * 0.2), 'is_offered' => true,
                ]);
                $created += (int) $quota->wasRecentlyCreated;
            }
        }

        return $created;
    }

    /**
     * Biaya per prodi & sistem kuliah, berlaku untuk semua jalur (admission_path_id null).
     *
     * @param  Collection<int, array{program: StudyProgram, karyawan: bool, fees: array{0: int, 1: int}}>  $programs
     * @param  Collection<string, string>  $systems
     */
    private function fees(Period $period, Collection $programs, Collection $systems): int
    {
        $created = 0;

        foreach ($programs as ['program' => $program, 'karyawan' => $karyawan, 'fees' => [$upfront, $tuition]]) {
            foreach ($karyawan ? ['REG' => 1.0, 'KRY' => 1.1] : ['REG' => 1.0] as $systemCode => $factor) {
                $components = [
                    ['UP', 'Uang Pangkal (DPP)', $upfront, true, [['percent' => 50, 'due_days' => 0], ['percent' => 25, 'due_days' => 60], ['percent' => 25, 'due_days' => 120]]],
                    ['SPP1', 'SPP Semester 1', (int) round($tuition * $factor, -3), false, null],
                    ['ORIENTASI', 'Orientasi & Jas Almamater', 750_000, false, null],
                ];

                foreach ($components as $i => [$code, $name, $amount, $installable, $plan]) {
                    $fee = FeeComponent::query()->firstOrCreate([
                        'period_id' => $period->getKey(), 'study_program_id' => $program->getKey(),
                        'study_system_id' => $systems[$systemCode], 'admission_path_id' => null, 'component_code' => $code,
                    ], [
                        'university_id' => $this->university->getKey(), 'name' => $name, 'amount' => $amount,
                        'installable' => $installable, 'installment_plan' => $plan, 'is_published' => true, 'sort_order' => $i + 1,
                    ]);
                    $created += (int) $fee->wasRecentlyCreated;
                }
            }
        }

        return $created;
    }

    private function contents(): void
    {
        foreach (self::CONTENTS as $i => [$type, $slug, $title, $category, $body]) {
            Content::query()->firstOrCreate(['type' => $type, 'slug' => $slug, 'version' => 1], [
                'university_id' => $this->university->getKey(), 'title' => $title, 'body' => $body, 'category' => $category,
                'status' => 'published', 'publish_at' => now()->subDays(count(self::CONTENTS) - $i), 'sort_order' => $i + 1,
            ]);
        }
    }
}

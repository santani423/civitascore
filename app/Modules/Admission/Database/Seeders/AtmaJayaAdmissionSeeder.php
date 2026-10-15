<?php

namespace Modules\Admission\Database\Seeders;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\ExamMode;
use Modules\Admission\Enums\ExamScheduleStatus;
use Modules\Admission\Enums\PathCategory;
use Modules\Admission\Enums\PeriodStatus;
use Modules\Admission\Enums\SchemeStatus;
use Modules\Admission\Enums\TemplateStatus;
use Modules\Admission\Enums\WaveStatus;
use Modules\Admission\Models\AdmissionPath;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\CbtQuestion;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\DocumentType;
use Modules\Admission\Models\ExamRoom;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\ExamVenue;
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
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\Admission\Services\Configuration\PeriodService;
use Modules\Admission\Services\Configuration\SelectionSchemeService;
use Modules\Admission\Support\HtmlSanitizer;
use Modules\Admission\Support\PmbSettings;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\PlatformModule;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UniversityModule;
use Modules\Tenancy\Models\UniversitySetting;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;
use Modules\UserManagement\Support\PermissionRegistry;
use RuntimeException;

/**
 * Konfigurasi PMB siap pakai untuk kampus Atma Jaya (UAJ): modul admission
 * aktif, staf per role PMB, prodi + profil publik, jalur, formulir, skema
 * seleksi (terkunci), periode PMB 2027/2028 terpublikasi dengan dua
 * gelombang, kuota, biaya, persyaratan dokumen, ujian CBT + bank soal,
 * konten website (legal, FAQ, berita, panduan), dan satu akun pendaftar demo.
 *
 * Tanggal gelombang relatif terhadap waktu seeding (Gelombang 1 langsung
 * dibuka). Dijalankan ulang aman: data yang sudah ada TIDAK ditimpa (kunci
 * alami + firstOrCreate), sehingga perubahan admin tetap terjaga.
 *
 * university_id selalu diisi eksplisit dan konfigurasi dibuat langsung lewat
 * model (bukan service): DatabaseSeeder memakai WithoutModelEvents sehingga
 * pengisian otomatis TenantScoped tidak berjalan (lihat AdmissionSeeder).
 * Aturan service tetap ditegakkan lewat lockErrors() & publishChecklist().
 *
 *   php artisan db:seed --class="Modules\Admission\Database\Seeders\AtmaJayaAdmissionSeeder"
 */
class AtmaJayaAdmissionSeeder extends Seeder
{
    private const UNIVERSITY_CODE = 'UAJ';

    private const STAFF_DOMAIN = 'atmajaya.com';

    private const PERIOD_CODE = 'PMB2027';

    public const DEMO_APPLICANT_EMAIL = 'calon.mahasiswa@example.com';

    public const DEMO_APPLICANT_PASSWORD = 'Pendaftar-2027!';

    /** email local-part => [nama, role] — kata sandi "password" seperti akun demo lain. */
    private const STAFF = [
        'pmb.admin' => ['Administrator PMB Atma Jaya', 'admission_administrator'],
        'pmb.pengesah' => ['Pejabat Pengesah PMB Atma Jaya', 'admission_approver'],
        'pmb.verifikator' => ['Petugas Verifikasi PMB Atma Jaya', 'admission_verifier'],
        'pmb.seleksi' => ['Panitia Seleksi PMB Atma Jaya', 'admission_selection_committee'],
        'pmb.daftarulang' => ['Petugas Daftar Ulang PMB Atma Jaya', 'admission_registrar'],
    ];

    private const FACULTIES = [
        'TEK' => 'Fakultas Teknik',
        'FEB' => 'Fakultas Ekonomi dan Bisnis',
        'FH' => 'Fakultas Hukum',
        'FPSI' => 'Fakultas Psikologi',
        'FIABIKOM' => 'Fakultas Ilmu Administrasi Bisnis dan Ilmu Komunikasi',
    ];

    /**
     * TI1 sudah dibuat AtmaJayaStudentSeeder (tidak diubah).
     * kode => [fakultas, nama, gelar, akreditasi, SPP/semester, Dana Pengembangan, ringkasan, prospek karier]
     */
    private const PROGRAMS = [
        'TI1' => ['TEK', 'Teknik Informatika', 'S.Kom.', 'Unggul', 9_500_000, 25_000_000,
            'Rekayasa perangkat lunak, kecerdasan buatan, dan keamanan siber dengan pembelajaran berbasis proyek.',
            'Software engineer, data scientist, analis keamanan siber, konsultan TI'],
        'TIN' => ['TEK', 'Teknik Industri', 'S.T.', 'Unggul', 9_000_000, 22_000_000,
            'Perancangan dan optimasi sistem produksi, rantai pasok, serta manajemen kualitas.',
            'Supply chain analyst, process engineer, quality engineer, konsultan operasi'],
        'MNJ' => ['FEB', 'Manajemen', 'S.M.', 'Unggul', 8_500_000, 20_000_000,
            'Manajemen pemasaran, keuangan, SDM, dan kewirausahaan dengan studi kasus industri.',
            'Manajer pemasaran, analis keuangan, HR business partner, wirausaha'],
        'AKT' => ['FEB', 'Akuntansi', 'S.Ak.', 'Unggul', 8_500_000, 20_000_000,
            'Akuntansi keuangan, audit, perpajakan, dan sistem informasi akuntansi.',
            'Auditor, konsultan pajak, akuntan publik, analis keuangan'],
        'HKM' => ['FH', 'Ilmu Hukum', 'S.H.', 'Unggul', 8_000_000, 18_000_000,
            'Hukum bisnis, hukum pidana, dan hukum tata negara dengan klinik hukum.',
            'Advokat, notaris, legal officer korporasi, hakim, jaksa'],
        'PSI' => ['FPSI', 'Psikologi', 'S.Psi.', 'Unggul', 8_500_000, 20_000_000,
            'Psikologi klinis, pendidikan, serta industri dan organisasi.',
            'HR specialist, konselor, peneliti perilaku, asesor psikologi'],
        'KOM' => ['FIABIKOM', 'Ilmu Komunikasi', 'S.I.Kom.', 'Baik Sekali', 8_000_000, 18_000_000,
            'Komunikasi strategis, jurnalistik, dan media digital.',
            'Public relations, content strategist, jurnalis, digital marketer'],
    ];

    /** Prodi yang juga membuka Kelas Karyawan (KRY): kode => [SPP/semester, Dana Pengembangan]. */
    private const EMPLOYEE_CLASS = [
        'MNJ' => [7_500_000, 15_000_000],
        'HKM' => [7_000_000, 14_000_000],
    ];

    private const ORIENTATION_FEE = 1_500_000;

    /** kode => [nama, kategori, wajib ujian, deskripsi, biaya pendaftaran, skema, kuota REG, cadangan] */
    private const PATHS = [
        'RAPOR' => ['Jalur Rapor', PathCategory::Regular, false,
            'Seleksi berdasarkan nilai rapor semester 1–5 tanpa ujian tulis.', 300_000, 'SCH-RAPOR', 40, 10],
        'USM' => ['Ujian Saringan Masuk (CBT)', PathCategory::Regular, true,
            'Seleksi melalui Tes Potensi Akademik berbasis komputer (CBT) di kampus, dikombinasikan dengan nilai rapor.', 350_000, 'SCH-USM', 60, 15],
        'PRESTASI' => ['Jalur Prestasi', PathCategory::Achievement, false,
            'Untuk siswa berprestasi akademik/non-akademik minimal tingkat kabupaten/kota. Potongan Dana Pengembangan 50%.', 250_000, 'SCH-PRESTASI', 10, 5],
    ];

    /** Kuota Kelas Karyawan per prodi (hanya jalur Rapor & USM). */
    private const EMPLOYEE_CLASS_QUOTA = [30, 10];

    /**
     * kode skema => [nama, ambang skor, komponen[kode, nama, sumber, bobot, prioritas tie-break, config]]
     * Komponen ADM (administratif, lulus/gagal) selalu ditambahkan pertama.
     */
    private const SCHEMES = [
        'SCH-RAPOR' => ['Skema Jalur Rapor', 65, [
            ['RAPOR', 'Nilai Rapor', ComponentSource::ReportCard, 100, 1, ['semesters' => [1, 2, 3, 4, 5]]],
        ]],
        'SCH-USM' => ['Skema Ujian Saringan Masuk', 60, [
            ['TPA', 'Tes Potensi Akademik (CBT)', ComponentSource::Cbt, 70, 1, null],
            ['RAPOR', 'Nilai Rapor', ComponentSource::ReportCard, 30, 2, ['semesters' => [1, 2, 3, 4, 5]]],
        ]],
        'SCH-PRESTASI' => ['Skema Jalur Prestasi', 60, [
            ['PRESTASI', 'Nilai Prestasi', ComponentSource::Achievement, 60, 1, ['max_items' => 3, 'cap' => 100]],
            ['RAPOR', 'Nilai Rapor', ComponentSource::ReportCard, 40, 2, ['semesters' => [1, 2, 3, 4, 5]]],
        ]],
    ];

    /** [kode dokumen, tahap, wajib, instruksi] — berlaku untuk semua jalur. */
    private const DOCUMENTS = [
        ['PHOTO', DocumentStage::Application, true, 'Pas foto berwarna terbaru, latar polos, tampak wajah jelas.'],
        ['ID_CARD', DocumentStage::Application, true, 'KTP atau Kartu Pelajar yang masih berlaku.'],
        ['REPORT_CARD', DocumentStage::Application, true, 'Rapor semester 1–5 digabung menjadi satu PDF.'],
        ['FAMILY_CARD', DocumentStage::ReRegistration, true, 'Kartu Keluarga terbaru.'],
        ['DIPLOMA_OR_SKL', DocumentStage::ReRegistration, true, 'Ijazah atau Surat Keterangan Lulus dari sekolah.'],
        ['STATEMENT_LETTER', DocumentStage::ReRegistration, true, 'Surat pernyataan bermeterai (format tersedia di halaman Panduan).'],
        ['HEALTH_CERT', DocumentStage::ReRegistration, false, 'Surat keterangan sehat dari dokter/puskesmas.'],
    ];

    /** Dokumen tambahan per jalur. */
    private const PATH_DOCUMENTS = [
        'PRESTASI' => [['ACHIEVEMENT_CERT', DocumentStage::Application, true, 'Sertifikat/piagam prestasi tingkat kabupaten/kota atau lebih tinggi (maks. 3).']],
    ];

    /** [pertanyaan, opsi A–D, kunci] — soal TPA contoh untuk CBT. */
    private const CBT_QUESTIONS = [
        ['Lanjutkan deret berikut: 2, 6, 12, 20, 30, ...', ['40', '42', '44', '48'], 'B'],
        ['Jika semua mahasiswa rajin membaca dan Rina adalah mahasiswa, maka ...', ['Rina mungkin rajin membaca', 'Rina rajin membaca', 'Rina tidak rajin membaca', 'Tidak dapat disimpulkan'], 'B'],
        ['Sinonim kata "EFISIEN" adalah ...', ['Berdaya guna', 'Cepat', 'Murah', 'Lengkap'], 'A'],
        ['Antonim kata "EKSPLISIT" adalah ...', ['Tersurat', 'Tersirat', 'Jelas', 'Rinci'], 'B'],
        ['Harga 3 buku Rp45.000. Berapa harga 7 buku yang sama?', ['Rp95.000', 'Rp100.000', 'Rp105.000', 'Rp110.000'], 'C'],
        ['DOKTER : PASIEN = GURU : ...', ['Sekolah', 'Murid', 'Buku', 'Kelas'], 'B'],
        ['Sebuah mobil menempuh 180 km dalam 3 jam. Kecepatan rata-ratanya adalah ...', ['50 km/jam', '55 km/jam', '60 km/jam', '65 km/jam'], 'C'],
        ['25% dari 480 adalah ...', ['100', '110', '120', '125'], 'C'],
        ['Lanjutkan deret huruf: A, C, F, J, O, ...', ['S', 'T', 'U', 'V'], 'C'],
        ['Jika x + 2y = 10 dan x = 4, maka y = ...', ['2', '3', '4', '6'], 'B'],
        ['Kata yang tidak sekelompok: Mawar, Melati, Anggrek, Beringin', ['Mawar', 'Melati', 'Anggrek', 'Beringin'], 'D'],
        ['Rata-rata dari 70, 80, dan 90 adalah ...', ['75', '80', '85', '90'], 'B'],
    ];

    private University $university;

    private User $admin;

    public function run(): void
    {
        $university = University::query()->where('code', self::UNIVERSITY_CODE)->first();

        if ($university === null) {
            $this->command?->warn('Universitas UAJ belum ada — jalankan DemoUniversitiesSeeder terlebih dahulu.');

            return;
        }

        $this->university = $university;
        $tenant = app(TenantContext::class);
        $previous = $tenant->universityId();
        $tenant->setUniversityId($university->getKey());

        try {
            DB::transaction(function (): void {
                $this->enableAdmissionModule();
                (new AdmissionSeeder)->run($this->university);
                $this->admin = $this->seedStaff();
                $programs = $this->seedStudyPrograms();
                $systems = StudySystem::query()->whereIn('code', ['REG', 'KRY'])->get()->keyBy('code');
                $this->seedSettings();
                $this->seedContents();
                $this->seedProgramProfiles($programs);

                $paths = $this->seedAdmissionPaths();
                $template = $this->seedFormTemplate();
                $schemes = $this->seedSelectionSchemes();

                $period = Period::query()->where('code', self::PERIOD_CODE)->first();

                if ($period === null) {
                    $this->seedPeriod($paths, $template, $schemes, $programs, $systems);
                }

                $this->seedDemoApplicant();
            });
        } finally {
            $tenant->setUniversityId($previous);
        }

        foreach (['site', 'periods', 'programs', 'paths'] as $key) {
            Cache::forget('pmb:public:'.$university->getKey().':'.$key);
        }

        PermissionRegistry::flushAll();
    }

    private function enableAdmissionModule(): void
    {
        $module = PlatformModule::query()->where('key', 'admission')->first();

        if ($module !== null) {
            UniversityModule::query()->updateOrCreate(
                ['university_id' => $this->university->getKey(), 'module_id' => $module->getKey()],
                ['is_enabled' => true, 'enabled_at' => now()],
            );
        }
    }

    /** @return User administrator PMB (pembuat konfigurasi) */
    private function seedStaff(): User
    {
        $users = [];

        foreach (self::STAFF as $localPart => [$name, $roleSlug]) {
            $user = User::query()->firstOrCreate(
                ['email' => $localPart.'@'.self::STAFF_DOMAIN],
                ['name' => $name, 'password' => 'password', 'email_verified_at' => now(), 'is_active' => true],
            );

            UserUniversity::query()->firstOrCreate(
                ['user_id' => $user->getKey(), 'university_id' => $this->university->getKey()],
                ['membership_type' => MembershipType::Staff, 'status' => MembershipStatus::Active, 'joined_at' => now(), 'is_default' => true],
            );

            $role = Role::query()->where('slug', $roleSlug)->whereNull('university_id')->firstOrFail();
            UserRole::query()->firstOrCreate(
                ['user_id' => $user->getKey(), 'role_id' => $role->getKey(), 'university_id' => $this->university->getKey(), 'scope_type' => null, 'scope_id' => null],
                ['assigned_at' => now()],
            );

            $users[$roleSlug] = $user;
        }

        return $users['admission_administrator'];
    }

    /** @return array<string, StudyProgram> kode => prodi */
    private function seedStudyPrograms(): array
    {
        $faculties = [];

        foreach (self::FACULTIES as $code => $name) {
            $faculties[$code] = Faculty::query()->firstOrCreate(
                ['university_id' => $this->university->getKey(), 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        $programs = [];

        foreach (self::PROGRAMS as $code => [$faculty, $name]) {
            $programs[$code] = StudyProgram::query()->firstOrCreate(
                ['university_id' => $this->university->getKey(), 'code' => $code],
                ['faculty_id' => $faculties[$faculty]->getKey(), 'name' => $name, 'degree_level' => 'S1', 'is_active' => true],
            );
        }

        return $programs;
    }

    /** Pengaturan PMB hanya diisi bila tenant belum mengaturnya. */
    private function seedSettings(): void
    {
        $settings = app(PmbSettings::class);
        $defaults = [
            'pmb.contact' => [
                'address' => 'Kampus Semanggi, Jl. Jenderal Sudirman No. 51, Jakarta Selatan 12930',
                'phone' => '(021) 0000-0000',
                'email' => 'pmb@'.self::STAFF_DOMAIN,
                'whatsapp' => '0800-0000-0000',
                'hours' => 'Senin–Jumat 08.00–16.00 WIB, Sabtu 08.00–12.00 WIB',
                'map_url' => 'https://maps.google.com/?q=Universitas+Katolik+Indonesia+Atma+Jaya+Semanggi',
            ],
            'pmb.show_quota_status' => true,
            // Kunci bank/number/holder dibaca PaymentService::manualInstructions().
            'pmb.payment.bank_accounts' => [
                ['bank' => 'Bank Demo', 'number' => '000-000-0000', 'holder' => $this->university->name],
            ],
            'pmb.institution_email_domain' => 'student.'.self::STAFF_DOMAIN,
        ];

        foreach ($defaults as $key => $value) {
            $exists = UniversitySetting::query()->where('university_id', $this->university->getKey())->where('key', $key)->exists();

            if (! $exists) {
                $settings->update([$key => $value], (string) $this->admin->getKey());
            }
        }
    }

    private function seedContents(): void
    {
        $legal = [
            [ContentType::PrivacyPolicy, 'Kebijakan Privasi PMB', '<p>Data pribadi yang Anda isi pada sistem PMB Universitas Katolik Indonesia Atma Jaya hanya digunakan untuk proses seleksi, pengumuman, dan daftar ulang.</p><p>Dokumen identitas disimpan terenkripsi dan hanya dapat diakses petugas yang berwenang. Data pendaftar yang tidak menjadi mahasiswa dihapus setelah masa retensi berakhir.</p>'],
            [ContentType::Terms, 'Ketentuan Pendaftaran PMB', '<ol><li>Pendaftar wajib mengisi data dengan benar dan dapat dipertanggungjawabkan.</li><li>Biaya pendaftaran yang telah dibayarkan tidak dapat dikembalikan, kecuali gelombang dibatalkan oleh panitia.</li><li>Pemalsuan dokumen mengakibatkan pembatalan kelulusan.</li><li>Keputusan panitia seleksi bersifat final.</li></ol>'],
        ];

        foreach ($legal as $i => [$type, $title, $body]) {
            $this->content($type, $type->value, $title, $body, null, $i);
        }

        $faqs = [
            ['Pendaftaran', 'Apakah saya boleh memilih lebih dari satu program studi?', 'Ya. Setiap jalur mengizinkan hingga dua pilihan program studi dengan urutan prioritas.'],
            ['Pendaftaran', 'Apakah lulusan tahun sebelumnya dapat mendaftar?', 'Dapat, untuk lulusan maksimal dua tahun terakhir pada semua jalur.'],
            ['Pembayaran', 'Bagaimana cara membayar biaya pendaftaran?', 'Setelah formulir dikirim, tagihan muncul di portal. Bayar melalui transfer bank lalu unggah bukti transfer, atau bayar tunai di loket kampus.'],
            ['Pembayaran', 'Berapa lama pembayaran diverifikasi?', 'Bukti transfer diverifikasi bagian keuangan maksimal 1×24 jam kerja.'],
            ['Seleksi', 'Kapan jadwal ujian CBT untuk jalur USM?', 'Jadwal dan sesi ujian tercantum di kartu peserta yang dapat diunduh di portal setelah berkas diverifikasi.'],
            ['Daftar Ulang', 'Apa yang harus dilakukan setelah dinyatakan lulus?', 'Konfirmasi penerimaan di portal, lengkapi data daftar ulang, bayar biaya awal, lalu unggah dokumen daftar ulang sebelum batas waktu.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            $this->content(ContentType::Faq, Str::slug($question), $question, '<p>'.$answer.'</p>', $category, $i);
        }

        $news = [
            ['PMB Atma Jaya 2027/2028 Resmi Dibuka', 'Pendaftaran mahasiswa baru tahun akademik 2027/2028 resmi dibuka melalui Jalur Rapor, Ujian Saringan Masuk (CBT), dan Jalur Prestasi. Gelombang 1 dibuka mulai hari ini.', 1],
            ['Jalur Prestasi: Potongan Dana Pengembangan 50%', 'Calon mahasiswa dengan prestasi minimal tingkat kabupaten/kota berkesempatan memperoleh potongan Dana Pengembangan Pendidikan sebesar 50%.', 3],
            ['Simulasi Ujian CBT untuk Peserta USM', 'Peserta jalur Ujian Saringan Masuk dapat mempelajari contoh soal Tes Potensi Akademik di halaman Panduan sebelum hari ujian.', 5],
        ];

        foreach ($news as $i => [$title, $body, $daysAgo]) {
            $this->content(ContentType::News, Str::slug($title), $title, '<p>'.$body.'</p>', 'Pengumuman', $i, now()->subDays($daysAgo));
        }

        $guides = [
            ['Cara Mendaftar', '<ol><li>Buat akun di menu <strong>Daftar</strong> dan verifikasi email.</li><li>Pilih gelombang dan jalur yang sedang dibuka.</li><li>Lengkapi biodata, riwayat pendidikan, nilai rapor, dan pilihan program studi.</li><li>Unggah dokumen persyaratan.</li><li>Kirim formulir, lalu bayar biaya pendaftaran.</li></ol>'],
            ['Cara Membayar Biaya Pendaftaran', '<ol><li>Buka menu Tagihan di portal pendaftar.</li><li>Pilih metode Transfer Bank atau Loket Kampus.</li><li>Untuk transfer, bayar tepat sesuai nominal (termasuk kode unik) lalu unggah bukti transfer.</li></ol>'],
            ['Panduan Ujian CBT', '<ul><li>Datang 30 menit sebelum sesi dimulai dengan membawa kartu peserta dan KTP/Kartu Pelajar.</li><li>Ujian berlangsung 60 menit dengan 10 soal pilihan ganda.</li><li>Dilarang membuka aplikasi lain selama ujian.</li></ul>'],
        ];

        foreach ($guides as $i => [$title, $body]) {
            $this->content(ContentType::Guide, Str::slug($title), $title, $body, null, $i);
        }

        $this->content(ContentType::Banner, 'gelombang-1-dibuka', 'Pendaftaran Gelombang 1 Dibuka', '<p>Daftar sekarang melalui Jalur Rapor, USM (CBT), atau Jalur Prestasi.</p>', null, 0);
        $this->content(ContentType::ReRegistrationInfo, 'info-daftar-ulang', 'Informasi Daftar Ulang', '<p>Peserta yang dinyatakan diterima wajib melakukan daftar ulang sesuai jadwal gelombang: konfirmasi penerimaan, lengkapi data, bayar biaya awal minimal cicilan pertama, dan serahkan dokumen asli untuk diperiksa petugas.</p>', null, 0);
    }

    private function content(ContentType $type, string $slug, string $title, string $body, ?string $category, int $sort, ?\DateTimeInterface $publishAt = null): void
    {
        Content::query()->firstOrCreate(
            ['type' => $type->value, 'slug' => $slug, 'version' => 1],
            [
                'university_id' => $this->university->getKey(), 'title' => $title, 'body' => HtmlSanitizer::clean($body), 'category' => $category,
                'status' => ContentStatus::Published, 'publish_at' => $publishAt ?? now(), 'sort_order' => $sort,
                'seo_title' => $title.' — PMB Atma Jaya', 'created_by' => $this->admin->getKey(), 'updated_by' => $this->admin->getKey(),
            ],
        );
    }

    /** @param  array<string, StudyProgram>  $programs */
    private function seedProgramProfiles(array $programs): void
    {
        foreach (self::PROGRAMS as $code => [, $name, $degreeTitle, $accreditation, , , $summary, $careers]) {
            ProgramProfile::query()->firstOrCreate(
                ['study_program_id' => $programs[$code]->getKey()],
                [
                    'university_id' => $this->university->getKey(),
                    'slug' => Str::slug($name),
                    'summary' => $summary,
                    'description' => HtmlSanitizer::clean('<p>Program Studi '.$name.' Universitas Katolik Indonesia Atma Jaya. '.$summary.'</p>'),
                    'accreditation_label' => $accreditation,
                    'accreditation_valid_until' => now()->addYears(4)->toDateString(),
                    'degree_title' => $degreeTitle,
                    'study_duration' => '4 tahun (8 semester)',
                    'career_prospects' => $careers,
                    'is_published' => true,
                    'updated_by' => $this->admin->getKey(),
                ],
            );
        }
    }

    /** @return array<string, AdmissionPath> */
    private function seedAdmissionPaths(): array
    {
        $paths = [];

        foreach (self::PATHS as $code => [$name, $category, $requiresExam, $description]) {
            $paths[$code] = AdmissionPath::query()->firstOrCreate(['code' => $code], [
                'university_id' => $this->university->getKey(), 'name' => $name, 'category' => $category, 'description' => $description,
                'eligible_degree_levels' => ['S1'], 'requires_exam' => $requiresExam, 'requires_previous_institution' => false, 'is_active' => true,
                'created_by' => $this->admin->getKey(), 'updated_by' => $this->admin->getKey(),
            ]);
        }

        return $paths;
    }

    private function seedFormTemplate(): FormTemplate
    {
        return FormTemplate::query()->firstOrCreate(['code' => 'FORM-PMB', 'version' => 1], [
            'university_id' => $this->university->getKey(), 'name' => 'Formulir Pendaftaran PMB Atma Jaya', 'status' => TemplateStatus::Published,
            'core_field_rules' => [], 'published_at' => now(), 'created_by' => $this->admin->getKey(), 'updated_by' => $this->admin->getKey(),
        ]);
    }

    /** @return array<string, SelectionScheme> */
    private function seedSelectionSchemes(): array
    {
        $service = app(SelectionSchemeService::class);
        $schemes = [];

        foreach (self::SCHEMES as $code => [$name, $passingScore, $components]) {
            $scheme = SelectionScheme::query()->where('code', $code)->where('version', 1)->first();

            if ($scheme === null) {
                $scheme = SelectionScheme::query()->create([
                    'university_id' => $this->university->getKey(), 'code' => $code, 'name' => $name, 'version' => 1, 'status' => SchemeStatus::Draft,
                    'passing_score' => $passingScore, 'allocation_method' => 'score_priority', 'tie_break_rules' => ['use_verified_at' => true],
                    'rounding_scale' => 2, 'created_by' => $this->admin->getKey(), 'updated_by' => $this->admin->getKey(),
                ]);

                $rows = [['ADM', 'Seleksi Administrasi', ComponentSource::Administrative, 0, null, null], ...$components];

                foreach ($rows as $i => [$componentCode, $componentName, $source, $weight, $tieBreak, $config]) {
                    $passFail = $source === ComponentSource::Administrative;
                    SelectionComponent::query()->create([
                        'university_id' => $this->university->getKey(), 'selection_scheme_id' => $scheme->getKey(),
                        'code' => $componentCode, 'name' => $componentName, 'source' => $source,
                        'score_type' => $passFail ? 'pass_fail' : 'numeric', 'raw_min' => $passFail ? null : 0, 'raw_max' => $passFail ? null : 100,
                        'weight' => $weight, 'is_mandatory' => true, 'tie_break_priority' => $tieBreak, 'normalization' => 'minmax',
                        'config' => $config, 'sort_order' => $i,
                    ]);
                }

                $errors = $service->lockErrors($scheme, $scheme->components()->get()->all());

                if ($errors !== []) {
                    throw new RuntimeException("Skema {$code} tidak dapat dikunci: ".implode(' ', $errors));
                }

                $scheme->forceFill(['status' => SchemeStatus::Locked, 'locked_at' => now(), 'locked_by' => $this->admin->getKey()])->save();
            }

            $schemes[$code] = $scheme;
        }

        return $schemes;
    }

    /**
     * Periode + gelombang + gelombang-jalur + kuota + biaya + dokumen + ujian,
     * lalu dipublikasikan setelah lolos daftar periksa publikasi.
     *
     * @param  array<string, AdmissionPath>  $paths
     * @param  array<string, SelectionScheme>  $schemes
     * @param  array<string, StudyProgram>  $programs
     * @param  Collection<string, StudySystem>  $systems
     */
    private function seedPeriod(array $paths, FormTemplate $template, array $schemes, array $programs, $systems): void
    {
        $uid = $this->university->getKey();
        $adminId = $this->admin->getKey();
        $term = AcademicTerm::query()->firstOrCreate(
            ['university_id' => $uid, 'academic_year' => '2027/2028', 'semester' => AcademicSemester::Ganjil],
            ['is_current' => false, 'start_date' => '2027-08-01', 'end_date' => '2028-01-31'],
        );

        $today = CarbonImmutable::now($this->university->timezone ?: 'Asia/Jakarta')->startOfDay();
        $at = fn (int $days, int $hour = 23, int $minute = 59) => $today->addDays($days)->setTime($hour, $minute)->setTimezone(config('app.timezone'));

        $period = Period::query()->create([
            'university_id' => $uid, 'entry_academic_term_id' => $term->getKey(), 'code' => self::PERIOD_CODE, 'name' => 'PMB 2027/2028',
            'description' => 'Penerimaan mahasiswa baru program sarjana tahun akademik 2027/2028.',
            'starts_on' => $today->subDays(14)->toDateString(), 'ends_on' => $today->addDays(180)->toDateString(),
            'status' => PeriodStatus::Draft, 'created_by' => $adminId, 'updated_by' => $adminId,
        ]);

        // Gelombang 1 sedang dibuka; Gelombang 2 menyusul tanpa tumpang tindih (BR-02).
        $waves = [
            ['G1', 'Gelombang 1', 1, -7, 30],
            ['G2', 'Gelombang 2', 2, 31, 75],
        ];
        $venue = $this->seedExamVenue();

        foreach ($waves as [$code, $name, $sequence, $opens, $closes]) {
            $wave = Wave::query()->create([
                'university_id' => $uid, 'period_id' => $period->getKey(), 'code' => $code, 'name' => $name, 'sequence' => $sequence,
                'registration_opens_at' => $at($opens, 8, 0), 'registration_closes_at' => $at($closes),
                'payment_deadline_at' => $at($closes + 2), 'form_lock_at' => $at($closes + 3),
                'exam_starts_on' => $today->addDays($closes + 5)->toDateString(), 'exam_ends_on' => $today->addDays($closes + 6)->toDateString(),
                'announcement_at' => $at($closes + 10, 10, 0),
                're_registration_opens_at' => $at($closes + 10, 10, 0), 're_registration_closes_at' => $at($closes + 25),
                'is_ods' => false, 'status' => WaveStatus::Draft, 'created_by' => $adminId, 'updated_by' => $adminId,
            ]);

            foreach (self::PATHS as $pathCode => [$pathName, , , , $fee, $schemeCode]) {
                $wavePath = WavePath::query()->create([
                    'university_id' => $uid, 'wave_id' => $wave->getKey(), 'admission_path_id' => $paths[$pathCode]->getKey(),
                    'registration_fee' => number_format($fee, 2, '.', ''), 'payment_timing' => 'after_submit', 'max_choices' => 2,
                    'allow_cross_system_choice' => false, 'form_template_id' => $template->getKey(), 'selection_scheme_id' => $schemes[$schemeCode]->getKey(),
                    'quota_mode' => 'period', 'bulk_verifiable' => false, 'is_open' => true,
                    'public_notes' => "{$pathName} {$name}: maksimal 2 pilihan program studi.",
                    'created_by' => $adminId, 'updated_by' => $adminId,
                ]);

                $this->seedDocumentRequirements($wavePath, $pathCode);

                if ($pathCode === 'USM') {
                    $this->seedCbtSchedule($wave, $wavePath, $schemes[$schemeCode], $venue);
                }
            }
        }

        $this->seedQuotas($period, $paths, $programs, $systems);
        $this->seedFees($period, $paths, $programs, $systems);

        $periods = app(PeriodService::class);
        $missing = $periods->publishChecklist($period);

        if ($missing !== []) {
            throw new RuntimeException('Periode PMB belum siap dipublikasikan: '.implode(' ', $missing));
        }

        // Sama dengan PeriodService::publishPeriod(): draft → published, gelombang draft → scheduled → open sesuai tanggal.
        $period->forceFill(['status' => PeriodStatus::Published, 'published_at' => now()])->save();
        Wave::query()->where('period_id', $period->getKey())->where('status', WaveStatus::Draft->value)->update(['status' => WaveStatus::Scheduled->value]);
        $periods->refreshWaveStatuses($period);
    }

    private function seedDocumentRequirements(WavePath $wavePath, string $pathCode): void
    {
        $types = DocumentType::query()->get()->keyBy('code');
        $rows = [...self::DOCUMENTS, ...(self::PATH_DOCUMENTS[$pathCode] ?? [])];

        foreach ($rows as $i => [$code, $stage, $mandatory, $instructions]) {
            DocumentRequirement::query()->create([
                'university_id' => $this->university->getKey(), 'wave_path_id' => $wavePath->getKey(), 'document_type_id' => $types[$code]->getKey(),
                'stage' => $stage, 'is_mandatory' => $mandatory, 'instructions' => $instructions, 'sort_order' => $i,
            ]);
        }
    }

    /**
     * @param  array<string, AdmissionPath>  $paths
     * @param  array<string, StudyProgram>  $programs
     * @param  Collection<string, StudySystem>  $systems
     */
    private function seedQuotas(Period $period, array $paths, array $programs, $systems): void
    {
        $quota = fn (string $path, string $program, string $system, int $capacity, int $waitlist) => Quota::query()->create([
            'university_id' => $this->university->getKey(), 'period_id' => $period->getKey(), 'admission_path_id' => $paths[$path]->getKey(),
            'study_program_id' => $programs[$program]->getKey(), 'study_system_id' => $systems[$system]->getKey(),
            'capacity' => $capacity, 'waitlist_capacity' => $waitlist, 'accepted_count' => 0, 'is_offered' => true,
            'created_by' => $this->admin->getKey(), 'updated_by' => $this->admin->getKey(),
        ]);

        foreach (array_keys(self::PROGRAMS) as $program) {
            foreach (self::PATHS as $path => [, , , , , , $capacity, $waitlist]) {
                $quota($path, $program, 'REG', $capacity, $waitlist);
            }
        }

        foreach (array_keys(self::EMPLOYEE_CLASS) as $program) {
            foreach (['RAPOR', 'USM'] as $path) {
                $quota($path, $program, 'KRY', ...self::EMPLOYEE_CLASS_QUOTA);
            }
        }
    }

    /**
     * SPP + Dana Pengembangan (bisa dicicil 2×) + orientasi per prodi & sistem kuliah;
     * Jalur Prestasi menimpa Dana Pengembangan dengan potongan 50% (FR-CFG-09).
     *
     * @param  array<string, AdmissionPath>  $paths
     * @param  array<string, StudyProgram>  $programs
     * @param  Collection<string, StudySystem>  $systems
     */
    private function seedFees(Period $period, array $paths, array $programs, $systems): void
    {
        $plan = [['percent' => 50, 'due_days' => 0], ['percent' => 50, 'due_days' => 90]];
        $fee = fn (string $program, string $system, ?string $path, string $code, string $name, int $amount, bool $installable, int $sort) => FeeComponent::query()->create([
            'university_id' => $this->university->getKey(), 'period_id' => $period->getKey(), 'study_program_id' => $programs[$program]->getKey(),
            'study_system_id' => $systems[$system]->getKey(), 'admission_path_id' => $path ? $paths[$path]->getKey() : null,
            'component_code' => $code, 'name' => $name, 'amount' => number_format($amount, 2, '.', ''), 'is_mandatory_for_enrollment' => true,
            'installable' => $installable, 'installment_plan' => $installable ? $plan : null, 'is_published' => true, 'sort_order' => $sort,
            'created_by' => $this->admin->getKey(), 'updated_by' => $this->admin->getKey(),
        ]);

        $tuition = [];

        foreach (self::PROGRAMS as $code => [, , , , $spp, $dpp]) {
            $tuition[] = [$code, 'REG', $spp, $dpp];
        }

        foreach (self::EMPLOYEE_CLASS as $code => [$spp, $dpp]) {
            $tuition[] = [$code, 'KRY', $spp, $dpp];
        }

        foreach ($tuition as [$program, $system, $spp, $dpp]) {
            $fee($program, $system, null, 'SPP', 'SPP Semester 1', $spp, false, 1);
            $fee($program, $system, null, 'DPP', 'Dana Pengembangan Pendidikan', $dpp, true, 2);
            $fee($program, $system, null, 'ORI', 'Orientasi Mahasiswa & Jas Almamater', self::ORIENTATION_FEE, false, 3);

            if ($system === 'REG') {
                $fee($program, $system, 'PRESTASI', 'DPP', 'Dana Pengembangan Pendidikan (potongan prestasi 50%)', intdiv($dpp, 2), true, 2);
            }
        }
    }

    private function seedExamVenue(): ExamVenue
    {
        $venue = ExamVenue::query()->firstOrCreate(['name' => 'Kampus Semanggi'], [
            'university_id' => $this->university->getKey(), 'address' => 'Jl. Jenderal Sudirman No. 51, Jakarta Selatan 12930',
            'map_url' => 'https://maps.google.com/?q=Universitas+Katolik+Indonesia+Atma+Jaya+Semanggi', 'is_active' => true,
        ]);

        foreach ([['LAB-1', 'Laboratorium Komputer 1', 40, false], ['LAB-2', 'Laboratorium Komputer 2', 40, true]] as [$code, $name, $capacity, $accessible]) {
            ExamRoom::query()->firstOrCreate(['venue_id' => $venue->getKey(), 'code' => $code], [
                'university_id' => $this->university->getKey(), 'name' => $name, 'capacity' => $capacity, 'is_accessible' => $accessible,
                'facilities' => ['komputer', 'AC', 'proyektor'], 'is_active' => true,
            ]);
        }

        return $venue;
    }

    /** Jadwal CBT terpublikasi (2 sesi × 2 lab) + bank soal TPA untuk jalur USM. */
    private function seedCbtSchedule(Wave $wave, WavePath $wavePath, SelectionScheme $scheme, ExamVenue $venue): void
    {
        $uid = $this->university->getKey();
        $component = $scheme->components()->where('code', 'TPA')->firstOrFail();
        $schedule = ExamSchedule::query()->create([
            'university_id' => $uid, 'wave_id' => $wave->getKey(), 'selection_component_id' => $component->getKey(),
            'wave_path_ids' => [$wavePath->getKey()], 'mode' => ExamMode::Cbt, 'title' => 'Tes Potensi Akademik (CBT) '.$wave->name,
            'exam_date' => $wave->exam_starts_on->toDateString(),
            'instructions' => 'Hadir 30 menit sebelum sesi dengan membawa kartu peserta dan KTP/Kartu Pelajar.',
            'cbt_duration_minutes' => 60, 'cbt_question_count' => 10, 'cbt_randomize' => true,
            'status' => ExamScheduleStatus::Published, 'published_at' => now(), 'created_by' => $this->admin->getKey(),
        ]);

        $examDay = CarbonImmutable::parse($wave->exam_starts_on->toDateString(), $this->university->timezone ?: 'Asia/Jakarta');

        foreach (ExamRoom::query()->where('venue_id', $venue->getKey())->orderBy('code')->get() as $room) {
            foreach ([['S1', 8], ['S2', 10]] as [$slot, $hour]) {
                $starts = $examDay->setTime($hour, 0);
                ExamSession::query()->create([
                    'university_id' => $uid, 'schedule_id' => $schedule->getKey(), 'room_id' => $room->getKey(), 'code' => $slot.'-'.$room->code,
                    'starts_at' => $starts->setTimezone(config('app.timezone')), 'ends_at' => $starts->addMinutes(90)->setTimezone(config('app.timezone')),
                    'checkin_opens_at' => $starts->subMinutes(30)->setTimezone(config('app.timezone')), 'capacity' => $room->capacity, 'participants_count' => 0,
                ]);
            }
        }

        foreach (self::CBT_QUESTIONS as $i => [$question, $options, $correct]) {
            CbtQuestion::query()->create([
                'university_id' => $uid, 'schedule_id' => $schedule->getKey(), 'question' => $question,
                'options' => array_map(fn ($text, $key) => ['key' => $key, 'text' => $text], $options, ['A', 'B', 'C', 'D']),
                'correct_option' => $correct, 'points' => 1, 'sort_order' => $i,
            ]);
        }
    }

    /** Akun pendaftar terverifikasi + persetujuan legal, sama seperti hasil registrasi. */
    private function seedDemoApplicant(): void
    {
        $account = ApplicantAccount::query()->firstOrCreate(['email' => self::DEMO_APPLICANT_EMAIL], [
            'university_id' => $this->university->getKey(), 'name' => 'Calon Mahasiswa Demo', 'phone' => '+6281200000001',
            'password' => self::DEMO_APPLICANT_PASSWORD, 'status' => AccountStatus::Active, 'email_verified_at' => now(), 'info_source' => 'website',
        ]);

        if (! $account->wasRecentlyCreated) {
            return;
        }

        $accounts = app(AccountService::class);

        foreach ([ConsentType::PrivacyPolicy, ConsentType::Terms] as $type) {
            $content = ContentService::activeVersion(ContentType::from($type->value));
            $accounts->recordConsent($account, $type, $content?->getKey(), $content?->version, '127.0.0.1', 'seeder');
        }
    }
}

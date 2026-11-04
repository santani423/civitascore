<?php

namespace Modules\Admission\Database\Seeders;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Academic\Models\StudyProgram;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\ExaminerRole;
use Modules\Admission\Enums\ExamMode;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\WaveStatus;
use Modules\Admission\Models\AdmissionPath;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\ApplicantAchievement;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\CbtAttempt;
use Modules\Admission\Models\CbtQuestion;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\ExamSessionExaminer;
use Modules\Admission\Models\ManualPaymentProof;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Models\StudySystem;
use Modules\Admission\Models\Wave;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Outbox\OutboxProcessor;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Account\ProfileService;
use Modules\Admission\Services\Announcement\DecisionService;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Application\DocumentService;
use Modules\Admission\Services\Communication\CommunicationService;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\Admission\Services\Configuration\PeriodService;
use Modules\Admission\Services\Exam\CbtService;
use Modules\Admission\Services\Exam\ExamService;
use Modules\Admission\Services\Payment\InvoiceService;
use Modules\Admission\Services\Payment\PaymentService;
use Modules\Admission\Services\Payment\RefundService;
use Modules\Admission\Services\Reporting\ReportService;
use Modules\Admission\Services\ReRegistration\ReRegistrationService;
use Modules\Admission\Services\Selection\ScoreService;
use Modules\Admission\Services\Selection\SelectionRunService;
use Modules\Admission\Services\Verification\VerificationService;
use Modules\Admission\Support\Actor;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Services\ApprovalActionService;
use Modules\Tenancy\Models\University;
use Modules\UserManagement\Support\PermissionRegistry;

/**
 * Pendaftar DEMO PMB Atma Jaya di setiap tahap siklus, di atas konfigurasi
 * AtmaJayaAdmissionSeeder (periode PMB2027, tiga gelombang):
 *
 * - Gelombang 1 (sudah lewat): pendaftaran → bayar (transfer/loket) →
 *   verifikasi (termasuk ditolak & ditarik) → CBT (termasuk tidak hadir) &
 *   wawancara → run seleksi → pengesahan 2 langkah → pengumuman → daftar
 *   ulang di berbagai status (belum konfirmasi, isi data, menunggu bukti
 *   bayar, revisi, menunggu pemeriksaan, ditolak) → konversi mahasiswa (NIM),
 *   cadangan & promosi cadangan, serta refund yang menunggu persetujuan.
 * - Gelombang 2 (sedang dibuka): draft, belum bayar, menunggu bukti transfer,
 *   bukti ditolak, antrean verifikasi, sedang diperiksa, revisi, kirim ulang,
 *   terverifikasi + terjadwal ujian/wawancara, voucher, jadwal ulang ujian,
 *   klaim identitas ganda, pemulihan akun, chat, broadcast.
 * - Statistik harian (dashboard/KPI) sejak Gelombang 1 dibuka.
 *
 * Semua data dibuat lewat service domain (aturan bisnis, nomor urut, riwayat
 * status, audit, outbox) — bukan insert langsung — dengan waktu "diputar
 * mundur" (Carbon test-now) sehingga tanggal setiap langkah realistis. Model
 * events diaktifkan kembali (DatabaseSeeder memakai WithoutModelEvents) karena
 * service mengandalkan pengisian otomatis TenantScoped. Antrean dijalankan
 * sinkron dan email ditahan di mailer `array` selama seeding.
 *
 * Satu transaksi: gagal di tengah → tidak ada yang tersimpan. Dilewati bila
 * pendaftar demo sudah ada atau konfigurasi periode bukan hasil seeder
 * terbaru (jalankan `php artisan migrate:fresh --seed`). Kata sandi seluruh
 * akun pendaftar demo: AtmaJayaAdmissionSeeder::DEMO_APPLICANT_PASSWORD.
 *
 *   php artisan db:seed --class="Modules\Admission\Database\Seeders\AtmaJayaApplicantDemoSeeder"
 */
class AtmaJayaApplicantDemoSeeder extends Seeder
{
    private const EMAIL_DOMAIN = 'example.com';

    private const IP = '127.0.0.1';

    private const USER_AGENT = 'Mozilla/5.0 (CivitasOne demo seeder)';

    /** kode => [kota, provinsi, kode wilayah (6 digit NIK), kecamatan, kelurahan, tempat lahir, kode pos] */
    private const CITIES = [
        'JKS' => ['Jakarta Selatan', 'DKI Jakarta', '317401', 'Kebayoran Baru', 'Senayan', 'Jakarta', '12190'],
        'JKT' => ['Jakarta Timur', 'DKI Jakarta', '317502', 'Matraman', 'Utan Kayu Utara', 'Jakarta', '13120'],
        'JKU' => ['Jakarta Utara', 'DKI Jakarta', '317206', 'Kelapa Gading', 'Kelapa Gading Barat', 'Jakarta', '14240'],
        'TNG' => ['Kota Tangerang Selatan', 'Banten', '367403', 'Serpong', 'Lengkong Gudang', 'Tangerang', '15310'],
        'BKS' => ['Kota Bekasi', 'Jawa Barat', '327501', 'Bekasi Selatan', 'Kayuringin Jaya', 'Bekasi', '17144'],
        'DPK' => ['Kota Depok', 'Jawa Barat', '327605', 'Beji', 'Kukusan', 'Depok', '16425'],
        'BDG' => ['Kota Bandung', 'Jawa Barat', '327302', 'Coblong', 'Dago', 'Bandung', '40135'],
        'SMG' => ['Kota Semarang', 'Jawa Tengah', '337405', 'Candisari', 'Jatingaleh', 'Semarang', '50254'],
        'YGY' => ['Kota Yogyakarta', 'DI Yogyakarta', '347106', 'Gondokusuman', 'Kotabaru', 'Yogyakarta', '55224'],
        'SBY' => ['Kota Surabaya', 'Jawa Timur', '357805', 'Gubeng', 'Airlangga', 'Surabaya', '60286'],
        'MDN' => ['Kota Medan', 'Sumatera Utara', '127112', 'Medan Baru', 'Darat', 'Medan', '20153'],
        'PTK' => ['Kota Pontianak', 'Kalimantan Barat', '617101', 'Pontianak Kota', 'Darat Sekip', 'Pontianak', '78117'],
        'KPG' => ['Kota Kupang', 'Nusa Tenggara Timur', '537101', 'Oebobo', 'Oebobo', 'Kupang', '85111'],
        'MKS' => ['Kota Makassar', 'Sulawesi Selatan', '737109', 'Panakkukang', 'Masale', 'Makassar', '90231'],
    ];

    /** kunci => [nama, jenis kelamin, tanggal lahir, kota, sekolah asal, agama, ayah, ibu, sumber info] — seluruhnya fiktif. */
    private const APPLICANTS = [
        'r1' => ['Andreas Wijaya', 'male', '2009-03-14', 'JKS', 'SMA Kolese Kanisius', 'katolik', 'Hendra Wijaya', 'Maria Lestari', 'website'],
        'r2' => ['Clarissa Halim', 'female', '2009-07-22', 'JKU', 'SMA Santa Ursula', 'katolik', 'Budi Halim', 'Lina Kurniawan', 'school_visit'],
        'rk' => ['Stefanus Hariyanto', 'male', '2001-04-05', 'JKT', 'SMK Negeri 26 Jakarta', 'katolik', 'Paulus Hariyanto', 'Theresia Murni', 'friend_family'],
        'r3' => ['Bagus Pratama', 'male', '2009-01-09', 'BKS', 'SMA Negeri 1 Bekasi', 'islam', 'Agus Santoso', 'Sri Wahyuni', 'social_media'],
        'r4' => ['Yohana Sitompul', 'female', '2009-05-30', 'MDN', 'SMA Santo Thomas 1 Medan', 'kristen', 'Rudolf Sitompul', 'Ester Simanjuntak', 'expo'],
        'r5' => ['Nadia Putri Rahmawati', 'female', '2009-11-02', 'DPK', 'SMA Negeri 2 Depok', 'islam', 'Rahmat Hidayat', 'Dewi Anggraini', 'search_engine'],
        'r6' => ['Kevin Tanoto', 'male', '2009-08-17', 'TNG', 'SMA Pahoa', 'buddha', 'Wijaya Tanoto', 'Susan Gunawan', 'social_media'],
        'r7' => ['Rizky Maulana', 'male', '2009-02-25', 'JKT', 'SMA Negeri 12 Jakarta', 'islam', 'Maulana Yusuf', 'Siti Rohmah', 'website'],
        'r8' => ['Felicia Angelina', 'female', '2009-09-12', 'BDG', 'SMA Trinitas Bandung', 'katolik', 'Antonius Gunadi', 'Veronika Sari', 'alumni'],
        'u1' => ['Gabriel Santoso', 'male', '2009-06-11', 'SBY', 'SMA Katolik St. Louis 1 Surabaya', 'katolik', 'Robert Santoso', 'Elisabeth Wulandari', 'expo'],
        'u2' => ['Dimas Aditya Nugroho', 'male', '2009-10-03', 'SMG', 'SMA Negeri 3 Semarang', 'islam', 'Bambang Nugroho', 'Endang Sulistyowati', 'search_engine'],
        'u3' => ['Maria Goretti Lado', 'female', '2009-12-20', 'KPG', 'SMA Katolik Giovanni Kupang', 'katolik', 'Yosef Lado', 'Agustina Bria', 'school_visit'],
        'u4' => ['Fajar Ramadhan', 'male', '2009-04-18', 'JKS', 'SMA Negeri 70 Jakarta', 'islam', 'Ramadhan Syah', 'Nurul Aini', 'social_media'],
        'u5' => ['Jessica Wongso', 'female', '2009-03-01', 'PTK', 'SMA Santo Paulus Pontianak', 'kristen', 'Hartono Wongso', 'Meilani Tan', 'website'],
        'u6' => ['Theodorus Bayu Aji', 'male', '2009-07-07', 'YGY', 'SMA Kolese De Britto', 'katolik', 'Ignatius Purnomo', 'Caecilia Rahayu', 'alumni'],
        'u7' => ['Aulia Rahma', 'female', '2009-01-28', 'MKS', 'SMA Negeri 5 Makassar', 'islam', 'Abdul Rahman', 'Hasnah Yusuf', 'maukuliah'],
        'p1' => ['Vincentius Adi Nugraha', 'male', '2009-02-14', 'JKS', 'SMA Kolese Gonzaga', 'katolik', 'Fransiskus Nugraha', 'Anastasia Dewi', 'school_visit'],
        'p2' => ['Cecilia Anindya', 'female', '2009-05-05', 'TNG', 'SMA Santa Laurensia', 'katolik', 'Albertus Wirawan', 'Monika Kartika', 'website'],
        'p3' => ['Michael Hartanto', 'male', '2009-09-09', 'SBY', 'SMAK St. Hendrikus Surabaya', 'katolik', 'Johan Hartanto', 'Grace Lim', 'expo'],
        'p4' => ['Putri Ayu Lestari', 'female', '2009-08-08', 'BDG', 'SMA Negeri 3 Bandung', 'islam', 'Asep Supriatna', 'Euis Nurhayati', 'social_media'],
        'p5' => ['Samuel Pardede', 'male', '2009-11-11', 'MDN', 'SMA Methodist 2 Medan', 'kristen', 'Jonner Pardede', 'Rosmawati Siregar', 'friend_family'],
        'g01' => ['Raka Firmansyah', 'male', '2009-06-21', 'BKS', 'SMA Negeri 4 Bekasi', 'islam', 'Firman Hakim', 'Yuliani Safitri', 'social_media'],
        'g02' => ['Angela Christina', 'female', '2009-10-15', 'JKU', 'SMA Santa Maria Jakarta', 'katolik', 'Christian Lukito', 'Ratna Sari', 'website'],
        'g03' => ['Hana Kusuma', 'female', '2009-04-27', 'DPK', 'SMA Negeri 1 Depok', 'islam', 'Kusuma Atmaja', 'Lilis Suryani', 'expo'],
        'g04' => ['Daniel Simatupang', 'male', '2009-12-03', 'MDN', 'SMA Negeri 1 Medan', 'kristen', 'Bonar Simatupang', 'Lamria Hutabarat', 'search_engine'],
        'g05' => ['Olivia Tjahjadi', 'female', '2009-02-02', 'SBY', 'SMA Petra 1 Surabaya', 'kristen', 'Tommy Tjahjadi', 'Linda Wijaya', 'alumni'],
        'g06' => ['Yusuf Habibie Akbar', 'male', '2009-08-30', 'MKS', 'SMA Negeri 17 Makassar', 'islam', 'Akbar Hamid', 'Fatimah Zahra', 'maukuliah'],
        'g07' => ['Bella Anastasya', 'female', '2009-01-19', 'TNG', 'SMA Tarakanita Gading Serpong', 'katolik', 'Yohanes Setiadi', 'Maria Imelda', 'social_media'],
        'g08' => ['Gilang Saputra', 'male', '2009-05-12', 'BDG', 'SMA Negeri 5 Bandung', 'islam', 'Dadang Saputra', 'Neneng Hasanah', 'friend_family'],
        'g09' => ['Natalia Sembiring', 'female', '2009-07-18', 'MDN', 'SMA Santo Thomas 2 Medan', 'kristen', 'Ferdinan Sembiring', 'Herlina Ginting', 'website'],
        'g10' => ['Ivan Kristanto', 'male', '2009-03-23', 'SMG', 'SMA Kolese Loyola Semarang', 'katolik', 'Petrus Kristanto', 'Yuliana Hartati', 'school_visit'],
        'g11' => ['Sekar Ayuningtyas', 'female', '2009-09-29', 'YGY', 'SMA Negeri 3 Yogyakarta', 'islam', 'Bayu Wicaksono', 'Ratih Kusumawardani', 'search_engine'],
        'g12' => ['Timotius Wibowo', 'male', '2009-11-24', 'JKT', 'SMAK 3 BPK Penabur Jakarta', 'kristen', 'Hendrik Wibowo', 'Debora Susanti', 'expo'],
        'g13' => ['Agnes Kartika Putri', 'female', '2009-06-06', 'JKS', 'SMA Santa Theresia Jakarta', 'katolik', 'Gregorius Kartika', 'Yustina Wulan', 'website'],
        'g14' => ['Benedictus Raharjo', 'male', '2009-02-08', 'YGY', 'SMA Stella Duce 1 Yogyakarta', 'katolik', 'Antonius Raharjo', 'Bernadeta Sri', 'alumni'],
        'g15' => ['Lucia Permatasari', 'female', '2009-10-10', 'PTK', 'SMA Immanuel Pontianak', 'kristen', 'Darius Permana', 'Sulastri Wijaya', 'social_media'],
        'g16' => ['Kenneth Halim', 'male', '2009-05-25', 'JKU', 'SMA Kristen Kalam Kudus Jakarta', 'kristen', 'Rudy Halim', 'Wenny Susanto', 'website'],
        'g17' => ['Rafael Gunawan', 'male', '2009-12-28', 'JKT', 'SMA Fons Vitae 1 Jakarta', 'katolik', 'Agustinus Gunawan', 'Lucia Ratnasari', 'expo'],
        'g18' => ['Stella Maris Tanjung', 'female', '2009-04-04', 'KPG', 'SMA Negeri 1 Kupang', 'katolik', 'Markus Tanjung', 'Yuliana Mau', 'search_engine'],
        'g19' => ['Agnes Kartika', 'female', '2009-06-06', 'JKS', 'SMA Santa Theresia Jakarta', 'katolik', 'Gregorius Kartika', 'Yustina Wulan', 'website'],
    ];

    /**
     * Gelombang 1: kunci => [hari daftar, jalur, pilihan prodi (KODE atau KODE:SISTEM), rata-rata rapor, bayar pendaftaran, skor CBT/wawancara].
     * Hasil (ambang Rapor 65, USM 60, Prestasi 60; kuota Prestasi TI 3 + cadangan 2) dipastikan oleh skor di bawah.
     */
    private const WAVE_ONE = [
        'r1' => [-73, 'RAPOR', ['TI1'], 88, 'cashier', null],
        'r2' => [-70, 'RAPOR', ['MNJ', 'AKT'], 85, 'manual', null],
        'u1' => [-68, 'USM', ['TI1', 'TIN'], 85, 'manual', 0.9],
        'p1' => [-66, 'PRESTASI', ['TI1'], 90, 'manual', 88],
        'r3' => [-64, 'RAPOR', ['AKT'], 83, 'cashier', null],
        'u2' => [-62, 'USM', ['TIN'], 80, 'manual', 0.8],
        'p2' => [-60, 'PRESTASI', ['TI1'], 87, 'cashier', 85],
        'rk' => [-58, 'RAPOR', ['MNJ:KRY'], 79, 'cashier', null],
        'r4' => [-56, 'RAPOR', ['HKM'], 80, 'manual', null],
        'u3' => [-54, 'USM', ['KOM'], 82, 'manual', 0.7],
        'p3' => [-52, 'PRESTASI', ['TI1'], 85, 'manual', 80],
        'r5' => [-50, 'RAPOR', ['PSI', 'KOM'], 78, 'manual', null],
        'u4' => [-49, 'USM', ['TI1'], 75, 'cashier', 0.4],
        'p4' => [-48, 'PRESTASI', ['TI1'], 86, 'manual', 84],
        'r6' => [-47, 'RAPOR', ['KOM'], 76, 'cashier', null],
        'u5' => [-46, 'USM', ['PSI'], 81, 'manual', null],
        'p5' => [-45, 'PRESTASI', ['TI1'], 84, 'cashier', 78],
        'r7' => [-44, 'RAPOR', ['TIN'], 61, 'manual', null],
        'u6' => [-43, 'USM', ['MNJ'], 78, 'manual', 0.8],
        'r8' => [-42, 'RAPOR', ['PSI'], 82, 'cashier', null],
        'u7' => [-41, 'USM', ['AKT'], 80, 'cashier', null],
    ];

    /** Prestasi pendaftar jalur Prestasi: [nama lomba, kategori, tingkat, peringkat, tahun, penyelenggara]. */
    private const ACHIEVEMENTS = [
        'p1' => [['Asian Pacific Informatics Olympiad', 'academic', 'international', '2', 2026, 'APIO Committee'], ['Olimpiade Sains Nasional Informatika', 'academic', 'national', '1', 2026, 'Balai Pengembangan Talenta Indonesia']],
        'p2' => [['Lomba Debat Bahasa Inggris Nasional', 'academic', 'national', '1', 2026, 'Balai Pengembangan Talenta Indonesia'], ['Festival Paduan Suara Pelajar Banten', 'non_academic', 'province', '2', 2025, 'Dinas Pendidikan Provinsi Banten']],
        'p3' => [['Kompetisi Robotik Nasional', 'academic', 'national', '2', 2026, 'Asosiasi Robotik Pelajar'], ['Olimpiade Matematika Jawa Timur', 'academic', 'province', '1', 2025, 'Dinas Pendidikan Provinsi Jawa Timur']],
        'p4' => [['Lomba Karya Tulis Ilmiah Jawa Barat', 'academic', 'province', '1', 2026, 'Dinas Pendidikan Provinsi Jawa Barat'], ['Kejuaraan Catur Pelajar Kota Bandung', 'non_academic', 'district', '1', 2025, 'Percasi Kota Bandung']],
        'p5' => [['Kejuaraan Bulu Tangkis Pelajar Nasional', 'non_academic', 'national', '3', 2026, 'PB Persatuan Bulutangkis Pelajar']],
        'g15' => [['Festival Film Pendek Pelajar Nasional', 'non_academic', 'national', '2', 2026, 'Kementerian Pendidikan']],
    ];

    /**
     * Gelombang 2: kunci => [hari daftar, jalur, pilihan prodi, rata-rata rapor, tahap].
     * Tahap: draft_identity, draft, unpaid, transfer_pending, proof, proof_rejected, paid, in_review, revision, resubmitted, verified, voucher.
     */
    private const WAVE_TWO = [
        'g08' => [-12, 'RAPOR', ['KOM'], 80, 'paid'],
        'g09' => [-11, 'USM', ['TI1'], 84, 'paid'],
        'g10' => [-10, 'RAPOR', ['MNJ'], 79, 'in_review'],
        'g11' => [-10, 'RAPOR', ['PSI'], 83, 'revision'],
        'g12' => [-9, 'USM', ['AKT', 'MNJ'], 77, 'resubmitted'],
        'g13' => [-9, 'RAPOR', ['TI1', 'TIN'], 86, 'verified'],
        'g14' => [-8, 'USM', ['TI1'], 82, 'verified'],
        'g15' => [-8, 'PRESTASI', ['KOM'], 85, 'verified'],
        'g16' => [-6, 'RAPOR', ['HKM'], 81, 'voucher'],
        'g07' => [-4, 'RAPOR', ['AKT'], 78, 'proof_rejected'],
        'g03' => [-3, 'RAPOR', ['MNJ'], 80, 'unpaid'],
        'g05' => [-3, 'USM', ['PSI'], 82, 'proof'],
        'g01' => [-2, 'RAPOR', ['TI1'], 79, 'draft_identity'],
        'g06' => [-2, 'RAPOR', ['TI1'], 83, 'proof'],
        'g02' => [-1, 'RAPOR', ['TIN'], 80, 'draft'],
        'g04' => [-1, 'USM', ['HKM'], 81, 'transfer_pending'],
    ];

    /** Mata pelajaran rapor semester 1–5. */
    private const SUBJECTS = ['MTK' => 'Matematika', 'BIN' => 'Bahasa Indonesia', 'BIG' => 'Bahasa Inggris', 'IPA' => 'Ilmu Pengetahuan Alam'];

    private const STREETS = ['Jl. Melati', 'Jl. Kenanga', 'Jl. Cempaka Putih', 'Jl. Flamboyan', 'Jl. Anggrek', 'Jl. Dahlia', 'Jl. Teratai', 'Jl. Kamboja'];

    private const OCCUPATIONS = [['Karyawan swasta', 'S1', '5m_10m'], ['Wiraswasta', 'SMA', '5m_10m'], ['PNS', 'S1', '3m_5m'], ['Dokter', 'S2', 'gt_10m'], ['Guru', 'S1', '3m_5m'], ['Petani', 'SMA', '1m_3m']];

    private University $university;

    private Period $period;

    private CarbonImmutable $today;

    /** @var array<string, User> admin, approver, verifier, committee, registrar, finance */
    private array $staff = [];

    /** @var array<string, ApplicantAccount> */
    private array $accounts = [];

    /** @var array<string, Application> */
    private array $applications = [];

    /** @var array<string, StudyProgram> */
    private array $programs = [];

    /** @var array<string, StudySystem> */
    private array $systems = [];

    /** @var array<int, string> */
    private array $declarations = [];

    /** @var array<int, array{0: ConsentType, 1: ?string, 2: ?int}> */
    private array $legal = [];

    /** @var array<int, string> */
    private array $tempFiles = [];

    private string $passwordHash;

    private int $receipt = 0;

    public function run(): void
    {
        $university = University::query()->where('code', AtmaJayaAdmissionSeeder::UNIVERSITY_CODE)->first();

        if ($university === null) {
            $this->note('Universitas UAJ belum ada — jalankan DemoUniversitiesSeeder & AtmaJayaAdmissionSeeder terlebih dahulu.', warning: true);

            return;
        }

        $tenant = app(TenantContext::class);
        $previousTenant = $tenant->universityId();
        $tenant->setUniversityId($university->getKey());
        $this->university = $university;

        try {
            if (! $this->ready()) {
                return;
            }

            $this->withDemoRuntime(function (): void {
                DB::transaction(function (): void {
                    $this->bootstrap();
                    $this->seedWaveOne();
                    $this->seedWaveTwo();
                    $this->finish();
                });
            });
        } finally {
            $tenant->setUniversityId($previousTenant);
        }

        foreach (['site', 'periods', 'programs', 'paths'] as $key) {
            Cache::forget('pmb:public:'.$university->getKey().':'.$key);
        }

        PermissionRegistry::flushAll();
        $this->note(sprintf('PMB demo Atma Jaya: %d akun pendaftar, %d pendaftaran.', count($this->accounts), count($this->applications)));
    }

    /** Periode hasil AtmaJayaAdmissionSeeder terbaru (3 gelombang, Gelombang 1 sudah lewat) & belum ada pendaftar demo. */
    private function ready(): bool
    {
        $period = Period::query()->where('code', AtmaJayaAdmissionSeeder::PERIOD_CODE)->with('waves')->first();
        $waves = $period?->waves->keyBy('code');

        if ($period === null || $waves->keys()->sort()->values()->all() !== ['G1', 'G2', 'G3'] || $waves['G1']->registration_closes_at->gt(now()->subDays(30))) {
            $this->note('Periode '.AtmaJayaAdmissionSeeder::PERIOD_CODE.' UAJ tidak sesuai konfigurasi demo (3 gelombang, Gelombang 1 sudah lewat) — jalankan `php artisan migrate:fresh --seed`.', warning: true);

            return false;
        }

        if (ApplicantAccount::query()->where('email', $this->email('r1'))->exists()) {
            $this->note('Pendaftar demo PMB Atma Jaya sudah ada — dilewati.');

            return false;
        }

        $this->period = $period;

        return true;
    }

    /**
     * Lingkungan seeding demo: model events aktif, antrean sinkron, email
     * ditahan (mailer array), lalu semuanya dikembalikan — termasuk jam.
     */
    private function withDemoRuntime(callable $callback): void
    {
        $dispatcher = Model::getEventDispatcher();
        $config = ['queue.default' => config('queue.default'), 'mail.default' => config('mail.default')];
        Model::setEventDispatcher(app('events'));
        config(['queue.default' => 'sync', 'mail.default' => 'array']);

        try {
            $callback();
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
            Auth::forgetUser();
            config($config);
            $dispatcher === null ? Model::unsetEventDispatcher() : Model::setEventDispatcher($dispatcher);

            foreach ($this->tempFiles as $path) {
                @unlink($path);
            }
        }
    }

    private function bootstrap(): void
    {
        $this->today = CarbonImmutable::now((string) ($this->university->getAttribute('timezone') ?: 'Asia/Jakarta'))->startOfDay();
        $this->passwordHash = Hash::make(AtmaJayaAdmissionSeeder::DEMO_APPLICANT_PASSWORD);

        foreach (['admin' => 'pmb.admin', 'approver' => 'pmb.pengesah', 'verifier' => 'pmb.verifikator', 'committee' => 'pmb.seleksi', 'registrar' => 'pmb.daftarulang', 'finance' => 'pmb.keuangan'] as $role => $localPart) {
            $this->staff[$role] = User::query()->where('email', $localPart.'@atmajaya.com')->firstOrFail();
        }

        $this->programs = StudyProgram::query()->where('university_id', $this->university->getKey())->get()->keyBy('code')->all();
        $this->systems = StudySystem::query()->get()->keyBy('code')->all();
        $this->declarations = app(ApplicationService::class)->activeDeclarations()->pluck('id')->map(fn ($id) => (string) $id)->all();

        // Diambil sebelum jam diputar mundur: konten legal terbit pada waktu seeding.
        foreach ([ConsentType::PrivacyPolicy, ConsentType::Terms] as $type) {
            $content = ContentService::activeVersion(ContentType::from($type->value));
            $this->legal[] = [$type, $content?->getKey(), $content?->version];
        }

        // Jam diputar ke awal Gelombang 1: status gelombang dikembalikan ke keadaan saat itu,
        // lalu refreshWaveStatuses() memajukannya mengikuti jam (open → closed → announced).
        Wave::query()->where('period_id', $this->period->getKey())->where('code', 'G1')->update(['status' => WaveStatus::Open->value]);
        Wave::query()->where('period_id', $this->period->getKey())->whereIn('code', ['G2', 'G3'])->update(['status' => WaveStatus::Scheduled->value]);
    }

    // ================================================================== Gelombang 1

    private function seedWaveOne(): void
    {
        // Tahap 1 — daftar, kirim, bayar: seluruhnya sebelum Gelombang 1 ditutup. Status gelombang hanya
        // bergerak maju mengikuti jam, jadi langkah sesudah penutupan menunggu tahap berikutnya.
        foreach (self::WAVE_ONE as $key => [$day, $path, $choices, $report, $payment]) {
            $this->account($key, $day - 1);
            $application = $this->submitted($key, 'G1', $path, $choices, $report, $day);
            $proof = $this->payRegistration($application, $payment, $day + 1);

            if ($proof !== null) {
                $this->at($day + 2, 9);
                $this->decideProof($proof, true);
            }
        }

        // Tahap 2 — verifikasi administrasi (sebelum batas kunci formulir & penjadwalan ujian).
        foreach (self::WAVE_ONE as $key => [$day]) {
            match ($key) {
                // Mengundurkan diri sebelum diverifikasi; biaya pendaftaran diajukan refund (pengecualian kebijakan).
                'u7' => $this->withdrawWithRefund($this->applications[$key], $day + 2),
                'r8' => $this->rejectForForgery($this->applications[$key], $day + 3),
                default => $this->verify($this->applications[$key], $day + 3),
            };
        }

        $this->examsWaveOne();
        $this->selectWaveOne();
        $this->reRegisterWaveOne();
    }

    private function withdrawWithRefund(Application $application, int $day): void
    {
        $this->at($day, 11);
        app(ApplicationService::class)->withdraw($application, 'Melanjutkan studi ke luar negeri.', Actor::applicant($application->account));

        $this->at($day + 3, 10);
        $transaction = PaymentTransaction::query()->where('status', 'success')
            ->whereHas('invoice', fn ($q) => $q->where('application_id', $application->getKey())->where('type', InvoiceType::RegistrationFee->value))->firstOrFail();

        $this->actingAs($this->staff['finance'], fn () => app(RefundService::class)->request($transaction, [
            'amount' => (string) $transaction->amount, 'reason_code' => 'WITHDRAWAL',
            'reason_note' => 'Pengunduran diri sebelum verifikasi berkas; diajukan sebagai pengecualian kebijakan (surat permohonan orang tua terlampir).',
            'beneficiary_bank' => 'Bank Demo', 'beneficiary_account' => '0001234567', 'beneficiary_name' => self::APPLICANTS['u7'][7],
        ], $this->staff['finance']));
    }

    /** Rapor terindikasi dipalsukan: dokumen ditolak (alasan disamarkan bagi pendaftar) lalu aplikasi ditolak. */
    private function rejectForForgery(Application $application, int $day): void
    {
        $this->at($day, 10);
        $verifier = $this->staff['verifier'];

        $this->actingAs($verifier, function () use ($application, $verifier): void {
            $service = app(VerificationService::class);
            $service->claim($application, $verifier);

            foreach ($this->documentsOf($application, DocumentStage::Application) as $document) {
                $forged = $document->documentType->code === 'REPORT_CARD';
                app(DocumentService::class)->review($document, [
                    'decision' => $forged ? 'rejected' : 'approved', 'version_id' => (string) $document->current_version_id,
                    'reason_code' => $forged ? 'SUSPECTED_FORGERY' : null,
                    'internal_note' => $forged ? 'Nilai rapor tidak sesuai legalisasi; dikonfirmasi ke sekolah melalui telepon.' : null,
                ], $verifier);
            }

            $application->refresh();
            $service->reject($application, 'FALSIFIED_DATA', 'Data nilai rapor tidak dapat diverifikasi oleh sekolah asal. Hubungi panitia PMB untuk informasi lebih lanjut.', 'Sekolah menyatakan nilai rapor berbeda dari arsip.', $application->lock_version, $verifier);
        });
    }

    /** CBT USM (satu peserta tidak hadir) & wawancara Prestasi, lalu nilai sesi dikunci. */
    private function examsWaveOne(): void
    {
        $wave = $this->wave('G1');
        $schedules = ExamSchedule::query()->where('wave_id', $wave->getKey())->orderBy('exam_date')->get();
        $committee = $this->staff['committee'];

        $this->at(-37, 10);
        $this->actingAs($committee, function () use ($schedules): void {
            foreach ($schedules as $schedule) {
                app(ExamService::class)->autoAssign($schedule);
            }
        });
        $this->processOutbox();

        $keys = array_flip(array_map(fn (Application $a) => (string) $a->getKey(), $this->applications));

        foreach ($schedules as $schedule) {
            $participants = ExamParticipant::query()->with('session')->where('schedule_id', $schedule->getKey())->orderBy('participant_number')->get();

            if ($schedule->mode === ExamMode::Cbt) {
                // Skor CBT null = tidak hadir: dicatat pengawas setelah peserta lain mulai mengerjakan.
                [$present, $absent] = $participants->partition(fn (ExamParticipant $p) => self::WAVE_ONE[$keys[(string) $p->application_id]][5] !== null);

                foreach ($present->values() as $i => $participant) {
                    $this->travelTo(CarbonImmutable::parse($participant->session->starts_at)->addMinutes(5 + $i));
                    $this->takeCbt($participant, (float) self::WAVE_ONE[$keys[(string) $participant->application_id]][5]);
                }

                foreach ($absent as $participant) {
                    $session = $participant->session;
                    $this->travelTo(CarbonImmutable::parse($session->starts_at)->addMinutes(40));
                    $this->actingAs($this->sessionStaff($session, ExaminerRole::Proctor), fn (User $proctor) => app(ExamService::class)->checkIn($session, null, (string) $participant->getKey(), 'absent', $proctor));
                }
            } else {
                foreach ($participants->groupBy('session_id') as $sessionParticipants) {
                    $session = $sessionParticipants->first()->session;
                    $this->travelTo(CarbonImmutable::parse($session->starts_at)->addMinutes(150));
                    $rows = $sessionParticipants->map(fn (ExamParticipant $p) => [
                        'participant_id' => (string) $p->getKey(), 'raw_score' => (float) self::WAVE_ONE[$keys[(string) $p->application_id]][5],
                        'note' => 'Motivasi kuat, komunikasi baik.',
                    ])->values()->all();
                    $this->actingAs($this->sessionStaff($session, ExaminerRole::Interviewer), fn (User $interviewer) => app(ScoreService::class)->inputSessionScores($session, $rows, $interviewer));
                }
            }

            foreach (ExamSession::query()->where('schedule_id', $schedule->getKey())->where('participants_count', '>', 0)->orderBy('starts_at')->get() as $session) {
                $this->travelTo(CarbonImmutable::parse($session->ends_at)->addMinutes(30));
                $this->actingAs($committee, fn () => app(ScoreService::class)->lockSession($session, $committee));
            }
        }
    }

    private function takeCbt(ExamParticipant $participant, float $accuracy): void
    {
        $cbt = app(CbtService::class);
        $cbt->start($participant);
        $attempt = CbtAttempt::query()->where('participant_id', $participant->getKey())->firstOrFail();
        $questions = CbtQuestion::query()->whereIn('id', (array) $attempt->question_order)->get()->keyBy('id');
        $correct = (int) round($accuracy * count((array) $attempt->question_order));

        foreach (array_values((array) $attempt->question_order) as $i => $questionId) {
            $key = (string) $questions[$questionId]->correct_option;
            $cbt->answer($attempt, (string) $questionId, $i < $correct ? $key : ($key === 'A' ? 'B' : 'A'));
        }

        $cbt->submit($attempt);
    }

    /** Run per gelombang-jalur → tinjau → ajukan → disetujui Ketua Panitia & Pejabat Pengesah → terbit sesuai jadwal. */
    private function selectWaveOne(): void
    {
        $runs = app(SelectionRunService::class);
        $committee = $this->staff['committee'];
        $submitted = [];

        $this->at(-32, 9);

        foreach (WavePath::query()->with('admissionPath')->where('wave_id', $this->wave('G1')->getKey())->get() as $wavePath) {
            $submitted[] = $this->actingAs($committee, function () use ($runs, $wavePath, $committee): SelectionRun {
                $run = $runs->compute($runs->create($wavePath, $committee));
                $run = $runs->startReview($run->refresh(), $committee);

                return $runs->submit($run, 'Hasil seleksi '.$wavePath->admissionPath->name.' Gelombang 1 siap disahkan.', $committee);
            });
        }

        foreach ([[-31, 'admin', 'Disetujui Ketua Panitia PMB.'], [-30, 'approver', 'Disahkan.']] as [$day, $role, $comment]) {
            $this->at($day, 14);

            foreach ($submitted as $run) {
                $step = ApprovalRequestStep::query()->findOrFail(ApprovalRequest::query()->findOrFail($run->approval_request_id)->current_step_id);
                $this->actingAs($this->staff[$role], fn (User $user) => app(ApprovalActionService::class)->approve($step, $user, $comment));
            }
        }

        // Batch pengumuman dijadwalkan pada announcement_at Gelombang 1; diterbitkan scheduler (pmb:tick).
        $this->travelTo(CarbonImmutable::parse($this->wave('G1')->announcement_at)->addMinutes(5));
        app(PublicationService::class)->publishDue();
        $this->processOutbox();
    }

    private function reRegisterWaveOne(): void
    {
        $confirmed = ['r1', 'r2', 'rk', 'u1', 'p1', 'r3', 'p2', 'u3', 'u6'];

        // Tawaran ditolak (r6) & diterima; data PDDikti + dokumen daftar ulang diisi keesokan harinya.
        $this->at(-27, 9);
        $this->decline('r6', 'Memilih melanjutkan studi di kampus lain.');

        foreach ($confirmed as $i => $key) {
            $this->at(-27, 11 + $i);
            $this->acceptOffer($key);
        }

        foreach ($confirmed as $i => $key) {
            $this->at(-26, 9 + $i);
            $this->completeReRegistrationData($key);
        }

        // Kursi Prestasi TI dilepas p3 → cadangan peringkat 1 (p4) dipromosikan, batas daftar ulang individual.
        $this->at(-26, 19);
        $this->decline('p3', 'Diterima di program beasiswa penuh perguruan tinggi lain.');

        $this->at(-25, 9);
        $this->actingAs($this->staff['admin'], fn (User $admin) => app(DecisionService::class)->promoteWaitlist(
            $this->quota('PRESTASI', 'TI1', 'REG'), 1, $this->moment(5, 23, 59)->toIso8601String(), $admin,
        ));
        $this->processOutbox();

        // Pembayaran biaya awal minimum (cicilan 1) — r1 melunasi seluruhnya di loket.
        foreach (['r1' => 'cashier_full', 'r2' => 'manual', 'rk' => 'cashier', 'u1' => 'manual', 'p1' => 'cashier', 'r3' => 'manual', 'p2' => 'cashier', 'u3' => 'manual'] as $key => $method) {
            $this->at(-25, 11);
            $this->payTuition($key, $method);
        }

        foreach (['r1', 'r2', 'rk', 'u1', 'p1', 'r3', 'p2', 'u3'] as $i => $key) {
            $this->at(-24, 10 + $i);
            $reRegistration = $this->reRegistration($key);
            app(ReRegistrationService::class)->submit($reRegistration, $reRegistration->lock_version, Actor::applicant($this->accounts[$key]));
        }

        // Petugas daftar ulang: periksa dokumen & dokumen asli, setujui → konversi mahasiswa (NIM).
        foreach (['r1', 'r2', 'rk', 'u1', 'p1'] as $i => $key) {
            $this->at(-22, 9 + $i);
            $this->approveReRegistration($key);
        }

        $this->at(-20, 10);
        $this->actingAs($this->staff['registrar'], fn (User $registrar) => app(ReRegistrationService::class)->requestRevision($this->reRegistration('u3'), ['documents' => ['STATEMENT_LETTER']], 'Surat pernyataan belum bermeterai dan belum ditandatangani orang tua. Unggah ulang versi yang sudah bermeterai.', $registrar));

        // Mahasiswa baru (u1) bertanya lewat chat — sudah dijawab & ditutup.
        $chat = app(CommunicationService::class);
        $this->at(-20, 13);
        $thread = $chat->openThread($this->accounts['u1'], 'Jadwal orientasi mahasiswa baru', 'Selamat siang, kapan jadwal orientasi mahasiswa baru dan apakah jas almamater diambil saat orientasi?', (string) $this->applications['u1']->getKey(), null);
        $this->at(-19, 9);
        $this->actingAs($this->staff['registrar'], fn (User $registrar) => $chat->reply($thread, $registrar, 'Orientasi dijadwalkan minggu kedua Agustus 2027. Jas almamater dibagikan pada hari pertama orientasi sesuai ukuran yang Anda pilih saat daftar ulang.', null));
        $chat->updateThread($thread->refresh(), ['status' => 'closed']);

        // Sedang mengisi data & dokumen (belum bayar), baru konfirmasi; r5 belum konfirmasi sama sekali.
        $this->at(-10, 20);
        $this->acceptOffer('r4');
        $this->at(-6, 21);
        $this->completeReRegistrationData('r4', ['FAMILY_CARD']);
        $this->at(-5, 19);
        $this->acceptOffer('u2');

        // Siap disetujui: dokumen & dokumen asli sudah diperiksa, tinggal persetujuan akhir petugas.
        $this->at(-4, 10);
        $this->approveReRegistration('p2', approve: false);

        // Menunggu verifikasi bukti transfer biaya awal oleh keuangan.
        $this->at(-3, 19);
        $this->payTuition('u6', 'proof');
    }

    private function decline(string $key, string $reason): void
    {
        app(ReRegistrationService::class)->decline($this->reRegistration($key), $reason, Actor::applicant($this->accounts[$key]));
        $this->processOutbox();
    }

    private function acceptOffer(string $key): void
    {
        $reRegistration = $this->reRegistration($key);
        app(ReRegistrationService::class)->accept($reRegistration, [], $reRegistration->lock_version, self::IP, self::USER_AGENT, Actor::applicant($this->accounts[$key]));
    }

    /**
     * Data PDDikti + ukuran almamater + dokumen tahap daftar ulang.
     *
     * @param  array<int, string>|null  $only  kode dokumen yang diunggah (null = semua)
     */
    private function completeReRegistrationData(string $key, ?array $only = null): void
    {
        [$name, , , $cityKey, , $religion, , $mother] = self::APPLICANTS[$key];
        [$city, $province, $region, $district, $village] = self::CITIES[$cityKey];
        $reRegistration = $this->reRegistration($key);
        $actor = Actor::applicant($this->accounts[$key]);

        app(ReRegistrationService::class)->saveData($reRegistration, [
            'religion' => $religion, 'mother_name' => $mother, 'nationality' => 'ID', 'address_line' => $this->address($key),
            'village' => $village, 'district' => $district, 'city' => $city, 'province' => $province, 'region_code' => $region.'1001',
            'residence_type' => $cityKey === 'JKS' || $cityKey === 'JKT' || $cityKey === 'JKU' ? 'orang_tua' : 'kos',
            'transportation' => $key === 'rk' ? 'motor' : 'angkutan_umum', 'kps_recipient' => false,
            'almamater_size' => $this->pick($key, ['S', 'M', 'L', 'XL']),
        ], $reRegistration->lock_version, $actor);

        $application = $this->applications[$key]->refresh();

        foreach ($this->documentsOf($application, DocumentStage::ReRegistration, false) as $document) {
            $code = $document->documentType->code;

            if ($only !== null && ! in_array($code, $only, true)) {
                continue;
            }

            $title = match ($code) {
                'FAMILY_CARD' => 'KARTU KELUARGA',
                'DIPLOMA_OR_SKL' => 'SURAT KETERANGAN LULUS',
                'STATEMENT_LETTER' => 'SURAT PERNYATAAN MAHASISWA BARU',
                'HEALTH_CERT' => 'SURAT KETERANGAN SEHAT',
                default => Str::upper($document->documentType->name),
            };
            app(DocumentService::class)->upload($application, $document, $this->pdfFile(Str::slug($code).'.pdf', $title, [
                'Nama: '.$name, 'Nomor pendaftaran: '.$application->registration_number, 'Dokumen contoh data demo PMB Atma Jaya.',
            ]), $actor);
        }
    }

    /**
     * @param  string  $method  cashier_full | cashier | manual (bukti disetujui) | proof (bukti menunggu verifikasi)
     */
    private function payTuition(string $key, string $method): void
    {
        $application = $this->applications[$key];
        $invoice = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', InvoiceType::InitialTuition->value)->firstOrFail();
        $minimum = (string) ($invoice->minimum_for_enrollment ?? $invoice->amount_net);

        if (str_starts_with($method, 'cashier')) {
            $this->actingAs($this->staff['finance'], fn (User $finance) => app(PaymentService::class)->recordCashierPayment(
                $invoice, $method === 'cashier_full' ? $invoice->outstanding() : $minimum, $this->receiptNumber(), now()->toIso8601String(), $finance,
            ));

            return;
        }

        $transaction = app(PaymentService::class)->createTransaction($invoice, 'manual_transfer', $minimum, (string) Str::uuid(), Actor::applicant($this->accounts[$key]));
        $proof = $this->submitProof($key, $transaction);

        if ($method === 'manual') {
            $this->decideProof($proof, true);
        }
    }

    private function approveReRegistration(string $key, bool $approve = true): void
    {
        $registrar = $this->staff['registrar'];

        $this->actingAs($registrar, function () use ($key, $approve, $registrar): void {
            $application = $this->applications[$key]->refresh();

            foreach ($this->documentsOf($application, DocumentStage::ReRegistration) as $document) {
                app(DocumentService::class)->review($document, ['decision' => 'approved', 'version_id' => (string) $document->current_version_id], $registrar);
            }

            $service = app(ReRegistrationService::class);
            $service->markCheck($this->reRegistration($key), 'ORIGINAL_DOCUMENTS_CHECKED', 'passed', 'Dokumen asli diperiksa di loket daftar ulang.', $registrar);

            if ($approve) {
                $reRegistration = $this->reRegistration($key);
                $service->approve($reRegistration, $reRegistration->lock_version, $registrar);
            }
        });

        $this->processOutbox();
    }

    // ================================================================== Gelombang 2

    private function seedWaveTwo(): void
    {
        foreach (['g17' => -10, 'g18' => -5] as $key => $day) {
            $this->account($key, $day, 20);
        }

        foreach (self::WAVE_TWO as $key => [$day, $path, $choices, $report, $stage]) {
            $this->account($key, $day - 1, 19);

            match ($stage) {
                'draft_identity' => $this->draft($key, 'G2', $path, $day, identityOnly: true),
                'draft' => $this->draft($key, 'G2', $path, $day, choices: $choices, report: $report),
                default => $this->seedWaveTwoApplication($key, $path, $choices, $report, $day, $stage),
            };
        }

        $this->examsWaveTwo();
        $this->seedWaveTwoSupport();
    }

    /** @param  array<int, string>  $choices */
    private function seedWaveTwoApplication(string $key, string $path, array $choices, float $report, int $day, string $stage): void
    {
        $application = $this->submitted($key, 'G2', $path, $choices, $report, $day);

        match ($stage) {
            'unpaid' => $this->invoiceOnly($application),
            'transfer_pending' => null, // instruksi transfer dibuat pada waktu seeding (finish) agar belum kedaluwarsa
            'proof' => $this->payRegistration($application, 'manual', $day + 1),
            'proof_rejected' => $this->rejectedProof($application, $day),
            'voucher' => $this->voucherPayment($application, $day),
            default => $this->paidAndProcessed($application, $key, $day, $stage),
        };
    }

    private function invoiceOnly(Application $application): void
    {
        app(InvoiceService::class)->ensureRegistrationInvoice($application);
    }

    private function rejectedProof(Application $application, int $day): void
    {
        $proof = $this->payRegistration($application, 'manual', $day, shortTransfer: true);
        $this->at($day + 1, 10);
        $this->decideProof($proof, false);
    }

    private function voucherPayment(Application $application, int $day): void
    {
        $proof = $this->payRegistration($application, 'manual', $day, voucher: 'EARLYBIRD27');
        $this->at($day + 1, 10);
        $this->decideProof($proof, true);
    }

    private function paidAndProcessed(Application $application, string $key, int $day, string $stage): void
    {
        $proof = $this->payRegistration($application, $key === 'g08' || $key === 'g11' ? 'cashier' : 'manual', $day);

        if ($proof !== null) {
            $this->at($day + 1, 9);
            $this->decideProof($proof, true);
        }

        $verifier = $this->staff['verifier'];

        match ($stage) {
            'paid' => null,
            'in_review' => $this->atThen($day + 2, 9, fn () => $this->actingAs($this->staff['admin'], fn (User $admin) => app(VerificationService::class)->assign([(string) $application->getKey()], [(string) $verifier->getKey()], 'round_robin', $admin))),
            'revision' => $this->requestRevision($application, $day + 3, $this->moment(5, 23, 59)),
            'resubmitted' => $this->resubmitAfterRevision($key, $application, $day),
            'verified' => $this->verify($application, $day + 3),
            default => null,
        };
    }

    private function requestRevision(Application $application, int $day, CarbonImmutable $due): void
    {
        $this->at($day, 10);
        $verifier = $this->staff['verifier'];

        $this->actingAs($verifier, function () use ($application, $due, $verifier): void {
            $service = app(VerificationService::class);
            $service->claim($application, $verifier);
            $target = null;

            foreach ($this->documentsOf($application, DocumentStage::Application) as $document) {
                $blurred = $document->documentType->code === 'REPORT_CARD';
                $target = $blurred ? $document : $target;
                app(DocumentService::class)->review($document, [
                    'decision' => $blurred ? 'revision_requested' : 'approved', 'version_id' => (string) $document->current_version_id,
                    'reason_code' => $blurred ? 'UNREADABLE' : null,
                    'note_for_applicant' => $blurred ? 'Halaman semester 4–5 buram dan tidak terbaca. Pindai ulang seluruh halaman rapor dalam satu PDF.' : null,
                ], $verifier);
            }

            $application->refresh();
            $service->requestRevision($application, ['documents' => [(string) $target->getKey()]], 'Mohon unggah ulang rapor semester 1–5 dengan hasil pindai yang jelas terbaca.', $due->toIso8601String(), $application->lock_version, $verifier);
        });
    }

    private function resubmitAfterRevision(string $key, Application $application, int $day): void
    {
        $this->requestRevision($application, $day + 2, $this->moment($day + 5, 23, 59));

        $this->at($day + 4, 20);
        $application->refresh();
        $actor = Actor::applicant($this->accounts[$key]);
        $document = $this->documentsOf($application, DocumentStage::Application)->first(fn (ApplicationDocument $d) => $d->documentType->code === 'REPORT_CARD');
        app(DocumentService::class)->upload($application, $document, $this->reportCardFile($key, $application), $actor);
        $application->refresh();
        app(ApplicationService::class)->submit($application, $this->declarations, $application->lock_version, self::IP, self::USER_AGENT, $actor);
    }

    /** Peserta G2 terverifikasi ditempatkan di jadwal CBT/wawancara Gelombang 2; satu meminta jadwal ulang. */
    private function examsWaveTwo(): void
    {
        $this->at(-1, 10);
        $this->actingAs($this->staff['committee'], function (): void {
            foreach (ExamSchedule::query()->where('wave_id', $this->wave('G2')->getKey())->get() as $schedule) {
                app(ExamService::class)->autoAssign($schedule);
            }
        });
        $this->processOutbox();

        $this->at(-1, 13);
        $participant = ExamParticipant::query()->where('application_id', $this->applications['g14']->getKey())->firstOrFail();
        app(ExamService::class)->requestReschedule($participant, 'Bentrok dengan ujian praktik sekolah pada tanggal yang sama (surat sekolah menyusul).', null);
    }

    /** Chat, klaim identitas ganda, pemulihan akun, broadcast. */
    private function seedWaveTwoSupport(): void
    {
        $chat = app(CommunicationService::class);

        $this->at(-8, 19);
        $thread = $chat->openThread($this->accounts['g10'], 'Status verifikasi berkas', 'Halo panitia, berkas saya sudah lengkap dan biaya sudah dibayar. Kira-kira kapan verifikasi selesai?', (string) $this->applications['g10']->getKey(), null);
        $this->at(-7, 9);
        $this->actingAs($this->staff['verifier'], fn (User $verifier) => $chat->reply($thread, $verifier, 'Berkas Anda sedang kami periksa. Hasil verifikasi akan muncul di portal paling lambat 2 hari kerja.', null));

        $this->at(-2, 21);
        $chat->openThread($this->accounts['g03'], 'Pembayaran di loket kampus', 'Apakah biaya pendaftaran bisa dibayar tunai di loket kampus Semanggi pada hari Sabtu?', (string) $this->applications['g03']->getKey(), null);

        // Akun kedua dengan NIK yang sudah dipakai g13 → tiket klaim identitas untuk petugas.
        $this->account('g19', -3, 19);
        $this->at(-3, 20);
        app(ProfileService::class)->claimIdentity($this->accounts['g19'], $this->nik('g13'), 'Saya lupa pernah membuat akun dengan email sekolah. Mohon akun lama dinonaktifkan.');

        $this->at(-2, 10);
        app(AccountService::class)->submitRecoveryRequest([
            'identity_number' => $this->nik('g08'), 'full_name' => self::APPLICANTS['g08'][0], 'birth_date' => self::APPLICANTS['g08'][2],
            'new_email' => 'gilang.saputra.baru@'.self::EMAIL_DOMAIN,
        ], null);

        $broadcasts = app(CommunicationService::class);
        $admin = $this->staff['admin'];
        $wave = $this->wave('G2');

        $this->at(-5, 9);
        $this->actingAs($admin, function () use ($broadcasts, $admin, $wave): void {
            $sent = $broadcasts->saveBroadcast(null, [
                'title' => 'Pengingat pembayaran Gelombang 2', 'channel' => 'email',
                'audience_filter' => ['wave_id' => (string) $wave->getKey(), 'registration_payment_status' => ['unpaid', 'pending']],
                'content_subject' => 'Selesaikan pembayaran biaya pendaftaran PMB Atma Jaya',
                'content_body' => 'Formulir Anda sudah kami terima. Selesaikan pembayaran biaya pendaftaran agar berkas dapat segera diverifikasi.',
            ], $admin);
            $broadcasts->schedule($sent, null);
        });
        $this->processOutbox();

        $this->at(-1, 16);
        $this->actingAs($admin, function () use ($broadcasts, $admin, $wave): void {
            $closing = $broadcasts->saveBroadcast(null, [
                'title' => 'Pendaftaran Gelombang 2 segera ditutup', 'channel' => 'email',
                'audience_filter' => ['wave_id' => (string) $wave->getKey(), 'form_status' => ['draft']],
                'content_subject' => 'Pendaftaran Gelombang 2 ditutup 10 hari lagi',
                'content_body' => 'Lengkapi dan kirim formulir pendaftaran Anda sebelum Gelombang 2 ditutup.',
            ], $admin);
            $broadcasts->schedule($closing, CarbonImmutable::parse($wave->registration_closes_at)->subDays(10)->setTime(9, 0)->toIso8601String());

            $broadcasts->saveBroadcast(null, [
                'title' => 'Undangan Campus Tour', 'channel' => 'email', 'audience_filter' => [],
                'content_subject' => 'Campus Tour Atma Jaya — Kampus Semanggi',
                'content_body' => 'Kenali fasilitas kampus, laboratorium, dan kegiatan mahasiswa bersama duta kampus. Daftar melalui portal pendaftar.',
            ], $admin);
        });
    }

    /** Kembali ke waktu nyata: instruksi transfer yang masih berlaku, outbox, statistik harian. */
    private function finish(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        app(PeriodService::class)->refreshWaveStatuses($this->period);

        $this->payRegistration($this->applications['g04'], 'transfer_pending', 0);
        $this->processOutbox();

        $reports = app(ReportService::class);

        for ($day = -75; $day <= -1; $day++) {
            $reports->snapshotDaily($this->today->addDays($day));
        }
    }

    // ================================================================== Langkah pendaftar

    private function account(string $key, int $day, int $hour = 20): ApplicantAccount
    {
        $this->at($day, $hour, 15);
        $account = ApplicantAccount::query()->create([
            'university_id' => $this->university->getKey(), 'name' => self::APPLICANTS[$key][0], 'email' => $this->email($key), 'phone' => $this->phone($key, '813'),
            'password' => $this->passwordHash, 'status' => AccountStatus::Active, 'email_verified_at' => now(), 'info_source' => self::APPLICANTS[$key][8],
        ]);

        foreach ($this->legal as [$type, $contentId, $version]) {
            app(AccountService::class)->recordConsent($account, $type, $contentId, $version, self::IP, self::USER_AGENT);
        }

        return $this->accounts[$key] = $account;
    }

    /**
     * Draft: identitas saja, atau lengkap kecuali dokumen.
     *
     * @param  array<int, string>  $choices
     */
    private function draft(string $key, string $waveCode, string $path, int $day, bool $identityOnly = false, array $choices = [], float $report = 0): Application
    {
        $this->at($day, 19, 30);
        $account = $this->accounts[$key];
        $actor = Actor::applicant($account);
        $service = app(ApplicationService::class);
        $application = $service->createDraft($account, (string) $this->wavePath($waveCode, $path)->getKey(), $actor);
        $application = $service->saveSection($application, 'identity', $this->identity($key), $application->lock_version, $actor);

        if (! $identityOnly) {
            $application = $this->fillEducationAndSupport($key, $application, $report, $actor);
            $application = $service->saveChoices($application, $this->choiceRows($choices), $application->lock_version);
        }

        return $this->applications[$key] = $application;
    }

    /**
     * Formulir lengkap + dokumen → dikirim.
     *
     * @param  array<int, string>  $choices
     */
    private function submitted(string $key, string $waveCode, string $path, array $choices, float $report, int $day): Application
    {
        $application = $this->draft($key, $waveCode, $path, $day, choices: $choices, report: $report);
        $actor = Actor::applicant($this->accounts[$key]);

        $this->at($day, 20, 10);
        $this->uploadApplicationDocuments($key, $application, $actor);
        $application->refresh();
        $application = app(ApplicationService::class)->submit($application, $this->declarations, $application->lock_version, self::IP, self::USER_AGENT, $actor);

        return $this->applications[$key] = $application;
    }

    private function fillEducationAndSupport(string $key, Application $application, float $report, Actor $actor): Application
    {
        [, , , $cityKey, $school, , $father, $mother, $source] = self::APPLICANTS[$key];
        [$city, $province] = self::CITIES[$cityKey];
        $profiles = app(ProfileService::class);
        $service = app(ApplicationService::class);
        $account = $this->accounts[$key];
        $worker = $key === 'rk';

        $education = $profiles->saveEducation($account, null, [
            'level' => 'secondary', 'institution_name' => $school, 'school_type' => str_starts_with($school, 'SMK') ? 'smk' : 'sma',
            'major' => $worker ? 'Teknik Komputer dan Jaringan' : 'IPA', 'graduation_year' => $worker ? 2019 : 2027, 'is_graduating' => ! $worker,
            'nisn' => '00'.substr($this->nik($key), -8), 'city' => $city, 'province' => $province, 'country' => 'ID',
        ]);
        $profiles->replaceReportScores($education, $this->reportScores($report));
        $application = $service->saveSection($application, 'education', ['primary_education_id' => (string) $education->getKey()], $application->lock_version, $actor);

        [$occupation, $level, $income] = self::OCCUPATIONS[crc32($key) % count(self::OCCUPATIONS)];
        $profiles->saveGuardian($account, null, ['relationship' => 'father', 'full_name' => $father, 'phone' => $this->phone($key, '811'), 'occupation' => $occupation, 'education_level' => $level, 'income_range' => $income]);
        $profiles->saveGuardian($account, null, ['relationship' => 'mother', 'full_name' => $mother, 'phone' => $this->phone($key, '812'), 'occupation' => $this->pick($key, ['Ibu rumah tangga', 'Guru', 'Karyawan swasta', 'Perawat']), 'education_level' => 'S1', 'income_range' => 'none']);

        foreach (self::ACHIEVEMENTS[$key] ?? [] as [$name, $category, $achievementLevel, $rank, $year, $organizer]) {
            $profiles->saveAchievement($account, null, ['name' => $name, 'category' => $category, 'level' => $achievementLevel, 'rank' => $rank, 'year' => $year, 'organizer' => $organizer]);
        }

        return $service->saveSection($application, 'supporting', ['info_source' => $source, 'scholarship_interest' => isset(self::ACHIEVEMENTS[$key])], $application->lock_version, $actor);
    }

    /** Pas foto, KTP/Kartu Pelajar, rapor (PDF), dan sertifikat prestasi (satu slot per prestasi). */
    private function uploadApplicationDocuments(string $key, Application $application, Actor $actor): void
    {
        $documents = app(DocumentService::class);
        $achievements = ApplicantAchievement::query()->where('applicant_account_id', $this->accounts[$key]->getKey())->orderBy('created_at')->orderBy('id')->get()->values();
        $certificate = ApplicationDocument::query()->with('documentType')->where('application_id', $application->getKey())->where('stage', DocumentStage::Application->value)
            ->whereHas('documentType', fn ($q) => $q->where('code', 'ACHIEVEMENT_CERT'))->first();

        for ($slot = 2; $certificate !== null && $slot <= $achievements->count(); $slot++) {
            $documents->addSlot($application, (string) $certificate->document_type_id, DocumentStage::Application);
        }

        [$name, , $birth, $cityKey] = self::APPLICANTS[$key];

        foreach ($this->documentsOf($application, DocumentStage::Application, false) as $document) {
            $file = match ($document->documentType->code) {
                'PHOTO' => $this->file('pas-foto.jpg', $this->photo($name)),
                'ID_CARD' => $this->file('kartu-identitas.png', $this->image(856, 540, [
                    'KARTU TANDA PENDUDUK (CONTOH DATA DEMO)', '', 'NIK          : '.$this->nik($key), 'Nama         : '.$name,
                    'Tempat/Tgl   : '.self::CITIES[$cityKey][5].', '.CarbonImmutable::parse($birth)->format('d-m-Y'), 'Alamat       : '.$this->address($key), 'Kota         : '.self::CITIES[$cityKey][0],
                ], [226, 236, 248], 'png')),
                'REPORT_CARD' => $this->reportCardFile($key, $application),
                'ACHIEVEMENT_CERT' => $this->achievementFile($achievements->get($document->slot - 1), $name),
                default => $this->pdfFile(Str::slug($document->documentType->code).'.pdf', Str::upper($document->documentType->name), ['Nama: '.$name]),
            };

            $documents->upload($application, $document, $file, $actor);

            if ($document->documentType->code === 'ACHIEVEMENT_CERT' && $achievements->has($document->slot - 1)) {
                $achievements->get($document->slot - 1)->forceFill(['application_document_id' => $document->getKey()])->save();
            }
        }
    }

    /**
     * Tagihan biaya pendaftaran + pembayaran; mengembalikan bukti transfer yang menunggu keputusan keuangan.
     *
     * @param  string  $method  cashier | manual (unggah bukti) | transfer_pending (instruksi saja)
     */
    private function payRegistration(Application $application, string $method, int $day, ?string $voucher = null, bool $shortTransfer = false): ?ManualPaymentProof
    {
        if ($day < 0) {
            $this->at($day, 21);
        }

        $key = $this->keyOf($application);
        $invoice = app(InvoiceService::class)->ensureRegistrationInvoice($application);

        if ($voucher !== null) {
            $invoice = app(InvoiceService::class)->applyVoucher($invoice, $voucher);
        }

        if ($method === 'cashier') {
            $this->actingAs($this->staff['finance'], fn (User $finance) => app(PaymentService::class)->recordCashierPayment($invoice->refresh(), $invoice->refresh()->outstanding(), $this->receiptNumber(), now()->toIso8601String(), $finance));

            return null;
        }

        $transaction = app(PaymentService::class)->createTransaction($invoice, 'manual_transfer', null, (string) Str::uuid(), Actor::applicant($this->accounts[$key]));

        return $method === 'transfer_pending' ? null : $this->submitProof($key, $transaction, $shortTransfer);
    }

    private function submitProof(string $key, PaymentTransaction $transaction, bool $shortTransfer = false): ManualPaymentProof
    {
        [$name, , , , , , $father] = self::APPLICANTS[$key];
        $amount = $shortTransfer ? number_format((float) $transaction->amount - (float) $transaction->unique_code, 2, '.', '') : (string) $transaction->amount;

        return app(PaymentService::class)->submitManualProof($transaction, $this->file('bukti-transfer.jpg', $this->image(600, 800, [
            'BUKTI TRANSFER (CONTOH DATA DEMO)', '', 'Tanggal  : '.now()->format('d-m-Y H:i'), 'Dari     : '.$father, 'Bank     : Bank Demo',
            'Ke       : '.$this->university->name, 'Nominal  : Rp'.number_format((float) $amount, 0, ',', '.'), 'Berita   : PMB '.$name, 'Status   : BERHASIL',
        ], [250, 250, 245], 'jpg')), [
            'sender_bank' => 'Bank Demo', 'sender_name' => $father, 'transfer_date' => now()->toDateString(), 'transferred_amount' => $amount,
        ], Actor::applicant($this->accounts[$key]));
    }

    private function decideProof(ManualPaymentProof $proof, bool $approve): void
    {
        $this->actingAs($this->staff['finance'], fn (User $finance) => app(PaymentService::class)->decideManualProof(
            $proof, $approve, $approve ? null : 'AMOUNT_MISMATCH', $approve ? null : 'Nominal transfer kurang dari tagihan (kode unik tidak disertakan). Silakan buat instruksi baru.', $finance,
        ));
        $this->processOutbox();
    }

    /** Klaim → setujui seluruh dokumen → verifikasi (nilai rapor/prestasi dihitung otomatis). */
    private function verify(Application $application, int $day): void
    {
        $this->at($day, 10);
        $verifier = $this->staff['verifier'];

        $this->actingAs($verifier, function () use ($application, $verifier): void {
            $service = app(VerificationService::class);
            $service->claim($application, $verifier);

            foreach ($this->documentsOf($application, DocumentStage::Application) as $document) {
                app(DocumentService::class)->review($document, ['decision' => 'approved', 'version_id' => (string) $document->current_version_id], $verifier);
            }

            $application->refresh();
            $service->verify($application, $application->lock_version, null, $verifier);
        });
    }

    // ================================================================== Data pendukung

    /** @return array<string, mixed> */
    private function identity(string $key): array
    {
        [$name, $gender, $birth, $cityKey, , $religion, , $mother] = self::APPLICANTS[$key];
        [$city, $province, $region, $district, $village, $birthPlace, $postal] = self::CITIES[$cityKey];

        return [
            'full_name' => $name, 'nationality' => 'ID', 'nik' => $this->nik($key), 'birth_place' => $birthPlace, 'birth_date' => $birth,
            'gender' => $gender, 'religion' => $religion, 'mother_name' => $mother, 'address_line' => $this->address($key),
            'rt' => sprintf('%03d', crc32($key) % 12 + 1), 'rw' => sprintf('%03d', crc32($name) % 9 + 1),
            'village' => $village, 'district' => $district, 'city' => $city, 'province' => $province, 'region_code' => $region.'1001', 'postal_code' => $postal,
        ];
    }

    /**
     * @param  array<int, string>  $choices  KODE atau KODE:SISTEM
     * @return array<int, array{priority: int, study_program_id: string, study_system_id: string}>
     */
    private function choiceRows(array $choices): array
    {
        return array_map(function (string $choice, int $index): array {
            [$program, $system] = array_pad(explode(':', $choice), 2, 'REG');

            return ['priority' => $index + 1, 'study_program_id' => (string) $this->programs[$program]->getKey(), 'study_system_id' => (string) $this->systems[$system]->getKey()];
        }, $choices, array_keys($choices));
    }

    /**
     * Rapor semester 1–5: simpangan −2..+2 yang saling meniadakan per mapel,
     * sehingga rata-ratanya tepat sama dengan target (hasil seleksi dapat diprediksi).
     *
     * @return array<int, array{semester: int, subject_code: string, subject_name: string, score: float}>
     */
    private function reportScores(float $average): array
    {
        $rows = [];

        foreach ([1, 2, 3, 4, 5] as $semester) {
            $offset = 0;

            foreach (self::SUBJECTS as $code => $name) {
                $rows[] = ['semester' => $semester, 'subject_code' => $code, 'subject_name' => $name, 'score' => $average + (($semester + $offset++) % 5) - 2];
            }
        }

        return $rows;
    }

    /** NIK konsisten dengan tanggal lahir (tanggal +40 untuk perempuan, 04 §D.2.1). */
    private function nik(string $key): string
    {
        [, $gender, $birth, $cityKey] = self::APPLICANTS[$key];
        $date = CarbonImmutable::parse($birth);
        $day = (int) $date->format('d') + ($gender === 'female' ? 40 : 0);
        $serial = $key === 'g19' ? $this->serial('g13') : $this->serial($key);

        return self::CITIES[$cityKey][2].sprintf('%02d', $day).$date->format('my').sprintf('%04d', $serial);
    }

    private function serial(string $key): int
    {
        return (int) array_search($key, array_keys(self::APPLICANTS), true) + 1;
    }

    private function email(string $key): string
    {
        return Str::slug(self::APPLICANTS[$key][0], '.').'@'.self::EMAIL_DOMAIN;
    }

    private function phone(string $key, string $prefix): string
    {
        return '+62'.$prefix.sprintf('%08d', 31_000_000 + $this->serial($key) * 7919);
    }

    private function address(string $key): string
    {
        return self::STREETS[crc32($key) % count(self::STREETS)].' No. '.(crc32(strrev($key)) % 90 + 1);
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $options
     * @return T
     */
    private function pick(string $key, array $options): mixed
    {
        return $options[crc32($key.'pick') % count($options)];
    }

    private function receiptNumber(): string
    {
        return 'LKT-UAJ-'.$this->today->format('y').sprintf('%05d', ++$this->receipt);
    }

    // ================================================================== Berkas contoh

    private function reportCardFile(string $key, Application $application): UploadedFile
    {
        [$name, , , , $school] = self::APPLICANTS[$key];
        $lines = ['Nama: '.$name, 'Sekolah: '.$school, 'Nomor pendaftaran: '.($application->registration_number ?? '-'), ''];
        $byCode = collect($this->reportScores((float) (self::WAVE_ONE[$key][3] ?? self::WAVE_TWO[$key][3] ?? 80)))->groupBy('subject_code');

        foreach (self::SUBJECTS as $code => $subject) {
            $lines[] = str_pad($subject, 26).' '.$byCode[$code]->pluck('score')->map(fn ($s) => sprintf('%5.1f', $s))->implode('  ');
        }

        return $this->pdfFile('rapor-semester-1-5.pdf', 'RAPOR SEMESTER 1-5 (CONTOH DATA DEMO)', [...$lines, '', 'Semester:                   1      2      3      4      5']);
    }

    private function achievementFile(?ApplicantAchievement $achievement, string $name): UploadedFile
    {
        return $this->pdfFile('sertifikat-prestasi.pdf', 'SERTIFIKAT PRESTASI (CONTOH DATA DEMO)', [
            'Diberikan kepada: '.$name, 'Atas prestasi: '.($achievement->name ?? 'Prestasi'), 'Peringkat: '.($achievement->rank ?? '-').' — tingkat '.($achievement->level ?? '-'),
            'Tahun: '.($achievement->year ?? '-'), 'Penyelenggara: '.($achievement->organizer ?? '-'),
        ]);
    }

    /** @param  array<int, string>  $lines */
    private function pdfFile(string $name, string $title, array $lines): UploadedFile
    {
        return $this->file($name, $this->pdf($title, $lines));
    }

    private function file(string $name, string $contents): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pmbdemo');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * PDF satu halaman (Helvetica) dengan tabel xref yang benar — lolos
     * validasi PmbFileStorage (magic bytes, tanpa enkripsi, ≤ 20 halaman).
     *
     * @param  array<int, string>  $lines
     */
    private function pdf(string $title, array $lines): string
    {
        $text = fn (string $value): string => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii($value));
        $stream = 'BT /F1 15 Tf 56 780 Td ('.$text($title).") Tj ET\n";

        foreach (array_values($lines) as $i => $line) {
            $stream .= 'BT /F1 10 Tf 56 '.(748 - $i * 16).' Td ('.$text($line).") Tj ET\n";
        }

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    /** Pas foto 3:4 (450×600) dengan siluet & nama — unik per pendaftar. */
    private function photo(string $name): string
    {
        $image = imagecreatetruecolor(450, 600);
        imagefilledrectangle($image, 0, 0, 450, 600, (int) imagecolorallocate($image, 176, 205, 236));
        $figure = (int) imagecolorallocate($image, 88, 104, 128);
        imagefilledellipse($image, 225, 600, 400, 330, $figure);
        imagefilledellipse($image, 225, 250, 190, 230, $figure);
        imagestring($image, 5, 20, 20, Str::ascii($name), (int) imagecolorallocate($image, 255, 255, 255));

        return $this->encode($image, 'jpg');
    }

    /**
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     * @param  array<int, string>  $lines
     * @param  array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}  $background
     */
    private function image(int $width, int $height, array $lines, array $background, string $format): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, (int) imagecolorallocate($image, ...$background));
        imagerectangle($image, 8, 8, $width - 9, $height - 9, (int) imagecolorallocate($image, 120, 130, 150));
        $ink = (int) imagecolorallocate($image, 30, 35, 45);

        foreach ($lines as $i => $line) {
            imagestring($image, 5, 32, 32 + $i * 28, Str::ascii($line), $ink);
        }

        return $this->encode($image, $format);
    }

    private function encode(\GdImage $image, string $format): string
    {
        ob_start();
        $format === 'png' ? imagepng($image) : imagejpeg($image, null, 88);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    // ================================================================== Infrastruktur

    private function moment(int $days, int $hour = 9, int $minute = 0): CarbonImmutable
    {
        return $this->today->addDays($days)->setTime($hour, $minute)->setTimezone(config('app.timezone'));
    }

    private function at(int $days, int $hour = 9, int $minute = 0): void
    {
        $this->travelTo($this->moment($days, $hour, $minute));
    }

    /** Jalankan aksi pada waktu tertentu (dipakai di ekspresi match). */
    private function atThen(int $days, int $hour, callable $callback): mixed
    {
        $this->at($days, $hour);

        return $callback();
    }

    /** "Mesin waktu" demo: jam aplikasi dipindah, status gelombang mengikuti jam. */
    private function travelTo(CarbonImmutable $moment): void
    {
        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);
        app(PeriodService::class)->refreshWaveStatuses($this->period);
    }

    /**
     * Aksi staf dengan pengguna terautentikasi (atribusi audit log).
     *
     * @template T
     *
     * @param  callable(User): T  $callback
     * @return T
     */
    private function actingAs(User $user, callable $callback): mixed
    {
        Auth::setUser($user);

        try {
            return $callback($user);
        } finally {
            Auth::forgetUser();
        }
    }

    /** Efek samping outbox (notifikasi, kartu ujian, LoA, konversi) diproses pada jam demo saat itu. */
    private function processOutbox(): void
    {
        for ($round = 0; $round < 10 && app(OutboxProcessor::class)->processPending(500) > 0; $round++) {
            // ulangi sampai tidak ada event baru (handler dapat mencatat event lanjutan)
        }
    }

    /** Pesan ke konsol artisan; seeder juga dipanggil langsung (tes) tanpa command. */
    private function note(string $message, bool $warning = false): void
    {
        if ($this->command instanceof Command) {
            $warning ? $this->command->warn($message) : $this->command->info($message);
        }
    }

    private function sessionStaff(ExamSession $session, ExaminerRole $role): User
    {
        return ExamSessionExaminer::query()->with('user')->where('session_id', $session->getKey())->where('role', $role->value)->first()->user
            ?? $this->staff['committee'];
    }

    /** @return Collection<int, ApplicationDocument> */
    private function documentsOf(Application $application, DocumentStage $stage, bool $uploadedOnly = true)
    {
        return ApplicationDocument::query()->with('documentType')->where('application_id', $application->getKey())->where('stage', $stage->value)
            ->when($uploadedOnly, fn ($q) => $q->whereNotNull('current_version_id'))->orderBy('document_type_id')->orderBy('slot')->get();
    }

    private function reRegistration(string $key): ReRegistration
    {
        return ReRegistration::query()->where('application_id', $this->applications[$key]->getKey())->firstOrFail();
    }

    private function keyOf(Application $application): string
    {
        return (string) array_search((string) $application->getKey(), array_map(fn (Application $a) => (string) $a->getKey(), $this->applications), true);
    }

    private function wave(string $code): Wave
    {
        return Wave::query()->where('period_id', $this->period->getKey())->where('code', $code)->firstOrFail();
    }

    private function wavePath(string $waveCode, string $pathCode): WavePath
    {
        return WavePath::query()->where('wave_id', $this->wave($waveCode)->getKey())
            ->where('admission_path_id', AdmissionPath::query()->where('code', $pathCode)->value('id'))->firstOrFail();
    }

    private function quota(string $pathCode, string $programCode, string $systemCode): Quota
    {
        return Quota::query()->where('period_id', $this->period->getKey())
            ->where('admission_path_id', AdmissionPath::query()->where('code', $pathCode)->value('id'))
            ->where('study_program_id', $this->programs[$programCode]->getKey())->where('study_system_id', $this->systems[$systemCode]->getKey())->firstOrFail();
    }
}

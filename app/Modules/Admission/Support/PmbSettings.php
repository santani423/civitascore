<?php

namespace Modules\Admission\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Modules\SystemSetting\Enums\SettingValueType;
use Modules\Tenancy\Models\UniversitySetting;

/**
 * Pengaturan PMB per tenant (`university_settings` grup `pmb`, 03 FR-CFG-12)
 * dengan default dari rancangan bila tenant belum mengatur.
 */
class PmbSettings
{
    /**
     * Default rancangan (docs/pmb/02 & 03 FR-CFG-12). Kunci di luar daftar ini ditolak.
     *
     * @var array<string, array{0: SettingValueType, 1: mixed, 2: string}>
     */
    public const DEFINITIONS = [
        'pmb.max_active_applications_per_period' => [SettingValueType::Integer, 1, 'Maksimal aplikasi aktif per periode (BR-04)'],
        'pmb.allow_multi_path_per_period' => [SettingValueType::Boolean, false, 'Boleh mendaftar lebih dari satu jalur dalam satu periode'],
        'pmb.revision_window_hours' => [SettingValueType::Integer, 72, 'Batas perbaikan dokumen dalam jam (BR-24)'],
        'pmb.re_registration_extension_max_days' => [SettingValueType::Integer, 7, 'Perpanjangan daftar ulang maksimal (hari, BR-34)'],
        'pmb.enrollment_min_payment' => [SettingValueType::String, 'first_installment', 'Minimum pembayaran biaya awal: full / first_installment (BR-33)'],
        'pmb.retention_years_applicant' => [SettingValueType::Integer, 2, 'Masa retensi data pendaftar yang tidak menjadi mahasiswa (tahun, BR-41)'],
        'pmb.nim_format' => [SettingValueType::String, '{YY}{PRODI}{SEQ:4}', 'Format NIM (BR-36)'],
        'pmb.verification_requires_payment' => [SettingValueType::Boolean, true, 'Antrean verifikasi hanya untuk aplikasi yang sudah membayar'],
        'pmb.otp_channel' => [SettingValueType::String, 'email', 'Kanal verifikasi akun: email / sms / whatsapp'],
        'pmb.payment.channels' => [SettingValueType::Json, [
            ['code' => 'manual_transfer', 'label' => 'Transfer Bank (unggah bukti)', 'provider' => 'manual', 'admin_fee' => '0.00', 'fee_borne_by' => 'campus', 'sort' => 90, 'is_active' => true],
            ['code' => 'cashier', 'label' => 'Loket Kampus (tunai)', 'provider' => 'cashier', 'admin_fee' => '0.00', 'fee_borne_by' => 'campus', 'sort' => 99, 'is_active' => true],
        ], 'Kanal pembayaran aktif (BR-17)'],
        'pmb.payment.bank_accounts' => [SettingValueType::Json, [], 'Rekening kampus untuk transfer manual'],
        'pmb.payment.unique_code_enabled' => [SettingValueType::Boolean, true, 'Kode unik 3 digit untuk transfer manual'],
        'pmb.payment.transaction_ttl_hours' => [SettingValueType::Integer, 24, 'Masa berlaku instruksi pembayaran (jam)'],
        'pmb.collect_gender' => [SettingValueType::Boolean, true, 'Kumpulkan jenis kelamin saat pendaftaran'],
        'pmb.min_age' => [SettingValueType::Integer, 14, 'Usia minimal pendaftar'],
        'pmb.max_age' => [SettingValueType::Integer, 70, 'Usia maksimal pendaftar'],
        'pmb.verification_mode' => [SettingValueType::String, 'pull', 'Mode penugasan verifikasi: pull / push'],
        'pmb.verification_claim_minutes' => [SettingValueType::Integer, 30, 'Lama klaim aplikasi oleh pemeriksa (menit)'],
        'pmb.tie_break_use_verified_at' => [SettingValueType::Boolean, true, 'Pakai waktu verifikasi sebagai tie-break (BR-29 langkah 3)'],
        'pmb.max_reschedules' => [SettingValueType::Integer, 1, 'Maksimal penjadwalan ulang ujian per peserta'],
        'pmb.show_quota_status' => [SettingValueType::Boolean, false, 'Tampilkan status kuota (tersedia/terbatas/penuh) di website'],
        'pmb.institution_email_domain' => [SettingValueType::String, '', 'Domain email institusi untuk akun mahasiswa (BR-38)'],
        'pmb.contact' => [SettingValueType::Json, ['address' => '', 'phone' => '', 'email' => '', 'whatsapp' => '', 'hours' => '', 'map_url' => ''], 'Kontak panitia PMB'],
        'pmb.auto_promote_waitlist' => [SettingValueType::Boolean, false, 'Promosi cadangan otomatis saat kursi kosong (BR-32 Phase 2)'],
        'pmb.waitlist_deadline_days' => [SettingValueType::Integer, 7, 'Batas daftar ulang individual hasil promosi cadangan (hari)'],
        'pmb.cbt.provider' => [SettingValueType::String, 'internal', 'Sumber CBT: internal / external'],
        'pmb.sms.enabled' => [SettingValueType::Boolean, false, 'Kirim notifikasi SMS/WA (butuh penyedia)'],
        'pmb.quiet_hours' => [SettingValueType::Json, ['start' => '21:00', 'end' => '06:00'], 'Jam tenang SMS/WA prioritas normal'],
        'pmb.status_labels' => [SettingValueType::Json, [], 'Label tampilan status untuk pendaftar (override)'],
    ];

    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly TenantContext $tenant) {}

    public function get(string $key): mixed
    {
        $universityId = $this->tenant->universityId();
        $cacheKey = ($universityId ?? '-').'|'.$key;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $definition = self::DEFINITIONS[$key] ?? null;
        $default = $definition[1] ?? null;

        if ($universityId === null) {
            return $this->cache[$cacheKey] = $default;
        }

        $row = UniversitySetting::query()->where('university_id', $universityId)->where('key', $key)->first();

        return $this->cache[$cacheKey] = $row ? $row->castedValue() : $default;
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $values = [];

        foreach (array_keys(self::DEFINITIONS) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values, ?string $userId): void
    {
        $universityId = $this->tenant->universityId();

        DB::transaction(function () use ($values, $universityId, $userId): void {
            foreach ($values as $key => $value) {
                [$type, , $description] = self::DEFINITIONS[$key];
                $stored = match ($type) {
                    SettingValueType::Json => json_encode($value, JSON_UNESCAPED_UNICODE),
                    SettingValueType::Boolean => $value ? 'true' : 'false',
                    default => (string) $value,
                };

                UniversitySetting::query()->updateOrCreate(
                    ['university_id' => $universityId, 'key' => $key],
                    ['value' => $stored, 'type' => $type, 'group' => 'pmb', 'description' => $description, 'is_public' => false, 'updated_by' => $userId],
                );
            }
        });

        $this->cache = [];
    }
}

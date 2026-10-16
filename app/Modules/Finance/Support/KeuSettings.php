<?php

namespace Modules\Finance\Support;

use App\Support\Tenancy\TenantContext;
use Modules\SystemSetting\Enums\SettingValueType;
use Modules\Tenancy\Models\UniversitySetting;

/**
 * Pengaturan KEU per tenant (`university_settings` grup `keu`, rancangan
 * KEU §9.5) dengan default rancangan bila tenant belum mengatur. Kunci di
 * luar daftar ditolak saat disimpan.
 */
class KeuSettings
{
    /** @var array<string, array{0: SettingValueType, 1: mixed, 2: string}> */
    public const DEFINITIONS = [
        'keu.rate_lock_mode' => [SettingValueType::String, 'cohort', 'Mode tarif: cohort (terkunci angkatan) / current (tabel tahun berjalan) — BR-KEU-03'],
        'keu.rounding.unit' => [SettingValueType::Integer, 1, 'Pembulatan nominal per baris (rupiah) — BR-KEU-71'],
        'keu.billing.eligible_statuses' => [SettingValueType::Json, ['active'], 'Status mahasiswa yang menerima tagihan wajib — BR-KEU-06'],
        'keu.credit_fee.auto_bill_on_krs_approval' => [SettingValueType::Boolean, true, 'Terbitkan tagihan SKS otomatis saat KRS disetujui — BR-KEU-10'],
        'keu.credit_fee.due_days' => [SettingValueType::Integer, 21, 'Jatuh tempo tagihan SKS (hari setelah terbit)'],
        'keu.credit_fee.drop_credit_deadline' => [SettingValueType::String, '', 'Batas tanggal pengurangan SKS masih dikreditkan (YYYY-MM-DD, kosong = selalu) — BR-KEU-11'],
        'keu.discount.stacking_mode' => [SettingValueType::String, 'sequential', 'Penerapan potongan: sequential / parallel — BR-KEU-21'],
        'keu.overdue.grace_days' => [SettingValueType::Integer, 3, 'Masa tenggang setelah jatuh tempo sebelum dihitung tunggakan'],
        'keu.manual_transfer.unique_code' => [SettingValueType::Boolean, true, 'Tambahkan kode unik 3 digit pada transfer manual'],
        'keu.bank_accounts' => [SettingValueType::Json, [], 'Rekening penerimaan kampus untuk transfer manual'],
        'keu.payment.pending_ttl_minutes' => [SettingValueType::Integer, 1440, 'Masa berlaku instruksi bayar online bila kanal tidak menentukan (menit)'],
        'keu.cash.max_per_transaction' => [SettingValueType::String, '0', 'Batas nominal tunai per transaksi (0 = tanpa batas)'],
        'keu.dispensation.staff_max_days' => [SettingValueType::Integer, 7, 'Dispensasi yang dapat disetujui petugas tanpa persetujuan pejabat (hari) — BR-KEU-67'],
        'keu.dispensation.max_days' => [SettingValueType::Integer, 30, 'Lama dispensasi maksimal (hari)'],
        'keu.clearance.pending_proof_grace_hours' => [SettingValueType::Integer, 0, 'Bukti transfer menunggu verifikasi dianggap memenuhi cekal selama X jam (0 = tidak) — BR-KEU-65'],
        'keu.penalty.due_days' => [SettingValueType::Integer, 7, 'Jatuh tempo tagihan denda (hari)'],
        'keu.voucher.max_failed_attempts' => [SettingValueType::Integer, 5, 'Batas kode voucher salah sebelum diblokir 30 menit — BR-KEU-29'],
        'keu.reminder.offsets_days' => [SettingValueType::Json, [-7, -3, -1, 1, 7], 'Jadwal pengingat jatuh tempo (hari relatif jatuh tempo)'],
        'keu.payment_channels.limit_override' => [SettingValueType::Integer, 0, 'Penimpaan kuota kanal online oleh Super Admin (0 = ikuti paket)'],
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

        $default = self::DEFINITIONS[$key][1] ?? null;

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

    public function set(string $key, mixed $value, ?string $userId = null): void
    {
        [$type, , $description] = self::DEFINITIONS[$key];
        $stored = match ($type) {
            SettingValueType::Json => json_encode($value),
            SettingValueType::Boolean => $value ? 'true' : 'false',
            default => (string) $value,
        };

        UniversitySetting::query()->updateOrCreate(
            ['university_id' => $this->tenant->universityId(), 'key' => $key],
            ['value' => $stored, 'type' => $type, 'group' => 'keu', 'description' => $description, 'is_public' => false, 'updated_by' => $userId],
        );

        unset($this->cache[($this->tenant->universityId() ?? '-').'|'.$key]);
    }

    /** @return array<int, array{key: string, value: mixed, type: string, description: string}> */
    public function all(): array
    {
        return array_map(fn (string $key) => [
            'key' => $key,
            'value' => $this->get($key),
            'type' => self::DEFINITIONS[$key][0]->value,
            'description' => self::DEFINITIONS[$key][2],
        ], array_keys(self::DEFINITIONS));
    }
}

<?php

namespace Modules\Academic\Enums;

/**
 * Draft & Pending hanya muncul dari KRS mandiri mahasiswa (KrsPlanService):
 * draft = masih rencana, pending = sudah diajukan & menunggu dosen wali.
 * Hanya Enrolled yang dianggap peserta kelas sungguhan — ujian, absensi,
 * nilai, dan IP/IPK tetap memfilter Enrolled saja.
 */
enum KrsItemStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Enrolled = 'enrolled';
    case Dropped = 'dropped';

    /**
     * Status yang menempati kursi kelas & dihitung ke beban SKS semester —
     * kursi sudah "dipegang" sejak mahasiswa memilih kelas (draft), bukan
     * baru saat disetujui, supaya kuota yang terlihat saat memilih tidak
     * berubah diam-diam sebelum pengajuan.
     *
     * @return array<int, self>
     */
    public static function seatHolding(): array
    {
        return [self::Draft, self::Pending, self::Enrolled];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Menunggu Persetujuan',
            self::Enrolled => 'Terdaftar',
            self::Dropped => 'Dibatalkan',
        };
    }
}

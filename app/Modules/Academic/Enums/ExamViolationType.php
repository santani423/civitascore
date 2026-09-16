<?php

namespace Modules\Academic\Enums;

/**
 * Jenis pelanggaran yang bisa dideteksi & dilaporkan dari implementasi
 * anti-cheat browser yang sudah ada (PortalExamTakingPage/ExamPublicAttemptPage)
 * — lihat catatan di komponen itu soal keterbatasan deteksi sisi klien.
 * WINDOW_FOCUS_LOST sengaja tidak dipisah dari WindowBlur: browser tidak
 * membedakan keduanya secara andal lewat event yang sama (`blur`).
 */
enum ExamViolationType: string
{
    case TabSwitch = 'tab_switch';
    case WindowBlur = 'window_blur';
    case FullscreenExit = 'fullscreen_exit';
    case CopyAttempt = 'copy_attempt';
    case PasteAttempt = 'paste_attempt';
    case ContextMenu = 'context_menu';

    /** Setiap pelanggaran = -1 poin (spec §4), seragam untuk semua jenis. */
    public function penaltyPoints(): float
    {
        return 1.0;
    }

    public function label(): string
    {
        return match ($this) {
            self::TabSwitch => 'Berpindah Tab/Aplikasi',
            self::WindowBlur => 'Jendela Kehilangan Fokus',
            self::FullscreenExit => 'Keluar dari Layar Penuh',
            self::CopyAttempt => 'Mencoba Menyalin (Copy)',
            self::PasteAttempt => 'Mencoba Menempel (Paste)',
            self::ContextMenu => 'Membuka Klik Kanan',
        };
    }
}

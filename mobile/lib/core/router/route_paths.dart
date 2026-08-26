/// Padanan `constants/routes.ts` (`ROUTES`) — path identik dengan React
/// agar App Link/deep link (BAB 8.8) bisa memakai skema yang sama.
///
/// Fase 0 hanya mendaftarkan route publik + dashboard placeholder di
/// [core/router/app_router.dart]; sisanya didaftarkan bertahap per modul
/// (BAB 12) tapi konstantanya didefinisikan di muka agar tidak perlu diubah
/// tiap kali modul baru ditambahkan.
abstract final class RoutePaths {
  static const login = '/login';
  static const forgotPassword = '/forgot-password';
  static const resetPassword = '/reset-password';
  static const dashboard = '/dashboard';
  static const pengaturanKeamanan = '/pengaturan/keamanan';

  static const persetujuan = '/persetujuan';
  static const persetujuanDetail = '/persetujuan/:id';
  static const persetujuanWorkflow = '/persetujuan/alur';

  static const akademikKurikulum = '/akademik/kurikulum';
  static const akademikKurikulumDetail = '/akademik/kurikulum/:id';
  static const akademikMataKuliah = '/akademik/mata-kuliah';
  static const akademikMataKuliahDetail = '/akademik/mata-kuliah/:id';
  static const akademikKelasJadwal = '/akademik/kelas-jadwal';
  static const akademikKelasJadwalDetail = '/akademik/kelas-jadwal/:id';
  static const akademikProgramStudi = '/akademik/program-studi';
  static const akademikProgramStudiDetail = '/akademik/program-studi/:id';
  static const akademikKrs = '/akademik/krs';
  static const akademikAbsensi = '/akademik/absensi';
  static const akademikPenilaian = '/akademik/penilaian';
  static const akademikUjian = '/akademik/ujian';
  static const akademikUjianBaru = '/akademik/ujian/baru';
  static const akademikUjianEdit = '/akademik/ujian/:id/edit';
  static const akademikUjianSoal = '/akademik/ujian/:id/soal';

  static const mahasiswa = '/mahasiswa';
  static const mahasiswaDetail = '/mahasiswa/:id';
  static const dosen = '/dosen';
  static const dosenDetail = '/dosen/:id';
  static const pegawai = '/pegawai';
  static const pegawaiDetail = '/pegawai/:id';

  static const keuanganTagihan = '/keuangan/tagihan';
  static const keuanganTagihanDetail = '/keuangan/tagihan/:id';
  static const keuanganPembayaran = '/keuangan/pembayaran';
  static const keuanganBeasiswa = '/keuangan/beasiswa';
  static const keuanganBeasiswaDetail = '/keuangan/beasiswa/:id';

  static const skripsi = '/skripsi';
  static const skripsiDetail = '/skripsi/:id';
  static const magangMbkm = '/magang-mbkm';
  static const magangMbkmDetail = '/magang-mbkm/:id';
  static const perpustakaan = '/perpustakaan';
  static const perpustakaanDetail = '/perpustakaan/:id';
  static const alumni = '/alumni';
  static const alumniDetail = '/alumni/:id';
  static const pengumuman = '/pengumuman';
  static const pengumumanDetail = '/pengumuman/:id';
  static const laporan = '/laporan';

  static const pengaturanRoles = '/pengaturan/roles';
  static const pengaturanPermissions = '/pengaturan/permissions';
  static const pengaturanUserRoles = '/pengaturan/user-roles';
  static const pengaturanSystemSettings = '/pengaturan/system-settings';
  static const pengaturanFeatureFlags = '/pengaturan/feature-flags';
  static const pengaturanNotifikasi = '/pengaturan/notifikasi';
  static const pengaturanAuditLog = '/pengaturan/audit-log';

  static const platformUniversitas = '/platform/universitas';
  static const platformKeamanan = '/platform/keamanan';

  static const portalProfil = '/portal/profil';
  static const portalKrs = '/portal/krs';
  static const portalJadwal = '/portal/jadwal-kuliah';
  static const portalKhs = '/portal/khs';
  static const portalTranskrip = '/portal/transkrip';
  static const portalNilai = '/portal/nilai';
  static const portalAbsensi = '/portal/absensi';
  static const portalTugas = '/portal/tugas';
  static const portalKuis = '/portal/kuis';
  static const portalCuti = '/portal/cuti';
  static const portalSurat = '/portal/surat';
  static const portalBeasiswa = '/portal/beasiswa';
  static const portalTagihan = '/portal/tagihan';
  static const portalBimbinganAkademik = '/portal/bimbingan-akademik';
  static const portalBimbinganSkripsi = '/portal/bimbingan-skripsi';
  static const portalPengumuman = '/portal/pengumuman';
  static const portalEvaluasiDosen = '/portal/evaluasi-dosen';
  static const portalWisuda = '/portal/wisuda';
}

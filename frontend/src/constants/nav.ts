import {
  LayoutDashboard,
  GraduationCap,
  Users,
  UserRound,
  Briefcase,
  Wallet,
  ScrollText,
  Handshake,
  Library,
  UserCheck,
  Megaphone,
  BarChart3,
  Settings,
  CheckSquare,
  Building2,
  ShieldAlert,
  UserCircle,
  BookOpen,
  FileText,
  ClipboardCheck,
  Award,
} from 'lucide-react'
import type { NavItem } from '@/types/navigation'
import { ROUTES } from '@/constants/routes'

const DASHBOARD_ITEM: NavItem = { label: 'Dashboard', path: ROUTES.dashboard, icon: LayoutDashboard }

/**
 * `permission` di tiap child mengikuti persis gate baca yang backend pakai
 * (route middleware `permission:...` / Policy `viewAny`) — lihat
 * app/Modules/*\/Routes/api.php dan Policies terkait. "Keamanan" sengaja
 * tanpa `permission`: /sessions dan /devices cuma butuh login, tidak ada
 * permission slug (self-service, bukan admin panel).
 */
const PERSETUJUAN_ITEM: NavItem = {
  label: 'Persetujuan',
  path: ROUTES.persetujuan,
  icon: CheckSquare,
  children: [
    { label: 'Pengajuan', path: ROUTES.persetujuan, permission: 'approval_requests.read' },
    { label: 'Alur Persetujuan', path: ROUTES.persetujuanWorkflow, permission: 'approval_workflows.read' },
  ],
}

const PENGATURAN_ITEM: NavItem = {
  label: 'Pengaturan',
  path: ROUTES.pengaturan.systemSettings,
  icon: Settings,
  children: [
    { label: 'Role', path: ROUTES.pengaturan.roles, permission: 'roles.read' },
    { label: 'Permission', path: ROUTES.pengaturan.permissions, permission: 'permissions.read' },
    { label: 'Role Pengguna', path: ROUTES.pengaturan.userRoles, permission: 'user_roles.read' },
    { label: 'Pengaturan Sistem', path: ROUTES.pengaturan.systemSettings, permission: 'system_settings.read' },
    { label: 'Feature Flag', path: ROUTES.pengaturan.featureFlags, permission: 'feature_flags.read' },
    { label: 'Notifikasi', path: ROUTES.pengaturan.notifications, permission: 'notification_templates.read' },
    { label: 'Audit Log', path: ROUTES.pengaturan.auditLog, permission: 'audit_logs.read' },
    { label: 'Keamanan', path: ROUTES.pengaturan.security },
  ],
}

/**
 * Menu operasional satu universitas — data bisnis yang menurut
 * docs/RANCANGAN-SUPER-ADMIN.md §1/§8 wajib "pilih universitas dulu".
 * Dipakai langsung oleh NAV_ITEMS (pengguna tenant biasa, selalu di tenant
 * sendiri) dan disisipkan ke menu Super Admin setelah Tenant Switcher aktif.
 */
export const TENANT_BUSINESS_NAV_ITEMS: NavItem[] = [
  {
    label: 'Akademik',
    path: ROUTES.akademik.krs,
    icon: GraduationCap,
    children: [
      { label: 'Program Studi', path: ROUTES.akademik.programStudi, permission: 'study_programs.read' },
      { label: 'Kurikulum', path: ROUTES.akademik.kurikulum, permission: 'curriculums.read' },
      { label: 'Mata Kuliah', path: ROUTES.akademik.mataKuliah, permission: 'courses.read' },
      { label: 'Kelas dan Jadwal', path: ROUTES.akademik.kelasJadwal, permission: 'classes.read' },
      { label: 'KRS', path: ROUTES.akademik.krs, permission: 'krs.read' },
      { label: 'Absensi', path: ROUTES.akademik.absensi, permission: 'attendance.read' },
      { label: 'Penilaian', path: ROUTES.akademik.penilaian, permission: 'grades.read' },
      { label: 'Ujian', path: ROUTES.akademik.ujian, permission: 'exams.read' },
      { label: 'Bank Soal', path: ROUTES.akademik.bankSoal, permission: 'question_bank.read' },
    ],
  },
  { label: 'Mahasiswa', path: ROUTES.mahasiswa, icon: Users, permission: 'students.read' },
  { label: 'Dosen', path: ROUTES.dosen, icon: UserRound, permission: 'lecturers.read' },
  { label: 'Pegawai', path: ROUTES.pegawai, icon: Briefcase, permission: 'employees.read' },
  {
    label: 'Keuangan',
    path: ROUTES.keuangan.tagihan,
    icon: Wallet,
    children: [
      { label: 'Tagihan', path: ROUTES.keuangan.tagihan, permission: 'invoices.read' },
      { label: 'Pembayaran', path: ROUTES.keuangan.pembayaran, permission: 'invoices.read' },
      { label: 'Beasiswa', path: ROUTES.keuangan.beasiswa, permission: 'scholarships.read' },
    ],
  },
  { label: 'Skripsi', path: ROUTES.skripsi, icon: ScrollText, permission: 'theses.read' },
  { label: 'Magang dan MBKM', path: ROUTES.magangMbkm, icon: Handshake, permission: 'internships.read' },
  { label: 'Perpustakaan', path: ROUTES.perpustakaan, icon: Library, permission: 'books.read' },
  { label: 'Alumni', path: ROUTES.alumni, icon: UserCheck, permission: 'alumni.read' },
  { label: 'Pengumuman', path: ROUTES.pengumuman, icon: Megaphone, permission: 'announcements.read' },
  { label: 'Laporan', path: ROUTES.laporan, icon: BarChart3, permission: 'reports.read' },
]

/** Menu pengguna tenant biasa (admin universitas, dosen, mahasiswa, dst) — tidak berubah, selalu di tenant sendiri. */
export const NAV_ITEMS: NavItem[] = [DASHBOARD_ITEM, ...TENANT_BUSINESS_NAV_ITEMS, PERSETUJUAN_ITEM, PENGATURAN_ITEM]

/**
 * Menu inti Super Admin — platform (lintas-universitas) + aktivitas yang
 * memang berlaku global. TENANT_BUSINESS_NAV_ITEMS di atas TIDAK termasuk
 * di sini; Sidebar menyisipkannya sendiri begitu Tenant Switcher aktif
 * (lihat docs/RANCANGAN-SUPER-ADMIN.md §1-2).
 */
export const PLATFORM_NAV_ITEMS: NavItem[] = [
  DASHBOARD_ITEM,
  { label: 'Manajemen Universitas', path: ROUTES.platform.universities, icon: Building2, permission: 'platform_universities.read' },
  { label: 'Keamanan Platform', path: ROUTES.platform.security, icon: ShieldAlert, permission: 'support_sessions.read' },
  PERSETUJUAN_ITEM,
  PENGATURAN_ITEM,
]

/**
 * Menu Portal Mahasiswa — data serba "milik saya sendiri" (KRS, nilai,
 * tugas, tagihan, dst), bukan resource admin, jadi sengaja TIDAK dipasangi
 * `permission` seperti TENANT_BUSINESS_NAV_ITEMS: tidak ada permission
 * slug admin yang relevan untuk "lihat KRS saya sendiri" — batasnya adalah
 * kepemilikan data (scoped ke user login), bukan RBAC. Ditampilkan
 * menggantikan NAV_ITEMS saat role aktif pengguna adalah mahasiswa (lihat
 * Sidebar.tsx + useIsStudent()). Fase ini murni UI — halaman-halamannya
 * belum tersambung ke API sungguhan (lihat masing-masing page).
 */
export const PORTAL_NAV_ITEMS: NavItem[] = [
  DASHBOARD_ITEM,
  { label: 'Profil Saya', path: ROUTES.portal.profil, icon: UserCircle },
  {
    label: 'Akademik',
    path: ROUTES.portal.krs,
    icon: GraduationCap,
    children: [
      { label: 'KRS', path: ROUTES.portal.krs },
      { label: 'Jadwal Kuliah', path: ROUTES.portal.jadwal },
      { label: 'KHS', path: ROUTES.portal.khs },
      { label: 'Transkrip Sementara', path: ROUTES.portal.transkrip },
      { label: 'Nilai', path: ROUTES.portal.nilai },
      { label: 'Absensi', path: ROUTES.portal.absensi },
    ],
  },
  {
    label: 'Perkuliahan',
    path: ROUTES.portal.tugas,
    icon: BookOpen,
    children: [
      { label: 'Tugas', path: ROUTES.portal.tugas },
      { label: 'Kuis', path: ROUTES.portal.kuis },
    ],
  },
  {
    label: 'Pengajuan',
    path: ROUTES.portal.cuti,
    icon: FileText,
    children: [
      { label: 'Cuti', path: ROUTES.portal.cuti },
      { label: 'Surat', path: ROUTES.portal.surat },
      { label: 'Beasiswa', path: ROUTES.portal.beasiswa },
    ],
  },
  { label: 'Tagihan', path: ROUTES.portal.tagihan, icon: Wallet },
  {
    label: 'Bimbingan',
    path: ROUTES.portal.bimbinganAkademik,
    icon: Users,
    children: [
      { label: 'Bimbingan Akademik', path: ROUTES.portal.bimbinganAkademik },
      { label: 'Bimbingan Skripsi', path: ROUTES.portal.bimbinganSkripsi },
    ],
  },
  { label: 'Pengumuman', path: ROUTES.portal.pengumuman, icon: Megaphone },
  { label: 'Evaluasi Dosen', path: ROUTES.portal.evaluasiDosen, icon: ClipboardCheck },
  { label: 'Pendaftaran Wisuda', path: ROUTES.portal.wisuda, icon: Award },
]

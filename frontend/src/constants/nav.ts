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
  CalendarDays,
  Bell,
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
      { label: 'Kelas Saya', path: ROUTES.akademik.kelasSaya, permission: ['course_materials.read', 'assignments.read'] },
      { label: 'Persetujuan KRS', path: ROUTES.akademik.persetujuanKrs, permission: ['krs.approve', 'krs_advising.read'] },
      { label: 'Pengajuan Mahasiswa', path: ROUTES.akademik.pengajuanMahasiswa, permission: 'approval_requests.read' },
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
 * Menu Portal Mahasiswa (RANCANGAN-AKUN-MAHASISWA.md) — data serba "milik
 * saya sendiri". `permission` di sini adalah slug layanan mandiri mahasiswa
 * (student_portal.*, krs_self_service.*, student_requests.*,
 * exam_participation.*), bukan permission admin: backend selalu
 * meresolusi data dari akun yang login, permission hanya menentukan
 * layanan mana yang aktif untuk role tersebut. Ditampilkan menggantikan
 * NAV_ITEMS saat role pengguna adalah mahasiswa (Sidebar.tsx + useIsStudent()).
 */
export const PORTAL_NAV_ITEMS: NavItem[] = [
  DASHBOARD_ITEM,
  { label: 'Profil', path: ROUTES.portal.profil, icon: UserCircle, permission: 'student_portal.read' },
  {
    label: 'Akademik',
    path: ROUTES.portal.akademik,
    icon: GraduationCap,
    children: [
      { label: 'Akademik Saya', path: ROUTES.portal.akademik, permission: 'student_portal.read' },
      { label: 'KRS', path: ROUTES.portal.krs, permission: 'krs_self_service.read' },
      { label: 'Jadwal', path: ROUTES.portal.jadwal, permission: 'student_portal.read' },
      { label: 'KHS', path: ROUTES.portal.khs, permission: 'student_portal.read' },
      { label: 'Nilai', path: ROUTES.portal.nilai, permission: 'student_portal.read' },
      { label: 'Transkrip', path: ROUTES.portal.transkrip, permission: 'student_portal.read' },
      { label: 'Presensi', path: ROUTES.portal.absensi, permission: 'student_portal.read' },
      { label: 'Dokumen', path: ROUTES.portal.dokumen, permission: 'student_portal.read' },
    ],
  },
  {
    label: 'Perkuliahan',
    path: ROUTES.portal.mataKuliah,
    icon: BookOpen,
    children: [
      { label: 'Mata Kuliah', path: ROUTES.portal.mataKuliah, permission: 'student_portal.read' },
      { label: 'Materi', path: ROUTES.portal.materi, permission: 'student_portal.read' },
      { label: 'Tugas', path: ROUTES.portal.tugas, permission: 'student_portal.read' },
    ],
  },
  { label: 'Ujian', path: ROUTES.portal.ujian, icon: ClipboardCheck, permission: 'exam_participation.read' },
  { label: 'Kalender', path: ROUTES.portal.kalender, icon: CalendarDays, permission: 'student_portal.read' },
  { label: 'Pengajuan', path: ROUTES.portal.pengajuan, icon: FileText, permission: 'student_requests.read' },
  { label: 'Pengumuman', path: ROUTES.portal.pengumuman, icon: Megaphone, permission: 'student_portal.read' },
  { label: 'Notifikasi', path: ROUTES.portal.notifikasi, icon: Bell },
  { label: 'Pengaturan', path: ROUTES.portal.pengaturan, icon: Settings },
]

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
} from 'lucide-react'
import type { NavItem } from '@/types/navigation'
import { ROUTES } from '@/constants/routes'

export const NAV_ITEMS: NavItem[] = [
  { label: 'Dashboard', path: ROUTES.dashboard, icon: LayoutDashboard },
  {
    label: 'Akademik',
    path: ROUTES.akademik.krs,
    icon: GraduationCap,
    children: [
      { label: 'Kurikulum', path: ROUTES.akademik.kurikulum },
      { label: 'Mata Kuliah', path: ROUTES.akademik.mataKuliah },
      { label: 'Kelas dan Jadwal', path: ROUTES.akademik.kelasJadwal },
      { label: 'KRS', path: ROUTES.akademik.krs },
      { label: 'Absensi', path: ROUTES.akademik.absensi },
      { label: 'Penilaian', path: ROUTES.akademik.penilaian },
    ],
  },
  { label: 'Mahasiswa', path: ROUTES.mahasiswa, icon: Users },
  { label: 'Dosen', path: ROUTES.dosen, icon: UserRound },
  { label: 'Pegawai', path: ROUTES.pegawai, icon: Briefcase },
  {
    label: 'Keuangan',
    path: ROUTES.keuangan.tagihan,
    icon: Wallet,
    children: [
      { label: 'Tagihan', path: ROUTES.keuangan.tagihan },
      { label: 'Beasiswa', path: ROUTES.keuangan.beasiswa },
    ],
  },
  { label: 'Skripsi', path: ROUTES.skripsi, icon: ScrollText },
  { label: 'Magang dan MBKM', path: ROUTES.magangMbkm, icon: Handshake },
  { label: 'Perpustakaan', path: ROUTES.perpustakaan, icon: Library },
  { label: 'Alumni', path: ROUTES.alumni, icon: UserCheck },
  { label: 'Pengumuman', path: ROUTES.pengumuman, icon: Megaphone },
  { label: 'Laporan', path: ROUTES.laporan, icon: BarChart3 },
  {
    label: 'Persetujuan',
    path: ROUTES.persetujuan,
    icon: CheckSquare,
    children: [
      { label: 'Pengajuan', path: ROUTES.persetujuan },
      { label: 'Alur Persetujuan', path: ROUTES.persetujuanWorkflow },
    ],
  },
  {
    label: 'Pengaturan',
    path: ROUTES.pengaturan.systemSettings,
    icon: Settings,
    children: [
      { label: 'Role', path: ROUTES.pengaturan.roles },
      { label: 'Permission', path: ROUTES.pengaturan.permissions },
      { label: 'Role Pengguna', path: ROUTES.pengaturan.userRoles },
      { label: 'Pengaturan Sistem', path: ROUTES.pengaturan.systemSettings },
      { label: 'Feature Flag', path: ROUTES.pengaturan.featureFlags },
      { label: 'Notifikasi', path: ROUTES.pengaturan.notifications },
      { label: 'Audit Log', path: ROUTES.pengaturan.auditLog },
      { label: 'Keamanan', path: ROUTES.pengaturan.security },
    ],
  },
]

/** Resolves the current route's label for the header/breadcrumb — checks children first (more specific). */
export function getPageTitle(pathname: string): string {
  for (const item of NAV_ITEMS) {
    const child = item.children?.find((entry) => entry.path === pathname)
    if (child) return child.label
    if (item.path === pathname) return item.label
  }

  return 'Halaman'
}

import { Users, GraduationCap, Briefcase, Building2, UserCheck, BookOpen, Wallet, ClipboardList } from 'lucide-react'
import type {
  SummaryCardData,
  YearlyStudentCount,
  FacultyDistribution,
  PaymentStatusSlice,
  AttendanceTrendPoint,
  StudentStatusSlice,
} from '@/types/dashboard'

/**
 * Shaped to resemble the eventual API response so swapping this for a real
 * fetch later only means changing the data source, not the consuming
 * component.
 */
export const SUMMARY_CARDS: SummaryCardData[] = [
  {
    id: 'total-students',
    label: 'Total Mahasiswa',
    value: '12.480',
    changePercent: 4.2,
    trend: 'up',
    caption: 'dibanding semester lalu',
    icon: Users,
  },
  {
    id: 'total-lecturers',
    label: 'Total Dosen',
    value: '642',
    changePercent: 1.1,
    trend: 'up',
    caption: 'dibanding semester lalu',
    icon: GraduationCap,
  },
  {
    id: 'total-employees',
    label: 'Total Pegawai',
    value: '318',
    changePercent: 0,
    trend: 'flat',
    caption: 'tidak ada perubahan',
    icon: Briefcase,
  },
  {
    id: 'total-study-programs',
    label: 'Program Studi',
    value: '38',
    changePercent: 0,
    trend: 'flat',
    caption: 'di 9 fakultas',
    icon: Building2,
  },
  {
    id: 'active-students',
    label: 'Mahasiswa Aktif',
    value: '11.902',
    changePercent: 2.6,
    trend: 'up',
    caption: 'dari total mahasiswa',
    icon: UserCheck,
  },
  {
    id: 'active-classes',
    label: 'Kelas Aktif',
    value: '864',
    changePercent: 3.4,
    trend: 'up',
    caption: 'semester ganjil 2026/2027',
    icon: BookOpen,
  },
  {
    id: 'unpaid-bills',
    label: 'Tagihan Belum Dibayar',
    value: '1.246',
    changePercent: -8.3,
    trend: 'down',
    caption: 'dibanding bulan lalu',
    icon: Wallet,
  },
  {
    id: 'pending-approvals',
    label: 'Menunggu Persetujuan',
    value: '57',
    changePercent: 12.5,
    trend: 'up',
    caption: 'butuh tindakan Anda',
    icon: ClipboardList,
  },
]

export const YEARLY_STUDENT_COUNTS: YearlyStudentCount[] = [
  { year: '2022', students: 9840 },
  { year: '2023', students: 10520 },
  { year: '2024', students: 11180 },
  { year: '2025', students: 11960 },
  { year: '2026', students: 12480 },
]

export const FACULTY_DISTRIBUTION: FacultyDistribution[] = [
  { faculty: 'Ekonomi & Bisnis', students: 2840 },
  { faculty: 'Teknik', students: 2510 },
  { faculty: 'Kedokteran', students: 1680 },
  { faculty: 'Hukum', students: 1420 },
  { faculty: 'Psikologi', students: 1180 },
  { faculty: 'Ilmu Komunikasi', students: 1360 },
  { faculty: 'Lainnya', students: 1490 },
]

export const PAYMENT_STATUS: PaymentStatusSlice[] = [
  { status: 'Lunas', value: 8620 },
  { status: 'Cicilan', value: 2140 },
  { status: 'Belum Dibayar', value: 1246 },
  { status: 'Menunggak', value: 474 },
]

export const ATTENDANCE_TREND: AttendanceTrendPoint[] = [
  { month: 'Feb', present: 92, absent: 8 },
  { month: 'Mar', present: 90, absent: 10 },
  { month: 'Apr', present: 94, absent: 6 },
  { month: 'Mei', present: 91, absent: 9 },
  { month: 'Jun', present: 88, absent: 12 },
  { month: 'Jul', present: 93, absent: 7 },
]

export const STUDENT_STATUS: StudentStatusSlice[] = [
  { status: 'Aktif', value: 11902 },
  { status: 'Cuti', value: 312 },
  { status: 'Nonaktif', value: 148 },
  { status: 'Drop Out', value: 64 },
  { status: 'Lulus (Semester Ini)', value: 54 },
]

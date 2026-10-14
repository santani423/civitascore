import { ClipboardList, Settings2 } from 'lucide-react'
import type { NavItem } from '@/types/navigation'
import { PMB } from '@/pmb/routes'

/**
 * Menu admin PMB (docs/pmb/12 §2.2). Permission tiap item = gate baca
 * endpoint utama halamannya (Modules/Admission/Routes/api.php), OR-match.
 */
export const PMB_NAV_ITEMS: NavItem[] = [
  {
    label: 'PMB',
    path: PMB.admin.dashboard,
    icon: ClipboardList,
    children: [
      { label: 'Dashboard PMB', path: PMB.admin.dashboard, permission: 'pmb_reports.read' },
      { label: 'Pendaftar', path: PMB.admin.applications, permission: 'pmb_applications.read' },
      { label: 'Verifikasi', path: PMB.admin.verification, permission: ['pmb_verification.approve', 'pmb_verification.update', 'pmb_verification.finalize'] },
      { label: 'Keuangan PMB', path: PMB.admin.finance, permission: 'pmb_payments.read' },
      { label: 'Ujian', path: PMB.admin.exams, permission: 'pmb_exams.update' },
      { label: 'Penilaian Ujian', path: PMB.admin.scoring, permission: ['pmb_scores.create', 'pmb_exams.update'] },
      { label: 'Seleksi', path: PMB.admin.selection, permission: ['pmb_selection.update', 'pmb_results.read', 'pmb_scores.import'] },
      { label: 'Pengumuman', path: PMB.admin.announcements, permission: ['pmb_applications.read', 'pmb_results.read', 'pmb_waitlist.update'] },
      { label: 'Daftar Ulang', path: PMB.admin.reRegistrations, permission: ['pmb_reregistration.read', 'pmb_conversion.read'] },
      { label: 'Komunikasi', path: PMB.admin.communication, permission: ['pmb_communication.update', 'pmb_communication.publish', 'pmb_config.update', 'pmb_audit.read'] },
      { label: 'Laporan PMB', path: PMB.admin.reports, permission: 'pmb_reports.read' },
      { label: 'Audit PMB', path: PMB.admin.audit, permission: 'pmb_audit.read' },
    ],
  },
  {
    label: 'Konfigurasi PMB',
    path: PMB.admin.periods,
    icon: Settings2,
    children: [
      { label: 'Periode & Gelombang', path: PMB.admin.periods, permission: 'pmb_config.read' },
      { label: 'Jalur & Dokumen', path: PMB.admin.master, permission: 'pmb_config.read' },
      { label: 'Template Formulir', path: PMB.admin.forms, permission: 'pmb_config.read' },
      { label: 'Skema Seleksi', path: PMB.admin.schemes, permission: 'pmb_config.read' },
      { label: 'Konten Website', path: PMB.admin.contents, permission: ['pmb_config.read', 'pmb_content.update'] },
      { label: 'Profil Prodi', path: PMB.admin.programProfiles, permission: 'pmb_config.read' },
      { label: 'Pengaturan PMB', path: PMB.admin.settings, permission: 'pmb_config.read' },
    ],
  },
]

/** Rute detail admin PMB mewarisi izin baca halaman induknya. */
export const PMB_DETAIL_ROUTE_PERMISSIONS: Record<string, string | string[]> = {
  [PMB.admin.period]: 'pmb_config.read',
  [PMB.admin.form]: 'pmb_config.read',
  [PMB.admin.scheme]: 'pmb_config.read',
  [PMB.admin.application]: 'pmb_applications.read',
  [PMB.admin.examSchedule]: 'pmb_exams.update',
  [PMB.admin.examSession]: ['pmb_exam_attendance.update', 'pmb_exams.update'],
  [PMB.admin.selectionRun]: 'pmb_results.read',
  [PMB.admin.reRegistration]: 'pmb_reregistration.read',
}

import type { LucideIcon } from 'lucide-react'

export type TrendDirection = 'up' | 'down' | 'flat'

export interface SummaryCardData {
  id: string
  label: string
  value: string
  changePercent: number
  trend: TrendDirection
  caption: string
  icon: LucideIcon
}

export interface StudentGrowthPoint {
  year: number
  total: number
}

export interface StatusCount {
  /** Label tampilan Indonesia (mis. "Aktif"). */
  status: string
  /** Slug enum mentah (mis. "active") — dipakai membangun link filter saat slice diklik. */
  value: string
  total: number
}

export interface ApprovalStatusCount {
  status: string
  /** Satu atau beberapa slug status dipisah koma (mis. "submitted,in_progress") — langsung dipakai sebagai filter[status]. */
  values: string
  total: number
}

export interface ProgramCount {
  /** id StudyProgram — target filter saat bar diklik. */
  id: string
  program: string
  total: number
}

export interface StaffByUnitPoint {
  unit: string
  /** Null kalau baris ini murni unit_kerja pegawai (bukan fakultas) — klik seri dosen tidak bisa link ke faculty_id. */
  faculty_id: string | null
  lecturers: number
  employees: number
}

export interface PaymentTrendPoint {
  /** Format YYYY-MM. */
  month: string
  /** Sum of payments for that month, in Rupiah — backend sends decimal sums as strings. */
  total: string
}

export interface InvoiceStatusSlice {
  status: string
  /** Slug enum mentah (unpaid/partial/paid). */
  value: string
  total: number
  /** Sum of invoice amounts for that status, in Rupiah — backend sends decimal sums as strings. */
  amount: string
}

/** GET /dashboard summary — every field is optional because the backend omits keys the current user isn't permitted to see (see DashboardController::SUMMARY_PERMISSIONS). */
export interface DashboardSummary {
  total_students?: number
  total_lecturers?: number
  total_employees?: number
  total_study_programs?: number
  active_students?: number
  active_classes?: number
  unpaid_invoices?: number
  pending_approvals?: number
}

/** Same "omitted if unauthorized" rule as DashboardSummary — see DashboardController::CHART_PERMISSIONS. */
export interface DashboardCharts {
  student_growth?: StudentGrowthPoint[]
  student_status?: StatusCount[]
  students_by_program?: ProgramCount[]
  staff_by_unit?: StaffByUnitPoint[]
  payment_trend?: PaymentTrendPoint[]
  invoice_status?: InvoiceStatusSlice[]
  active_classes_by_program?: ProgramCount[]
  approval_status?: ApprovalStatusCount[]
}

export interface DashboardAcademicTermOption {
  id: string
  label: string
  is_current: boolean
}

export interface DashboardFacultyOption {
  id: string
  name: string
}

export interface DashboardStudyProgramOption {
  id: string
  name: string
  faculty_id: string
}

export interface DashboardFilterOptions {
  academic_terms: DashboardAcademicTermOption[]
  faculties: DashboardFacultyOption[]
  study_programs: DashboardStudyProgramOption[]
}

export interface DashboardResponse {
  summary: DashboardSummary
  charts: DashboardCharts
  filters: DashboardFilterOptions
}

export interface DashboardQueryFilters {
  academic_term_id?: string
  faculty_id?: string
  study_program_id?: string
  date_from?: string
  date_to?: string
}

export type ActivityType =
  | 'student_registered'
  | 'krs_submitted'
  | 'grade_published'
  | 'payment_received'
  | 'letter_approved'
  | 'complaint_resolved'

export interface ActivityItem {
  id: string
  type: ActivityType
  title: string
  description: string
  actor: string
  timestamp: string
}

export type AgendaCategory = 'krs' | 'exam' | 'payment' | 'grading' | 'graduation'

export interface AgendaItem {
  id: string
  title: string
  category: AgendaCategory
  startDate: string
  endDate: string
}

export type NotificationType = 'info' | 'success' | 'warning' | 'danger'

export interface NotificationItem {
  id: string
  type: NotificationType
  title: string
  description: string
  isRead: boolean
  timestamp: string
}

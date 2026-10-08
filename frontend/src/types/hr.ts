import type { FileUpload } from '@/types/fileUpload'

/**
 * Tipe Modul SDM — mencerminkan persis resource Laravel di
 * app/Modules/HumanResource/Resources. Setiap enum dikirim backend sebagai
 * `value` + `*_label` (label bahasa Indonesia), jadi frontend tidak
 * menduplikasi peta label.
 */

export type EmployeeType = 'lecturer' | 'staff'
export type EmploymentStatus = 'permanent' | 'contract' | 'honorary' | 'probation' | 'outsourcing'
export type Gender = 'male' | 'female'
export type ContractStatus = 'active' | 'expired' | 'terminated'
export type DocumentStatus = 'pending' | 'valid' | 'invalid'
export type TrainingStatus = 'planned' | 'ongoing' | 'completed' | 'cancelled'
export type PerformanceStatus = 'draft' | 'final'
export type TransferStatus = 'scheduled' | 'applied' | 'cancelled'
export type LeaveStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'cancelled'
export type HrRequestStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'
export type HrRequestType = 'leave' | 'transfer' | 'data_change' | 'promotion' | 'document' | 'other'

export interface HrOption {
  value: string
  label: string
}

export interface HrOptions {
  enums: {
    employee_type: HrOption[]
    employment_status: HrOption[]
    gender: HrOption[]
    education_level: HrOption[]
    staff_category: HrOption[]
    work_unit_type: HrOption[]
    position_type: HrOption[]
    academic_rank: HrOption[]
    lecturer_status: HrOption[]
    lecturer_activity_type: HrOption[]
    contract_type: HrOption[]
    contract_status: HrOption[]
    document_type: HrOption[]
    document_status: HrOption[]
    training_status: HrOption[]
    performance_category: HrOption[]
    performance_status: HrOption[]
    transfer_status: HrOption[]
    leave_type: HrOption[]
    leave_status: HrOption[]
    request_type: HrOption[]
    request_status: HrOption[]
  }
  work_units: HrOption[]
  positions: Array<HrOption & { type: string }>
  ranks: HrOption[]
  faculties: HrOption[]
  study_programs: Array<HrOption & { faculty_id: string }>
}

/** Ringkasan pegawai yang ikut di resource riwayat/administrasi. */
export interface HrEmployeeSummary {
  id: string
  name: string
  nip: string | null
  employee_type: EmployeeType
  employee_type_label: string
  unit_kerja: string
}

export interface HrEmployee {
  id: string
  employee_type: EmployeeType
  employee_type_label: string
  nip: string | null
  nidn?: string | null
  name: string
  email: string | null
  phone: string | null
  unit_kerja: string
  work_unit_id: string | null
  position: string | null
  position_id: string | null
  employment_status: EmploymentStatus
  employment_status_label: string
  highest_education: string | null
  highest_education_label: string | null
  staff_category: string | null
  staff_category_label: string | null
  assigned_facility: string | null
  faculty_id: string | null
  faculty_name?: string | null
  study_program_id: string | null
  study_program_name?: string | null
  academic_rank?: string | null
  academic_rank_label?: string | null
  joined_at: string | null
  is_active: boolean
  has_account: boolean
  created_at: string | null
}

export interface HrLecturerDetail {
  id: string
  nidn: string
  nidk: string | null
  serdos_number: string | null
  academic_rank: string | null
  academic_rank_label: string | null
  expertise: string | null
  lecturer_status: string | null
  lecturer_status_label: string | null
  teaching_started_at: string | null
}

export interface HrEmployeeDetail extends HrEmployee {
  full_name: string
  front_title: string | null
  back_title: string | null
  /** Tersamar (****1234) kecuali untuk pemilik data sendiri — lihat `sensitive_masked`. */
  nik: string | null
  sensitive_masked: boolean
  competency_summary: string | null
  gender: Gender | null
  gender_label: string | null
  birth_place: string | null
  birth_date: string | null
  address: string | null
  rank_id: string | null
  rank?: { id: string; name: string; grade: string } | null
  work_unit_name?: string | null
  inactive_reason: string | null
  inactive_at: string | null
  user?: { id: string; name: string; email: string } | null
  lecturer: HrLecturerDetail | null
  updated_at: string | null
}

export interface HrEmployeePayload {
  employee_type?: EmployeeType
  name: string
  nik?: string | null
  nip?: string | null
  email?: string | null
  gender?: Gender | null
  birth_place?: string | null
  birth_date?: string | null
  phone?: string | null
  address?: string | null
  employment_status: EmploymentStatus
  work_unit_id?: string | null
  position_id?: string | null
  faculty_id?: string | null
  study_program_id?: string | null
  highest_education?: string | null
  staff_category?: string | null
  assigned_facility?: string | null
  competency_summary?: string | null
  front_title?: string | null
  back_title?: string | null
  joined_at?: string | null
  is_active?: boolean
  user_id?: string | null
  nidn?: string | null
  nidk?: string | null
  serdos_number?: string | null
  expertise?: string | null
  lecturer_status?: string | null
  teaching_started_at?: string | null
  academic_rank?: string | null
}

export interface HrUserOption {
  id: string
  name: string
  email: string
}

export interface EmploymentStatusSummary {
  value: EmploymentStatus
  label: string
  lecturers: number
  staff: number
  total: number
}

export interface StaffCountBucket {
  value: string
  label: string
  total: number
}

export interface StaffRatio {
  staff: number
  lecturers: number
  students: number
  students_per_staff: number | null
  lecturers_per_staff: number | null
}

/** GET /hr/staff/summary — rekap Data Tenaga Kependidikan (hanya tendik aktif, kecuali `totals`). */
export interface EducationStaffSummary {
  totals: {
    total: number
    active: number
    inactive: number
    uncategorized: number
  }
  by_category: StaffCountBucket[]
  by_employment_status: StaffCountBucket[]
  by_work_unit: Array<{
    work_unit_id: string | null
    name: string
    total: number
    /** Jumlah per kategori (`uncategorized` untuk yang belum berkategori). */
    categories: Record<string, number>
  }>
  ratios: {
    overall: StaffRatio & { staff_outside_faculty: number }
    by_faculty: Array<StaffRatio & { faculty_id: string; faculty_name: string }>
  }
}

export interface WorkUnit {
  id: string
  parent_id: string | null
  parent_name?: string | null
  faculty_id: string | null
  study_program_id: string | null
  code: string
  name: string
  type: string
  type_label: string
  is_active: boolean
  employees_count?: number
}

export interface Position {
  id: string
  code: string
  name: string
  type: string
  type_label: string
  description: string | null
  is_active: boolean
  employees_count?: number
}

export interface Rank {
  id: string
  name: string
  grade: string
  level: number
  is_active: boolean
  employees_count?: number
}

export interface EmployeeEducation {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  level: string
  level_label: string
  institution: string
  major: string | null
  entry_year: number | null
  graduation_year: number | null
  certificate_number: string | null
  gpa: string | null
  document_file_id: string | null
  document_file?: FileUpload | null
  created_at: string | null
}

export interface EmployeePositionHistory {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  position_id: string | null
  position_name: string
  position_type: string
  position_type_label: string
  work_unit_id: string | null
  work_unit_name: string | null
  start_date: string
  end_date: string | null
  decree_number: string | null
  decree_file_id: string | null
  decree_file?: FileUpload | null
  notes: string | null
  is_current: boolean
}

export interface EmployeeRankHistory {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  rank_id: string | null
  rank_name: string
  grade: string
  decree_number: string | null
  decree_date: string | null
  start_date: string
  end_date: string | null
  decree_file_id: string | null
  decree_file?: FileUpload | null
  is_current: boolean
}

export interface AcademicRankHistory {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  academic_rank: string
  academic_rank_label: string
  credit_points: string | null
  decree_number: string | null
  start_date: string
  end_date: string | null
  decree_file_id: string | null
  decree_file?: FileUpload | null
  is_current: boolean
}

export interface LecturerActivity {
  id: string
  employee_id: string
  type: string
  type_label: string
  title: string
  role: string | null
  year: number
  funding_source: string | null
  amount: string | null
  description: string | null
  document_file_id: string | null
  document_file?: FileUpload | null
}

export interface EmployeeContract {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  contract_number: string
  contract_type: string
  contract_type_label: string
  start_date: string
  end_date: string | null
  days_remaining: number | null
  status: ContractStatus
  status_label: string
  document_file_id: string | null
  document_file?: FileUpload | null
  notes: string | null
  terminated_at: string | null
  created_at: string | null
}

export interface EmployeeDocument {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  document_type: string
  document_type_label: string
  title: string | null
  document_number: string | null
  file_upload_id: string
  file?: FileUpload | null
  issued_at: string | null
  expires_at: string | null
  is_expired: boolean
  status: DocumentStatus
  status_label: string
  verification_note: string | null
  verified_by_name?: string | null
  verified_at: string | null
  version: number
  previous_version_id: string | null
  is_current: boolean
  notes: string | null
  created_at: string | null
}

export interface EmployeeTraining {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  name: string
  organizer: string
  start_date: string
  end_date: string | null
  duration_hours: string | null
  location: string | null
  cost: string | null
  status: TrainingStatus
  status_label: string
  certificate_file_id: string | null
  certificate_file?: FileUpload | null
  notes: string | null
}

export interface EmployeeCertification {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  name: string
  issuer: string
  certificate_number: string | null
  issued_at: string
  expires_at: string | null
  is_expired: boolean
  document_file_id: string | null
  document_file?: FileUpload | null
  notes: string | null
}

export interface PerformanceReview {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  reviewer_employee_id: string | null
  reviewer?: HrEmployeeSummary | null
  period: string
  score: string
  category: string
  category_label: string
  notes: string | null
  status: PerformanceStatus
  status_label: string
  updated_at: string | null
}

export interface EmployeeTransfer {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  from_work_unit_id: string | null
  from_work_unit_name: string | null
  to_work_unit_id: string | null
  to_work_unit_name: string | null
  from_position_id: string | null
  from_position_name: string | null
  to_position_id: string | null
  to_position_name: string | null
  effective_date: string
  decree_number: string | null
  document_file_id: string | null
  document_file?: FileUpload | null
  reason: string | null
  status: TransferStatus
  status_label: string
  applied_at: string | null
  created_by_name?: string | null
  created_at: string | null
}

export interface LeaveRequest {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  leave_type: string
  leave_type_label: string
  start_date: string
  end_date: string
  days: number
  reason: string
  attachment_file_id: string | null
  attachment_file?: FileUpload | null
  status: LeaveStatus
  status_label: string
  hr_request_id?: string | null
  requested_by_name?: string | null
  submitted_at: string | null
  approved_by_name?: string | null
  approved_at: string | null
  rejected_by_name?: string | null
  rejected_at: string | null
  approval_note: string | null
  cancelled_at: string | null
  created_at: string | null
}

export interface LeavePayload {
  employee_id?: string
  leave_type: string
  start_date: string
  end_date: string
  reason: string
  attachment_file_id?: string | null
  submit?: boolean
}

export interface LeaveQuota {
  year: number
  quota: number
  used: number
  remaining: number
}

export interface HrRequestHistory {
  id: string
  event: string
  description: string
  actor_name: string | null
  created_at: string
}

export interface HrRequest {
  id: string
  employee_id: string
  employee?: HrEmployeeSummary | null
  type: HrRequestType
  type_label: string
  title: string
  description: string | null
  payload: { changes?: Record<string, string> } & Record<string, unknown> | null
  leave_request_id: string | null
  leave_request?: LeaveRequest | null
  attachment_file_id: string | null
  attachment_file?: FileUpload | null
  status: HrRequestStatus
  status_label: string
  requested_by_name?: string | null
  approved_by_name?: string | null
  approved_at: string | null
  rejected_by_name?: string | null
  rejected_at: string | null
  approval_note: string | null
  requires_processing: boolean
  processed_at: string | null
  processed_by_name?: string | null
  cancelled_at: string | null
  can_act: boolean
  current_step_name?: string | null
  histories?: HrRequestHistory[]
  created_at: string | null
}

export interface HrRequestPayload {
  employee_id?: string
  type: Exclude<HrRequestType, 'leave'>
  title: string
  description?: string | null
  attachment_file_id?: string | null
  payload?: { changes?: Record<string, string> } | null
}

export interface HrChartPoint {
  key: string
  label: string
  total: number
}

export interface HrAlertBucket<T> {
  count: number
  items: T[]
}

export interface HrContractAlert {
  id: string
  employee_id: string
  employee_name: string | null
  contract_number: string
  end_date: string | null
  days_remaining: number | null
}

export interface HrDocumentAlert {
  id: string
  source: 'document' | 'certification'
  employee_id: string
  employee_name: string | null
  name: string
  expires_at: string | null
  is_expired: boolean
}

export interface HrRequestAlert {
  id: string
  employee_id: string
  employee_name: string | null
  type: HrRequestType
  type_label: string
  title: string
  created_at: string | null
}

export interface HrAlerts {
  contracts_expiring: HrAlertBucket<HrContractAlert>
  documents_expiring: HrAlertBucket<HrDocumentAlert>
  pending_requests: HrAlertBucket<HrRequestAlert>
  requests_to_process: HrAlertBucket<HrRequestAlert>
}

export interface HrDashboard {
  stats: {
    total_employees: number
    total_lecturers: number
    total_staff: number
    active_employees: number
    inactive_employees: number
    contract_employees: number
    contracts_expiring: number
    pending_leave_requests: number
  }
  charts: {
    by_type: HrChartPoint[]
    by_employment_status: HrChartPoint[]
    by_education: HrChartPoint[]
    by_academic_rank: HrChartPoint[]
    by_work_unit: HrChartPoint[]
  }
  alerts: HrAlerts
}

export interface HrNotificationItem {
  id: string
  event_key: string | null
  message: string | null
  read_at: string | null
  created_at: string | null
}

export interface HrNotifications {
  alerts: HrAlerts
  notifications: HrNotificationItem[]
  unread_count: number
}

export type HrReportType =
  | 'employees'
  | 'lecturers'
  | 'staff'
  | 'by_unit'
  | 'by_education'
  | 'by_employment_status'
  | 'by_position'
  | 'contracts'
  | 'leave'
  | 'certifications'
  | 'trainings'

export interface HrReportTypeOption {
  type: HrReportType
  label: string
}

export interface HrReportFilters {
  employee_type?: EmployeeType | ''
  is_active?: string
  date_from?: string
  date_to?: string
}

export interface HrReport {
  type: HrReportType
  label: string
  columns: Array<{ key: string; label: string }>
  rows: Array<Record<string, string | number | null>>
  summary: Record<string, string | number | null>
}

export interface HrAuditLog {
  id: string
  user_name: string
  action: string
  module: string | null
  module_label: string
  record_id: string
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
}

export interface HrSelfProfile {
  employee: HrEmployeeDetail
  leave_quota: LeaveQuota
}

export type ExportFormat = 'xlsx' | 'pdf'

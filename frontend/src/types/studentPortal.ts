/**
 * Bentuk respons API Portal Mahasiswa — mencerminkan persis array yang
 * dikirim service backend (app/Modules/Academic/Services/Student*,
 * KrsPlanService, LearningService, StudentRequestService, PortalFormatter)
 * dan feed pengumuman/notifikasi. Semua data selalu milik mahasiswa yang
 * login; tidak ada student_id di sisi klien.
 */

export interface PortalTerm {
  id: string
  academic_year: string
  semester: 'ganjil' | 'genap'
  label: string
  start_date: string
  end_date: string
  is_current: boolean
  krs_start_date: string | null
  krs_end_date: string | null
  is_krs_open: boolean
}

export interface PortalLecturer {
  id: string
  name: string
  email: string | null
}

export interface PortalSchedule {
  id: string
  day_of_week: number
  day_label: string
  start_time: string
  end_time: string
  room: string | null
}

export interface PortalCourseInfo {
  id: string
  code: string
  name: string
  credits: number
  semester_level: number
}

export interface PortalClassSection {
  id: string
  class_code: string
  course: PortalCourseInfo
  lecturer: PortalLecturer | null
  schedules: PortalSchedule[]
}

export interface StudentStatusInfo {
  value: 'active' | 'leave' | 'graduated' | 'inactive' | 'dropped_out' | 'resigned'
  label: string
}

export interface AcademicSummary {
  nim: string
  name: string
  study_program: { id: string; name: string; degree_level: string }
  faculty: { id: string; name: string } | null
  admission_year: number
  semester: number | null
  status: StudentStatusInfo
  academic_advisor: PortalLecturer | null
  curriculum: { id: string; name: string; academic_year: string; total_credits: number | null } | null
  current_term: PortalTerm | null
  ipk: number
  last_ips: { academic_term_id: string; label: string; ips: number } | null
  total_credits: number
  passed_credits: number
  remaining_credits: number | null
  current_term_credits: number
  current_term_enrolled_credits: number
  max_credits: number | null
  enrolled_at: string
  graduated_at: string | null
}

export interface StudentProfile {
  id: string
  personal: {
    name: string
    nim: string
    birth_place: string | null
    birth_date: string | null
    gender: 'male' | 'female' | null
    gender_label: string | null
  }
  contact: { email: string | null; phone: string | null }
  address: string | null
  photo: { file_id: string; mime_type: string } | null
  academic: AcademicSummary
  editable_fields: string[]
  request_only_fields: string[]
  pending_data_change: { id: string; submitted_at: string | null; changes: Record<string, string> } | null
}

export interface UpdateProfilePayload {
  phone?: string | null
  address?: string | null
  photo_file_id?: string | null
}

export interface AcademicHistoryEvent {
  date: string
  type: 'admission' | 'term' | 'status' | 'graduation'
  title: string
  description: string | null
}

// --- KRS -------------------------------------------------------------------

export type KrsStatus = 'no_active_term' | 'not_started' | 'draft' | 'submitted' | 'approved' | 'rejected'
export type KrsItemStatus = 'draft' | 'pending' | 'enrolled' | 'dropped'

export interface KrsSubmissionInfo {
  id: string
  status: 'draft' | 'submitted' | 'approved' | 'rejected'
  status_label: string
  total_credits: number
  max_credits: number | null
  submitted_at: string | null
  decided_at: string | null
  decided_by: string | null
  decision_note: string | null
}

export interface KrsPlanItem {
  id: string
  status: KrsItemStatus
  status_label: string
  class_section: PortalClassSection
}

export interface KrsOverview {
  term: PortalTerm | null
  status: KrsStatus
  status_label: string
  submission: KrsSubmissionInfo | null
  items: KrsPlanItem[]
  total_credits: number
  max_credits: number | null
  remaining_credits: number | null
  can_edit: boolean
  can_submit: boolean
  can_cancel_submission: boolean
  notices: string[]
  academic_advisor: PortalLecturer | null
}

export interface KrsOfferingClass extends PortalClassSection {
  capacity: number
  seats_taken: number
  seats_left: number
  is_full: boolean
  is_selected: boolean
  conflicts: string[]
}

export interface KrsOffering {
  course: PortalCourseInfo
  prerequisites: { course_id: string; code: string | null; name: string | null; min_grade: string; satisfied: boolean }[]
  best_grade: string | null
  is_retake: boolean
  is_selected: boolean
  selected_class_section_id: string | null
  blockers: string[]
  classes: KrsOfferingClass[]
}

export interface KrsOfferings {
  term: PortalTerm | null
  courses: KrsOffering[]
  total_credits: number
  max_credits: number | null
}

export interface KrsHistoryEntry {
  term: PortalTerm | null
  status: KrsStatus
  status_label: string
  total_credits: number
  course_count: number
  submission: KrsSubmissionInfo | null
  items: {
    id: string
    status: KrsItemStatus
    status_label: string
    course_code: string
    course_name: string
    credits: number
    class_code: string
    letter_grade: string | null
  }[]
}

// --- Jadwal & presensi -------------------------------------------------------

export interface ScheduleSlot extends PortalSchedule {
  krs_item_id: string
  krs_status: KrsItemStatus
  is_pending_approval: boolean
  class_section_id: string
  class_code: string
  course_code: string
  course_name: string
  credits: number
  lecturer: PortalLecturer | null
}

export interface TodaySlot extends ScheduleSlot {
  date: string
  status: 'upcoming' | 'ongoing' | 'finished'
}

export interface ScheduleOverview {
  term: PortalTerm | null
  now: string
  timezone: string
  is_in_term: boolean
  slots: ScheduleSlot[]
  today: TodaySlot[]
  next_class: (ScheduleSlot & { date: string; starts_at: string }) | null
}

export interface AttendanceTotals {
  total_meetings: number
  present: number
  permitted: number
  sick: number
  absent: number
  percentage: number | null
}

export interface AttendanceCourse extends AttendanceTotals {
  krs_item_id: string
  class_section_id: string
  course_code: string
  course_name: string
  class_code: string
  credits: number
  lecturer: PortalLecturer | null
  is_below_minimum: boolean
}

export interface AttendanceSummary {
  term: PortalTerm | null
  terms: PortalTerm[]
  minimum_percent: number
  courses: AttendanceCourse[]
  overall: AttendanceTotals
}

export interface AttendanceDetail extends AttendanceTotals {
  krs_item_id: string
  term: PortalTerm | null
  course_code: string
  course_name: string
  class_code: string
  lecturer: PortalLecturer | null
  minimum_percent: number
  meetings: {
    meeting_number: number
    meeting_date: string
    status: 'present' | 'permitted' | 'sick' | 'absent'
    status_label: string
    notes: string | null
  }[]
}

// --- Nilai, KHS, transkrip ---------------------------------------------------

export interface GradeRow {
  krs_item_id: string
  course_code: string
  course_name: string
  class_code: string
  credits: number
  score: string | null
  letter_grade: string | null
  weight: number | null
  quality_points: number | null
  is_passing: boolean | null
  is_graded: boolean
  graded_at: string | null
}

export interface GradesByTerm {
  terms: {
    term: PortalTerm
    semester_number: number | null
    ips: number | null
    graded_credits: number
    total_credits: number
    rows: GradeRow[]
  }[]
  ipk: number
  total_credits: number
  passed_credits: number
}

export interface Khs {
  terms: PortalTerm[]
  term: PortalTerm | null
  semester_number: number | null
  rows: GradeRow[]
  summary: {
    term_credits: number
    taken_credits: number
    ips: number
    ipk: number
    cumulative_credits: number
    all_graded: boolean
  } | null
}

export interface TranscriptRow {
  course_id: string
  course_code: string
  course_name: string
  credits: number
  semester_level: number
  term_label: string
  term_start: string
  score: string | null
  letter_grade: string
  weight: number
  quality_points: number
  is_passing: boolean
  attempts: number
}

export interface Transcript {
  student: {
    name: string
    nim: string
    study_program: string
    degree_level: string
    faculty: string | null
    admission_year: number
    status: string
    university: string | null
  }
  rows: TranscriptRow[]
  summary: { total_credits: number; passed_credits: number; ipk: number; course_count: number }
  terms: { academic_term_id: string; label: string; sks: number; ip: number }[]
}

// --- Kalender ------------------------------------------------------------------

export interface CalendarEvent {
  id: string
  type: 'academic' | 'class' | 'exam' | 'assignment'
  category: string
  category_label: string
  title: string
  description: string | null
  start: string
  end: string
  all_day: boolean
  location: string | null
  link: string | null
}

// --- Perkuliahan -----------------------------------------------------------

export interface PortalFileRef {
  id: string
  name: string
  size_bytes: number
  mime_type?: string
}

export interface CourseMaterial {
  id: string
  meeting_number: number | null
  title: string
  description: string | null
  type: 'file' | 'link' | 'video' | 'text'
  type_label: string
  url: string | null
  file: PortalFileRef | null
  is_published: boolean
  published_at: string | null
}

export type AssignmentStatus = 'not_submitted' | 'submitted' | 'late' | 'graded' | 'missed'

export interface AssignmentSubmissionInfo {
  id: string
  notes: string | null
  submitted_at: string
  is_late: boolean
  submission_count: number
  score: string | null
  feedback: string | null
  graded_at: string | null
  graded_by: string | null
  file: PortalFileRef | null
}

export interface AssignmentBase {
  id: string
  class_section_id: string
  title: string
  description: string | null
  due_at: string
  allow_late_submission: boolean
  allow_resubmission: boolean
  max_file_size_mb: number
  allowed_extensions: string[]
  max_score: string
  is_published: boolean
  published_at: string | null
  attachment: PortalFileRef | null
  submissions_count: number | null
  graded_count: number | null
}

export interface StudentAssignment extends AssignmentBase {
  course: { code: string; name: string; class_code: string; lecturer: PortalLecturer | null } | null
  status: AssignmentStatus
  status_label: string
  is_past_due: boolean
  can_submit: boolean
  submission: AssignmentSubmissionInfo | null
}

export interface StudentCourse extends PortalClassSection {
  krs_item_id: string
  materials_count: number
  assignments_count: number
  pending_assignments_count: number
  attendance: AttendanceTotals
}

export interface StudentCourseDetail extends PortalClassSection {
  krs_item_id: string
  term: PortalTerm | null
  meetings: { meeting_number: number | null; label: string; materials: CourseMaterial[] }[]
  assignments: StudentAssignment[]
  exams: { id: string; title: string; starts_at: string | null; ends_at: string | null; duration_minutes: number }[]
  attendance: AttendanceTotals
}

export interface StudentAssignmentList {
  term: PortalTerm | null
  assignments: StudentAssignment[]
  counts: Record<'all' | AssignmentStatus, number>
}

// --- Pengajuan ------------------------------------------------------------------

export type StudentRequestType = 'leave' | 'reactivation' | 'data_change' | 'letter' | 'other'
export type StudentRequestStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'cancelled'

export interface StudentRequest {
  id: string
  type: StudentRequestType
  type_label: string
  status: StudentRequestStatus
  status_label: string
  title: string
  description: string | null
  payload: Record<string, unknown> | null
  letter_type_label: string | null
  attachment: PortalFileRef | null
  term?: { id: string; label: string } | null
  student?: { id: string; nim: string; name: string; status: string; status_label: string }
  submitted_at: string | null
  decided_at: string | null
  reviewer?: string | null
  decision_note: string | null
  cancelled_at: string | null
  current_step: { name: string | null; status: string } | null
  can_act: boolean
  history: { event: string; description: string | null; created_at: string | null }[]
  has_letter: boolean
  created_at: string | null
}

export interface StudentRequestPayload {
  type?: StudentRequestType
  title?: string
  description?: string | null
  payload?: Record<string, unknown> | null
  attachment_file_id?: string | null
  submit?: boolean
}

export interface StudentRequestOptions {
  letter_types: { value: string; label: string }[]
  data_change_fields: string[]
}

// --- Pengumuman, notifikasi, dokumen ---------------------------------------------

export interface PortalAnnouncement {
  id: string
  title: string
  body: string
  target_scope: 'universitas' | 'fakultas' | 'program_studi'
  target_id: string | null
  audience: 'all' | 'students' | 'lecturers' | 'staff'
  target_admission_year: number | null
  target_semester: number | null
  is_pinned: boolean
  published_at: string
  creator_name?: string | null
  attachment?: PortalFileRef | null
  is_read?: boolean
  created_at: string
}

export type AnnouncementFilter = 'all' | 'unread' | 'study_program' | 'faculty' | 'admission_year' | 'semester' | 'students'

export interface InboxNotification {
  id: string
  event_key: string | null
  message: string | null
  link: string | null
  read_at: string | null
  created_at: string | null
}

export interface StudentDocument {
  type: 'krs' | 'khs' | 'transcript' | 'letter'
  title: string
  description: string
  url: string
  filename: string
}

// --- Dashboard ------------------------------------------------------------------

export interface DashboardAlert {
  type: 'status' | 'krs' | 'exam' | 'assignment' | 'grade' | 'attendance' | 'announcement'
  severity: 'info' | 'success' | 'warning' | 'danger'
  message: string
  link: string | null
}

export interface StudentDashboard {
  profile: {
    name: string
    nim: string
    photo_file_id: string | null
    study_program: string
    faculty: string | null
    admission_year: number
    semester: number | null
    status: StudentStatusInfo
  }
  academic: {
    ipk: number
    last_ips: AcademicSummary['last_ips']
    total_credits: number
    passed_credits: number
    current_term_credits: number
    max_credits: number | null
    remaining_credits: number | null
    current_term: PortalTerm | null
    academic_advisor: PortalLecturer | null
  }
  krs: {
    status: KrsStatus
    status_label: string
    total_credits: number
    max_credits: number | null
    course_count: number
    is_open: boolean
    krs_end_date: string | null
    decision_note: string | null
  }
  today_schedule: TodaySlot[]
  next_class: ScheduleOverview['next_class']
  attendance: { overall: AttendanceTotals; minimum_percent: number; below_minimum: AttendanceCourse[] }
  upcoming_exams: {
    id: string
    title: string
    course_name: string
    starts_at: string | null
    ends_at: string | null
    duration_minutes: number
    status: 'upcoming' | 'available' | 'in_progress' | 'completed' | 'expired'
  }[]
  assignments_due: StudentAssignment[]
  recent_grades: { course_code: string; course_name: string; term_label: string; letter_grade: string; graded_at: string | null }[]
  announcements: { unread_count: number; latest: { id: string; title: string; published_at: string; is_pinned: boolean; is_read: boolean }[] }
  notifications_unread: number
  alerts: DashboardAlert[]
}

// --- Dosen / Bagian Akademik ---------------------------------------------------

export interface KrsSubmissionReview {
  id: string
  status: 'draft' | 'submitted' | 'approved' | 'rejected'
  status_label: string
  total_credits: number
  max_credits: number | null
  submitted_at: string | null
  decided_at: string | null
  decided_by?: string | null
  decision_note: string | null
  term?: PortalTerm | null
  student?: {
    id: string
    nim: string
    name: string
    admission_year: number
    status: string
    study_program: string | null
    academic_advisor: PortalLecturer | null
  }
  items?: KrsPlanItem[]
}

export interface TeachingClass extends PortalClassSection {
  term: PortalTerm | null
  capacity: number
  enrolled_count: number
  materials_count: number
  assignments_count: number
}

export interface CourseMaterialPayload {
  title?: string
  type?: CourseMaterial['type']
  meeting_number?: number | null
  description?: string | null
  url?: string | null
  file_upload_id?: string | null
  is_published?: boolean
}

export interface AssignmentPayload {
  title?: string
  description?: string | null
  due_at?: string
  allow_late_submission?: boolean
  allow_resubmission?: boolean
  max_file_size_mb?: number
  allowed_extensions?: string[] | null
  max_score?: number
  is_published?: boolean
  attachment_file_id?: string | null
}

export interface AssignmentSubmissionRow {
  krs_item_id: string
  student: { id: string; nim: string; name: string }
  status: AssignmentStatus
  submission: AssignmentSubmissionInfo | null
}

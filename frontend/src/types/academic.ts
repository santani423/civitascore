export type StudentStatus = 'active' | 'leave' | 'graduated' | 'inactive' | 'dropped_out'

export interface Student {
  id: string
  study_program_id: string
  study_program_name: string | null
  nim: string
  name: string
  email: string | null
  admission_year: number
  status: StudentStatus
  enrolled_at: string
  graduated_at: string | null
  created_at: string
}

export interface StoreStudentPayload {
  study_program_id: string
  nim: string
  name: string
  email?: string | null
  tanggal_lahir?: string | null
  admission_year: number
  status: StudentStatus
  enrolled_at: string
}

export interface Lecturer {
  id: string
  faculty_id: string | null
  faculty_name: string | null
  nidn: string
  name: string
  email: string | null
  is_active: boolean
  created_at: string
}

export interface Employee {
  id: string
  unit_kerja: string
  name: string
  email: string | null
  position: string | null
  is_active: boolean
  created_at: string
}

export interface StudyProgram {
  id: string
  faculty_id: string
  faculty_name: string | null
  code: string
  name: string
  degree_level: string
  is_active: boolean
  students_count: number | null
  class_sections_count: number | null
  created_at: string
}

export interface ClassSection {
  id: string
  study_program_id: string
  study_program_name: string | null
  academic_term_id: string
  academic_term_label: string | null
  course_id: string
  course_name: string | null
  course_code: string | null
  credits: number | null
  class_code: string
  capacity: number
  enrolled_count: number | null
  is_active: boolean
  created_at: string
}

export interface Curriculum {
  id: string
  study_program_id: string
  study_program_name: string | null
  name: string
  academic_year: string
  is_active: boolean
  courses_count: number | null
  courses?: Course[]
  created_at: string
}

export interface Course {
  id: string
  study_program_id: string
  study_program_name: string | null
  curriculum_id: string
  curriculum_name: string | null
  code: string
  name: string
  credits: number
  semester_level: number
  is_active: boolean
  created_at: string
}

export type KrsItemStatus = 'enrolled' | 'dropped'

export interface KrsItem {
  id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  class_section_id: string
  course_name: string | null
  course_code: string | null
  class_code: string | null
  academic_term_id: string
  academic_term_label: string | null
  status: KrsItemStatus
  letter_grade: string | null
  created_at: string
}

export type LetterGrade = 'A' | 'AB' | 'B' | 'BC' | 'C' | 'D' | 'E'

export interface Grade {
  id: string
  krs_item_id: string
  student_id: string | null
  student_name: string | null
  student_nim: string | null
  course_name: string | null
  course_code: string | null
  academic_term_label: string | null
  letter_grade: LetterGrade | null
  score: string | null
  submitted_at: string | null
  created_at: string
}

export type AttendanceStatus = 'present' | 'permitted' | 'sick' | 'absent'

export interface Attendance {
  id: string
  krs_item_id: string
  student_id: string | null
  student_name: string | null
  student_nim: string | null
  course_name: string | null
  course_code: string | null
  meeting_number: number
  meeting_date: string
  status: AttendanceStatus
  notes: string | null
  created_at: string
}

export interface EnrollKrsPayload {
  student_id: string
  class_section_id: string
}

export interface UpsertGradePayload {
  score: number
  letter_grade?: LetterGrade | null
}

export interface AttendanceEntryPayload {
  krs_item_id: string
  status: AttendanceStatus
  notes?: string | null
}

export interface RecordAttendanceBatchPayload {
  meeting_number: number
  meeting_date: string
  entries: AttendanceEntryPayload[]
}

export type QuestionSelectionMode = 'all' | 'random' | 'manual'

export interface Exam {
  id: string
  class_section_id: string
  class_code: string | null
  course_name: string | null
  title: string
  duration_minutes: number
  starts_at: string | null
  ends_at: string | null
  creator_name: string | null
  question_pool_size: number
  questions_per_participant: number
  question_selection_mode: QuestionSelectionMode
  randomize_questions: boolean
  randomize_options: boolean
  allow_back_navigation: boolean
  show_result_after_submission: boolean
  max_attempts: number
  is_published: boolean
  published_at: string | null
  access_token: string | null
  access_token_generated_at: string | null
  weight_percentage: string | null
  created_at: string
}

export interface ExamOption {
  id: string
  option_text: string
  is_correct: boolean
  order_index: number
}

export interface ExamQuestion {
  id: string
  exam_id: string
  question_bank_item_id: string | null
  question_text: string
  points: string
  order_index: number
  is_selected: boolean
  options: ExamOption[]
}

export interface StoreExamPayload {
  class_section_id: string
  title: string
  duration_minutes: number
  starts_at?: string | null
  ends_at?: string | null
  questions_per_participant: number
  question_selection_mode: QuestionSelectionMode
  randomize_questions?: boolean
  randomize_options?: boolean
  allow_back_navigation?: boolean
  show_result_after_submission?: boolean
  max_attempts?: number
  weight_percentage?: number | null
}

export type UpdateExamPayload = Partial<Omit<StoreExamPayload, 'class_section_id'>>

export type ExamParticipantStatus = 'not_started' | 'in_progress' | 'completed'

export interface ExamParticipant {
  krs_item_id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  attempts_used: number
  status: ExamParticipantStatus
  score: string | null
  raw_score: string | null
  penalty_score: string | null
  grade: string | null
  weighted_score: string | null
  violation_count: number
  latest_attempt_id: string | null
  submitted_at: string | null
}

/** Jenis pelanggaran yang bisa dideteksi dari implementasi anti-cheat browser (spec §4) — lihat utils/examViolationTracking. */
export type ExamViolationType =
  | 'tab_switch'
  | 'window_blur'
  | 'fullscreen_exit'
  | 'copy_attempt'
  | 'paste_attempt'
  | 'context_menu'

export interface RecordExamViolationPayload {
  violation_type: ExamViolationType
  metadata?: Record<string, unknown> | null
}

export interface ExamViolation {
  id: string
  exam_attempt_id: string
  sequence_number: number
  violation_type: ExamViolationType
  violation_label: string
  penalty_points: string
  occurred_at: string
  student_name?: string
  student_nim?: string
}

export interface ExamAttemptViolationTimeline {
  student: { name: string; nim: string }
  raw_score: string | null
  penalty_score: string
  score: string | null
  grade: string | null
  weighted_score: string | null
  violation_count: number
  violations: ExamViolation[]
}

export interface ExamGradeRange {
  id: string
  grade: string
  min_score: string
  max_score: string
}

export interface UpsertExamGradeRangePayload {
  grade: string
  min_score: number
  max_score: number
}

export interface ExamRecapSummary {
  exam_title: string
  course_name: string | null
  class_code: string | null
  semester_label: string | null
  total_participants: number
  completed: number
  in_progress: number
  not_started: number
  average_score: number | null
  highest_score: number | null
  lowest_score: number | null
}

export interface ExamRecap {
  summary: ExamRecapSummary
  participants: ExamParticipant[]
}

export interface ExamQuestionOptionPayload {
  option_text: string
  is_correct: boolean
}

export interface StoreExamQuestionPayload {
  question_text: string
  points?: number
  is_selected?: boolean
  options: ExamQuestionOptionPayload[]
}

export type UpdateExamQuestionPayload = Partial<StoreExamQuestionPayload>

export interface QuestionBankItem {
  id: string
  course_id: string | null
  course_name: string | null
  question_text: string
  points: string
  options: ExamOption[]
  usage_count: number | null
  created_at: string
}

export interface StoreQuestionBankItemPayload {
  course_id?: string | null
  question_text: string
  points?: number
  options: ExamQuestionOptionPayload[]
}

export type UpdateQuestionBankItemPayload = Partial<StoreQuestionBankItemPayload>

export interface TranscriptTerm {
  academic_term_id: string
  label: string
  sks: number
  ip: number
}

export interface Transcript {
  terms: TranscriptTerm[]
  ipk: number
  total_sks: number
}

/**
 * Status ujian dari sudut pandang satu peserta (dihitung backend, lihat
 * ExamService::computeStudentStatus) — berbeda dari `Exam.is_published`
 * (admin), yang cuma menyatakan ujian sudah bisa diakses peserta yang
 * berhak, bukan status pengerjaan individual mahasiswa ybs.
 */
export type StudentExamStatus = 'upcoming' | 'available' | 'in_progress' | 'completed' | 'expired'

/** Tampilan ujian dari Portal Mahasiswa — aman untuk peserta (tidak pernah menyertakan soal/opsi/kunci jawaban). */
export interface StudentExam {
  id: string
  title: string
  course_name: string | null
  lecturer_name: string | null
  duration_minutes: number
  starts_at: string | null
  ends_at: string | null
  questions_per_participant: number
  allow_back_navigation: boolean
  max_attempts: number
  attempts_used: number
  status: StudentExamStatus
  result_visible: boolean
  score: string | null
  latest_attempt_id: string | null
}

export type ExamAttemptStatus = 'in_progress' | 'submitted'

export interface StudentExamAttemptOption {
  id: string
  option_text: string
}

export interface StudentExamAttemptQuestion {
  id: string
  question_text: string
  points: string | null
  options: StudentExamAttemptOption[]
  selected_option_id: string | null
}

/** Percobaan ujian milik sendiri — urutan soal & opsi sudah stabil sesuai penugasan backend, `is_correct` TIDAK PERNAH disertakan. */
export interface StudentExamAttempt {
  id: string
  exam_id: string
  krs_item_id: string
  attempt_number: number
  status: ExamAttemptStatus
  started_at: string
  submitted_at: string | null
  score: string | null
  raw_score: string | null
  penalty_score: string
  violation_count: number
  grade: string | null
  weighted_score: string | null
  questions: StudentExamAttemptQuestion[]
  result_visible: boolean
  /** Hanya terisi dari endpoint akses publik (/exam/*), yang tidak punya sesi login untuk menunjukkan identitas peserta. */
  student?: { name: string; nim: string }
}

export interface AnswerExamAttemptPayload {
  exam_question_id: string
  exam_question_option_id: string | null
}

/**
 * Hasil + koreksi lengkap satu percobaan (backend adalah satu-satunya
 * sumber kebenaran — lihat ExamService::buildAttemptResult) — hanya bisa
 * diambil setelah attempt Submitted dan `exam.show_result_after_submission`
 * bernilai true, lihat StudentExamController::guardResultAvailable().
 */
export interface StudentExamResultOption {
  id: string
  option_text: string | null
}

export interface StudentExamResultQuestion {
  number: number
  question_text: string | null
  options: StudentExamResultOption[]
  selected_option_id: string | null
  correct_option_id: string | null
  is_correct: boolean
  explanation: string | null
}

export interface StudentExamResult {
  attempt: {
    id: string
    attempt_number: number
    started_at: string
    submitted_at: string | null
    duration_seconds: number | null
  }
  student: {
    name: string
    nim: string
  }
  exam: {
    title: string
    course_name: string | null
  }
  summary: {
    total_questions: number
    correct_answers: number
    wrong_answers: number
    score: string
    percentage: string
    raw_score: string
    penalty_score: string
    violation_count: number
    grade: string | null
    weighted_score: string | null
  }
  questions: StudentExamResultQuestion[]
}

/**
 * Info ujian di halaman akses publik (/exam/{access_token}), sebelum NIM
 * divalidasi — aman ditampilkan ke siapa pun yang membuka link/scan QR,
 * tidak pernah menyertakan soal/opsi/token (lihat PublicExamResource).
 */
export interface PublicExamInfo {
  title: string
  course_name: string | null
  lecturer_name: string | null
  duration_minutes: number
  ends_at: string | null
  allow_back_navigation: boolean
}

export interface PublicExamAccessResponse {
  session_token: string
}

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
  questions_per_participant: number
  question_selection_mode: QuestionSelectionMode
  randomize_questions?: boolean
  randomize_options?: boolean
  allow_back_navigation?: boolean
  show_result_after_submission?: boolean
  max_attempts?: number
}

export type UpdateExamPayload = Partial<Omit<StoreExamPayload, 'class_section_id'>>

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

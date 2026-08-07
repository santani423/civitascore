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

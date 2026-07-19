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
  course_name: string
  class_code: string
  capacity: number
  is_active: boolean
  created_at: string
}

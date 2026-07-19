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

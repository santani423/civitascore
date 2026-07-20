export type InternshipProgramType = 'magang' | 'kkn' | 'mbkm'

export type InternshipStatus = 'terdaftar' | 'berlangsung' | 'selesai' | 'dibatalkan'

export interface Internship {
  id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  program_type: InternshipProgramType
  institution_name: string
  position: string | null
  supervisor_lecturer_id: string | null
  supervisor_name: string | null
  start_date: string
  end_date: string | null
  status: InternshipStatus
  sks_converted: number | null
  created_at: string
}

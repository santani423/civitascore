export type AlumniEmploymentStatus = 'bekerja' | 'wirausaha' | 'melanjutkan_studi' | 'mencari_kerja' | 'belum_bekerja'

export interface Alumni {
  id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  study_program_name: string | null
  graduation_year: number
  employment_status: AlumniEmploymentStatus
  company_name: string | null
  job_title: string | null
  waiting_period_months: number | null
  is_verified: boolean
  created_at: string
}

export type ScholarshipApplicationStatus = 'submitted' | 'under_review' | 'approved' | 'rejected'

export interface ScholarshipApplication {
  id: string
  scholarship_id: string
  scholarship_name: string | null
  student_id: string
  student_name: string | null
  student_nim: string | null
  status: ScholarshipApplicationStatus
  submitted_at: string
  reviewed_at: string | null
  notes: string | null
}

export interface Scholarship {
  id: string
  name: string
  provider: string
  quota: number
  amount: string
  academic_year: string
  registration_start: string
  registration_end: string
  is_active: boolean
  applications_count: number | null
  applications?: ScholarshipApplication[]
  created_at: string
}

export type ThesisType = 'skripsi' | 'tesis' | 'disertasi'

export type ThesisStatus = 'proposal' | 'bimbingan' | 'seminar_proposal' | 'penelitian' | 'sidang' | 'selesai'

export interface Thesis {
  id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  supervisor_lecturer_id: string | null
  supervisor_name: string | null
  title: string
  thesis_type: ThesisType
  status: ThesisStatus
  submitted_at: string
  completed_at: string | null
  created_at: string
}

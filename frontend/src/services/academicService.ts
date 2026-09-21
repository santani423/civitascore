import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type {
  AnswerExamAttemptPayload,
  Attendance,
  ClassSection,
  Course,
  Curriculum,
  EnrollKrsPayload,
  Employee,
  Exam,
  ExamAttemptViolationTimeline,
  ExamGradeRange,
  ExamParticipant,
  ExamQuestion,
  ExamRecap,
  ExamViolation,
  Grade,
  KrsItem,
  Lecturer,
  QuestionBankItem,
  RecordAttendanceBatchPayload,
  RecordExamViolationPayload,
  Student,
  StoreExamPayload,
  StoreExamQuestionPayload,
  StoreLecturerPayload,
  StoreQuestionBankItemPayload,
  StoreStudentPayload,
  StudentExam,
  StudentExamAttempt,
  StudentExamResult,
  StudyProgram,
  Transcript,
  UpdateExamPayload,
  UpdateExamQuestionPayload,
  UpdateLecturerPayload,
  UpdateQuestionBankItemPayload,
  UpsertExamGradeRangePayload,
  UpsertGradePayload,
} from '@/types/academic'
import { toQueryParams } from '@/utils/listParams'

export const studentService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Student>> {
    const response = await apiClient.get<ApiSuccessResponse<Student[]>>('/students', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Student> {
    const response = await apiClient.get<ApiSuccessResponse<Student>>(`/students/${id}`)
    return response.data.data
  },

  async transcript(id: string): Promise<Transcript> {
    const response = await apiClient.get<ApiSuccessResponse<Transcript>>(`/students/${id}/transcript`)
    return response.data.data
  },

  async create(payload: StoreStudentPayload): Promise<Student> {
    const response = await apiClient.post<ApiSuccessResponse<Student>>('/students', payload)
    return response.data.data
  },
}

export const lecturerService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Lecturer>> {
    const response = await apiClient.get<ApiSuccessResponse<Lecturer[]>>('/lecturers', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Lecturer> {
    const response = await apiClient.get<ApiSuccessResponse<Lecturer>>(`/lecturers/${id}`)
    return response.data.data
  },

  async create(payload: StoreLecturerPayload): Promise<Lecturer> {
    const response = await apiClient.post<ApiSuccessResponse<Lecturer>>('/lecturers', payload)
    return response.data.data
  },

  async update(id: string, payload: UpdateLecturerPayload): Promise<Lecturer> {
    const response = await apiClient.put<ApiSuccessResponse<Lecturer>>(`/lecturers/${id}`, payload)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/lecturers/${id}`)
  },
}

export const employeeService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Employee>> {
    const response = await apiClient.get<ApiSuccessResponse<Employee[]>>('/employees', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Employee> {
    const response = await apiClient.get<ApiSuccessResponse<Employee>>(`/employees/${id}`)
    return response.data.data
  },
}

export const studyProgramService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<StudyProgram>> {
    const response = await apiClient.get<ApiSuccessResponse<StudyProgram[]>>('/study-programs', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<StudyProgram> {
    const response = await apiClient.get<ApiSuccessResponse<StudyProgram>>(`/study-programs/${id}`)
    return response.data.data
  },
}

export const classSectionService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<ClassSection>> {
    const response = await apiClient.get<ApiSuccessResponse<ClassSection[]>>('/class-sections', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<ClassSection> {
    const response = await apiClient.get<ApiSuccessResponse<ClassSection>>(`/class-sections/${id}`)
    return response.data.data
  },
}

export const curriculumService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Curriculum>> {
    const response = await apiClient.get<ApiSuccessResponse<Curriculum[]>>('/curriculums', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Curriculum> {
    const response = await apiClient.get<ApiSuccessResponse<Curriculum>>(`/curriculums/${id}`)
    return response.data.data
  },
}

export const courseService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Course>> {
    const response = await apiClient.get<ApiSuccessResponse<Course[]>>('/courses', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Course> {
    const response = await apiClient.get<ApiSuccessResponse<Course>>(`/courses/${id}`)
    return response.data.data
  },
}

export const krsItemService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<KrsItem>> {
    const response = await apiClient.get<ApiSuccessResponse<KrsItem[]>>('/krs-items', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async enroll(payload: EnrollKrsPayload): Promise<KrsItem> {
    const response = await apiClient.post<ApiSuccessResponse<KrsItem>>('/krs-items', payload)
    return response.data.data
  },

  async drop(id: string): Promise<KrsItem> {
    const response = await apiClient.patch<ApiSuccessResponse<KrsItem>>(`/krs-items/${id}/drop`)
    return response.data.data
  },
}

export const gradeService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Grade>> {
    const response = await apiClient.get<ApiSuccessResponse<Grade[]>>('/grades', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async upsert(krsItemId: string, payload: UpsertGradePayload): Promise<Grade> {
    const response = await apiClient.put<ApiSuccessResponse<Grade>>(`/krs-items/${krsItemId}/grade`, payload)
    return response.data.data
  },
}

export const examService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Exam>> {
    const response = await apiClient.get<ApiSuccessResponse<Exam[]>>('/exams', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Exam> {
    const response = await apiClient.get<ApiSuccessResponse<Exam>>(`/exams/${id}`)
    return response.data.data
  },

  async create(payload: StoreExamPayload): Promise<Exam> {
    const response = await apiClient.post<ApiSuccessResponse<Exam>>('/exams', payload)
    return response.data.data
  },

  async update(id: string, payload: UpdateExamPayload): Promise<Exam> {
    const response = await apiClient.put<ApiSuccessResponse<Exam>>(`/exams/${id}`, payload)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/exams/${id}`)
  },

  async publish(id: string): Promise<Exam> {
    const response = await apiClient.patch<ApiSuccessResponse<Exam>>(`/exams/${id}/publish`)
    return response.data.data
  },

  /** Generate (belum ada link) atau regenerate (link lama langsung tidak valid) akses ujian publik. */
  async generateAccessLink(id: string): Promise<Exam> {
    const response = await apiClient.patch<ApiSuccessResponse<Exam>>(`/exams/${id}/access-link`)
    return response.data.data
  },

  /** Ringkasan + tabel nilai seluruh peserta (spec §7 "Score Recap"). */
  async recap(id: string): Promise<ExamRecap> {
    const response = await apiClient.get<ApiSuccessResponse<ExamRecap>>(`/exams/${id}/recap`)
    return response.data.data
  },

  /** Backend men-stream file mentah (CSV/PDF), diambil sebagai blob lalu dipicu sebagai download browser (pola sama seperti downloadResultPdf). */
  async downloadRecapExport(id: string, format: 'csv' | 'pdf', filename: string): Promise<void> {
    const response = await apiClient.get(`/exams/${id}/recap/export`, { params: { format }, responseType: 'blob' })
    triggerBlobDownload(response.data as Blob, filename)
  },

  /** Naskah ujian (PDF) — `withAnswers` hanya benar-benar disertakan kalau backend mengizinkan (exams.update), lihat ExamController::downloadPdf(). */
  async downloadExamPdf(id: string, withAnswers: boolean, filename: string): Promise<void> {
    const response = await apiClient.get(`/exams/${id}/download`, {
      params: { with_answers: withAnswers ? 1 : 0 },
      responseType: 'blob',
    })
    triggerBlobDownload(response.data as Blob, filename)
  },

  async getGradeRanges(id: string): Promise<ExamGradeRange[]> {
    const response = await apiClient.get<ApiSuccessResponse<ExamGradeRange[]>>(`/exams/${id}/grade-ranges`)
    return response.data.data
  },

  async updateGradeRanges(id: string, ranges: UpsertExamGradeRangePayload[]): Promise<ExamGradeRange[]> {
    const response = await apiClient.put<ApiSuccessResponse<ExamGradeRange[]>>(`/exams/${id}/grade-ranges`, { ranges })
    return response.data.data
  },

  /** Pelanggaran lintas semua peserta sejak `since` (spec §6) — dipoll berkala oleh halaman Detail Ujian. */
  async getRecentViolations(id: string, since: string | null): Promise<ExamViolation[]> {
    const response = await apiClient.get<ApiSuccessResponse<ExamViolation[]>>(`/exams/${id}/violations/recent`, {
      params: since ? { since } : {},
    })
    return response.data.data
  },
}

function triggerBlobDownload(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = filename
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(url)
}

export const examParticipantService = {
  async index(examId: string, params: ListParams = {}): Promise<PaginatedResult<ExamParticipant>> {
    const response = await apiClient.get<ApiSuccessResponse<ExamParticipant[]>>(`/exams/${examId}/participants`, {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },
}

/** Linimasa pelanggaran + ringkasan skor satu percobaan, dilihat dosen (spec §5). */
export const examAttemptViolationService = {
  async show(attemptId: string): Promise<ExamAttemptViolationTimeline> {
    const response = await apiClient.get<ApiSuccessResponse<ExamAttemptViolationTimeline>>(
      `/exam-attempts/${attemptId}/violations`,
    )
    return response.data.data
  },
}

export const examQuestionService = {
  async index(examId: string): Promise<ExamQuestion[]> {
    const response = await apiClient.get<ApiSuccessResponse<ExamQuestion[]>>(`/exams/${examId}/questions`)
    return response.data.data
  },

  async create(examId: string, payload: StoreExamQuestionPayload): Promise<ExamQuestion> {
    const response = await apiClient.post<ApiSuccessResponse<ExamQuestion>>(`/exams/${examId}/questions`, payload)
    return response.data.data
  },

  async update(questionId: string, payload: UpdateExamQuestionPayload): Promise<ExamQuestion> {
    const response = await apiClient.put<ApiSuccessResponse<ExamQuestion>>(`/exam-questions/${questionId}`, payload)
    return response.data.data
  },

  async remove(questionId: string): Promise<void> {
    await apiClient.delete(`/exam-questions/${questionId}`)
  },

  async applyBank(examId: string, questionBankItemIds: string[]): Promise<ExamQuestion[]> {
    const response = await apiClient.post<ApiSuccessResponse<ExamQuestion[]>>(`/exams/${examId}/questions/apply-bank`, {
      question_bank_item_ids: questionBankItemIds,
    })
    return response.data.data
  },
}

export const questionBankService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<QuestionBankItem>> {
    const response = await apiClient.get<ApiSuccessResponse<QuestionBankItem[]>>('/question-bank', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<QuestionBankItem> {
    const response = await apiClient.get<ApiSuccessResponse<QuestionBankItem>>(`/question-bank/${id}`)
    return response.data.data
  },

  async create(payload: StoreQuestionBankItemPayload): Promise<QuestionBankItem> {
    const response = await apiClient.post<ApiSuccessResponse<QuestionBankItem>>('/question-bank', payload)
    return response.data.data
  },

  async update(id: string, payload: UpdateQuestionBankItemPayload): Promise<QuestionBankItem> {
    const response = await apiClient.put<ApiSuccessResponse<QuestionBankItem>>(`/question-bank/${id}`, payload)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/question-bank/${id}`)
  },
}

/**
 * Portal Mahasiswa — self-service mengerjakan ujian sendiri. Terpisah dari
 * `examService`/`examQuestionService` di atas (dipakai dosen/admin
 * mengelola ujian): endpoint di sini tidak pernah menerima/menampilkan
 * `is_correct` atau kunci jawaban, dan backend selalu meresolusi
 * kepemilikan dari user login sendiri (lihat StudentExamController).
 */
export const studentExamService = {
  async index(): Promise<StudentExam[]> {
    const response = await apiClient.get<ApiSuccessResponse<StudentExam[]>>('/student/exams')
    return response.data.data
  },

  async show(examId: string): Promise<StudentExam> {
    const response = await apiClient.get<ApiSuccessResponse<StudentExam>>(`/student/exams/${examId}`)
    return response.data.data
  },

  /** Memulai (atau melanjutkan — idempotent & refresh-safe) percobaan ujian yang sedang berlangsung. */
  async start(examId: string): Promise<StudentExamAttempt> {
    const response = await apiClient.post<ApiSuccessResponse<StudentExamAttempt>>(`/student/exams/${examId}/start`)
    return response.data.data
  },

  async showAttempt(attemptId: string): Promise<StudentExamAttempt> {
    const response = await apiClient.get<ApiSuccessResponse<StudentExamAttempt>>(`/student/exam-attempts/${attemptId}`)
    return response.data.data
  },

  /** Autosave satu jawaban — dipanggil setiap kali peserta memilih/mengubah opsi. */
  async answer(attemptId: string, payload: AnswerExamAttemptPayload): Promise<StudentExamAttempt> {
    const response = await apiClient.put<ApiSuccessResponse<StudentExamAttempt>>(
      `/student/exam-attempts/${attemptId}/answer`,
      payload,
    )
    return response.data.data
  },

  async submit(attemptId: string): Promise<StudentExamAttempt> {
    const response = await apiClient.patch<ApiSuccessResponse<StudentExamAttempt>>(
      `/student/exam-attempts/${attemptId}/submit`,
    )
    return response.data.data
  },

  /** Hasil + koreksi lengkap — hanya tersedia setelah attempt disubmit dan dosen mengizinkan hasil terlihat. */
  async result(attemptId: string): Promise<StudentExamResult> {
    const response = await apiClient.get<ApiSuccessResponse<StudentExamResult>>(
      `/student/exam-attempts/${attemptId}/result`,
    )
    return response.data.data
  },

  /** Backend men-stream PDF mentah (bukan JSON) — diambil sebagai blob lalu dipicu sebagai download browser lewat anchor sementara (pola sama seperti fileUploadService.download). */
  async downloadResultPdf(attemptId: string, filename: string): Promise<void> {
    const response = await apiClient.get(`/student/exam-attempts/${attemptId}/result/pdf`, { responseType: 'blob' })
    triggerBlobDownload(response.data as Blob, filename)
  },

  /** Melaporkan satu pelanggaran terdeteksi (spec §4) — lihat utils/examViolationTracking. */
  async reportViolation(attemptId: string, payload: RecordExamViolationPayload): Promise<StudentExamAttempt> {
    const response = await apiClient.post<ApiSuccessResponse<StudentExamAttempt>>(
      `/student/exam-attempts/${attemptId}/violations`,
      payload,
    )
    return response.data.data
  },
}

export const attendanceService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Attendance>> {
    const response = await apiClient.get<ApiSuccessResponse<Attendance[]>>('/attendances', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async recordBatch(classSectionId: string, payload: RecordAttendanceBatchPayload): Promise<Attendance[]> {
    const response = await apiClient.post<ApiSuccessResponse<Attendance[]>>(
      `/class-sections/${classSectionId}/attendances`,
      payload,
    )
    return response.data.data
  },
}

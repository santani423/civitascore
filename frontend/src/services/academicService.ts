import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type {
  Attendance,
  ClassSection,
  Course,
  Curriculum,
  EnrollKrsPayload,
  Employee,
  Exam,
  ExamQuestion,
  Grade,
  KrsItem,
  Lecturer,
  QuestionBankItem,
  RecordAttendanceBatchPayload,
  Student,
  StoreExamPayload,
  StoreExamQuestionPayload,
  StoreQuestionBankItemPayload,
  StudyProgram,
  Transcript,
  UpdateExamPayload,
  UpdateExamQuestionPayload,
  UpdateQuestionBankItemPayload,
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

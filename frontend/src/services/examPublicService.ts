import axios, { type AxiosError } from 'axios'
import type { ApiSuccessResponse } from '@/types/api'
import type {
  AnswerExamAttemptPayload,
  PublicExamAccessResponse,
  PublicExamInfo,
  RecordExamViolationPayload,
  StudentExamAttempt,
  StudentExamResult,
} from '@/types/academic'
import type { NormalizedApiError } from '@/services/api'

/**
 * Klien API terpisah dari `apiClient` (lihat services/api.ts) — halaman
 * akses ujian publik (/exam/*) tidak pernah login, jadi tidak boleh
 * mengirim Bearer token seorang pengguna yang kebetulan sedang login di
 * browser yang sama, dan 401 di sini tidak berarti "sesi berakhir" (tidak
 * ada sesi sama sekali) sehingga tidak boleh memicu clearSession().
 */
const publicApiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1',
  timeout: 15_000,
  headers: {
    Accept: 'application/json',
  },
})

publicApiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    const normalized: NormalizedApiError = {
      status: error.response?.status ?? null,
      message: error.response?.data?.message ?? 'Terjadi kesalahan. Silakan coba lagi.',
      errors: error.response?.data?.errors,
    }

    return Promise.reject(normalized)
  },
)

export const examPublicService = {
  async getExamAccess(accessToken: string): Promise<PublicExamInfo> {
    const response = await publicApiClient.get<ApiSuccessResponse<PublicExamInfo>>(`/public/exams/${accessToken}`)
    return response.data.data
  },

  async submitNim(accessToken: string, nim: string): Promise<PublicExamAccessResponse> {
    const response = await publicApiClient.post<ApiSuccessResponse<PublicExamAccessResponse>>(
      `/public/exams/${accessToken}/access`,
      { nim },
    )
    return response.data.data
  },

  async getAttempt(sessionToken: string): Promise<StudentExamAttempt> {
    const response = await publicApiClient.get<ApiSuccessResponse<StudentExamAttempt>>(
      `/public/exam-attempts/${sessionToken}`,
    )
    return response.data.data
  },

  async answer(sessionToken: string, payload: AnswerExamAttemptPayload): Promise<StudentExamAttempt> {
    const response = await publicApiClient.put<ApiSuccessResponse<StudentExamAttempt>>(
      `/public/exam-attempts/${sessionToken}/answer`,
      payload,
    )
    return response.data.data
  },

  async reportViolation(sessionToken: string, payload: RecordExamViolationPayload): Promise<StudentExamAttempt> {
    const response = await publicApiClient.post<ApiSuccessResponse<StudentExamAttempt>>(
      `/public/exam-attempts/${sessionToken}/violations`,
      payload,
    )
    return response.data.data
  },

  async submit(sessionToken: string): Promise<StudentExamAttempt> {
    const response = await publicApiClient.patch<ApiSuccessResponse<StudentExamAttempt>>(
      `/public/exam-attempts/${sessionToken}/submit`,
    )
    return response.data.data
  },

  async getResult(sessionToken: string): Promise<StudentExamResult> {
    const response = await publicApiClient.get<ApiSuccessResponse<StudentExamResult>>(
      `/public/exam-attempts/${sessionToken}/result`,
    )
    return response.data.data
  },

  async downloadResultPdf(sessionToken: string, filename: string): Promise<void> {
    const response = await publicApiClient.get(`/public/exam-attempts/${sessionToken}/result/pdf`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(response.data as Blob)

    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = filename
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(url)
  },
}

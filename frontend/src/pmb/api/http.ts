import axios, { AxiosError, type AxiosInstance } from 'axios'
import type { NormalizedApiError } from '@/services/api'
import { apiClient } from '@/services/api'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { resolveCampusCode } from '@/pmb/lib/tenant'

const baseURL = (import.meta.env.VITE_API_BASE_URL as string | undefined) ?? 'http://localhost:8000/api/v1'

type ErrorBody = { message?: string; errors?: Record<string, unknown> }

async function normalize(error: AxiosError<ErrorBody | Blob>): Promise<NormalizedApiError> {
  const raw = error.response?.data
  let body: ErrorBody | undefined

  if (raw instanceof Blob) {
    try {
      body = JSON.parse(await raw.text()) as ErrorBody
    } catch {
      body = undefined
    }
  } else {
    body = raw
  }

  return {
    status: error.response?.status ?? null,
    message: body?.message ?? (error.code === 'ECONNABORTED' ? 'Koneksi lambat. Silakan coba lagi.' : 'Terjadi kesalahan. Silakan coba lagi.'),
    errors: body?.errors as Record<string, string[]> | undefined,
  }
}

function withCampus(instance: AxiosInstance): AxiosInstance {
  instance.interceptors.request.use((config) => {
    const code = resolveCampusCode()

    if (code) config.headers.set('X-University-Code', code)

    return config
  })

  return instance
}

/** Website publik & autentikasi pendaftar (tanpa token). */
export const publicClient = withCampus(axios.create({ baseURL: `${baseURL}/pmb`, timeout: 20_000, headers: { Accept: 'application/json' } }))
publicClient.interceptors.response.use((r) => r, async (error: AxiosError<ErrorBody>) => Promise.reject(await normalize(error)))

/** Portal pendaftar: token ApplicantAccount; 401 → sesi pendaftar dihapus. */
export const applicantClient = withCampus(axios.create({ baseURL: `${baseURL}/pmb/applicant`, timeout: 30_000, headers: { Accept: 'application/json' } }))
applicantClient.interceptors.request.use((config) => {
  const token = useApplicantStore.getState().token

  if (token) config.headers.set('Authorization', `Bearer ${token}`)

  return config
})
applicantClient.interceptors.response.use(
  (r) => r,
  async (error: AxiosError<ErrorBody>) => {
    if (error.response?.status === 401) useApplicantStore.getState().clear()

    return Promise.reject(await normalize(error))
  },
)

/** Admin PMB memakai klien staf yang sudah ada (token, X-University-ID, normalisasi error). */
export const adminClient = apiClient

/** Kunci idempotensi untuk aksi berefek finansial/status (11 §A.5). */
export function idempotencyKey(): string {
  return typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`
}

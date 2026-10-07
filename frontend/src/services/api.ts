import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { useAuthStore } from '@/stores/authStore'
import { useTenantStore } from '@/stores/tenantStore'

/**
 * Centralized API client. Nothing in the app should call axios/fetch
 * directly or hardcode an endpoint URL — every service module should go
 * through this instance so auth headers, timeouts, and error shape stay
 * consistent once real endpoints are wired in.
 */
export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api/v1',
  timeout: 15_000,
  headers: {
    Accept: 'application/json',
  },
})

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = useAuthStore.getState().session?.token

  if (token) {
    config.headers.set('Authorization', `Bearer ${token}`)
  }

  // Cuma terisi lewat Tenant Switcher (Super Admin, lihat stores/tenantStore.ts)
  // — pengguna tenant biasa tidak pernah mengisi ini, backend tetap
  // auto-resolve dari membership default mereka.
  const selectedUniversityId = useTenantStore.getState().selectedUniversity?.id

  if (selectedUniversityId) {
    config.headers.set('X-University-ID', selectedUniversityId)
  }

  return config
})

export interface NormalizedApiError {
  status: number | null
  message: string
  errors?: Record<string, string[]>
}

type ApiErrorBody = { message?: string; errors?: Record<string, string[]> }

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiErrorBody | Blob>) => {
    const status = error.response?.status ?? null

    if (status === 401) {
      useAuthStore.getState().clearSession()
    }

    // Unduhan berkas (`responseType: 'blob'`) yang gagal tetap membawa
    // envelope JSON backend, tetapi terbungkus Blob — dibaca ulang supaya
    // pesan aslinya (mis. "Transkrip belum tersedia...") sampai ke pengguna.
    let body: ApiErrorBody | undefined
    const raw = error.response?.data

    if (raw instanceof Blob) {
      try {
        body = JSON.parse(await raw.text()) as ApiErrorBody
      } catch {
        body = undefined
      }
    } else {
      body = raw
    }

    const normalized: NormalizedApiError = {
      status,
      message: body?.message ?? 'Terjadi kesalahan. Silakan coba lagi.',
      errors: body?.errors,
    }

    return Promise.reject(normalized)
  },
)

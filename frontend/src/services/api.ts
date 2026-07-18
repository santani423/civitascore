import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { useAuthStore } from '@/stores/authStore'

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

  return config
})

export interface NormalizedApiError {
  status: number | null
  message: string
  errors?: Record<string, string[]>
}

apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    const status = error.response?.status ?? null

    if (status === 401) {
      useAuthStore.getState().clearSession()
    }

    const normalized: NormalizedApiError = {
      status,
      message: error.response?.data?.message ?? 'Terjadi kesalahan. Silakan coba lagi.',
      errors: error.response?.data?.errors,
    }

    return Promise.reject(normalized)
  },
)

import type { AuthSession, AuthUser, LoginCredentials } from '@/types/auth'
import type { NormalizedApiError } from '@/services/api'

/**
 * Fase ini: login/me/logout disimulasikan secara lokal (belum ada modul
 * bisnis di backend yang butuh sesi nyata). Setiap fungsi di bawah sudah
 * berbentuk seperti pemanggilan API asli (async, melempar NormalizedApiError
 * yang sama dengan interceptor di api.ts) — pada integrasi nyata nanti,
 * hanya isi fungsi yang diganti dengan apiClient.post/get, signature dan
 * pemanggil (authStore, LoginPage) tidak perlu berubah.
 *
 * Endpoint asli yang akan dipakai:
 *   POST /api/v1/auth/login
 *   GET  /api/v1/auth/me
 *   POST /api/v1/auth/logout
 */

const DEMO_ACCOUNT = { email: 'admin@demo.test', password: 'password' }

const MOCK_USER: AuthUser = {
  id: 'usr-demo-1',
  name: 'Admin Demo',
  email: DEMO_ACCOUNT.email,
  role: 'Super Admin',
  avatarUrl: null,
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

function unauthorized(message: string): NormalizedApiError {
  return { status: 401, message }
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<AuthSession> {
    // return apiClient.post<ApiSuccessResponse<AuthSession>>('/auth/login', credentials).then((r) => r.data.data)
    await delay(600)

    if (credentials.email !== DEMO_ACCOUNT.email || credentials.password !== DEMO_ACCOUNT.password) {
      throw unauthorized('Email atau kata sandi salah.')
    }

    return { token: `demo-token-${Date.now()}`, user: MOCK_USER }
  },

  async getCurrentUser(): Promise<AuthUser> {
    // return apiClient.get<ApiSuccessResponse<AuthUser>>('/auth/me').then((r) => r.data.data)
    await delay(200)

    return MOCK_USER
  },

  async logout(): Promise<void> {
    // return apiClient.post('/auth/logout').then(() => undefined)
    await delay(200)
  },
}

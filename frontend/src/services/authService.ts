import { apiClient } from '@/services/api'
import type { ApiSuccessResponse } from '@/types/api'
import type {
  AuthSession,
  AuthUser,
  LoginCredentials,
  UserDevice,
  UserSession,
} from '@/types/auth'
import { humanizeSlug } from '@/utils/formatters'

interface MeResponse {
  user: { id: string; name: string; email: string }
  roles: string[]
  permissions: string[]
}

function buildAuthUser(user: MeResponse['user'], roles: string[], permissions: string[]): AuthUser {
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    role: roles[0] ? humanizeSlug(roles[0]) : 'Pengguna',
    roles,
    permissions,
    avatarUrl: null,
  }
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<AuthSession> {
    const loginResponse = await apiClient.post<ApiSuccessResponse<{ token: string; user: MeResponse['user'] }>>(
      '/login',
      { email: credentials.email, password: credentials.password },
    )
    const { token } = loginResponse.data.data

    const meResponse = await apiClient.get<ApiSuccessResponse<MeResponse>>('/me', {
      headers: { Authorization: `Bearer ${token}` },
    })
    const { user, roles, permissions } = meResponse.data.data

    return { token, user: buildAuthUser(user, roles, permissions) }
  },

  async getCurrentUser(): Promise<AuthUser> {
    const response = await apiClient.get<ApiSuccessResponse<MeResponse>>('/me')
    const { user, roles, permissions } = response.data.data

    return buildAuthUser(user, roles, permissions)
  },

  async logout(): Promise<void> {
    await apiClient.post('/logout')
  },

  async logoutAllDevices(): Promise<void> {
    await apiClient.post('/logout-all')
  },

  async forgotPassword(email: string): Promise<void> {
    await apiClient.post('/forgot-password', { email })
  },

  async resetPassword(payload: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    await apiClient.post('/reset-password', payload)
  },

  async getSessions(): Promise<UserSession[]> {
    const response = await apiClient.get<ApiSuccessResponse<UserSession[]>>('/sessions')
    return response.data.data
  },

  async revokeSession(sessionId: string): Promise<void> {
    await apiClient.delete(`/sessions/${sessionId}`)
  },

  async getDevices(): Promise<UserDevice[]> {
    const response = await apiClient.get<ApiSuccessResponse<UserDevice[]>>('/devices')
    return response.data.data
  },
}

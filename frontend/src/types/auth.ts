export interface AuthUser {
  id: string
  name: string
  email: string
  /** Label tampilan tunggal, diturunkan dari roles[0] (lihat authService.login). */
  role: string
  roles: string[]
  permissions: string[]
  avatarUrl?: string | null
}

export interface LoginCredentials {
  email: string
  password: string
  remember: boolean
}

export interface AuthSession {
  user: AuthUser
  token: string
}

export interface UserSessionDevice {
  id: string
  device_name: string | null
  platform: string | null
}

export interface UserSession {
  id: string
  device: UserSessionDevice | null
  ip_address: string | null
  user_agent: string | null
  last_activity_at: string | null
  is_active: boolean
  revoked_at: string | null
  created_at: string | null
}

export type DeviceType = 'web' | 'mobile' | 'desktop' | 'unknown'

export interface UserDevice {
  id: string
  device_name: string | null
  device_type: DeviceType
  platform: string | null
  is_trusted: boolean
  last_used_at: string | null
}

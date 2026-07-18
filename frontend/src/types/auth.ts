export interface AuthUser {
  id: string
  name: string
  email: string
  role: string
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

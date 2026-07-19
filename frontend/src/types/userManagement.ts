export type PermissionScope = 'menu' | 'module' | 'endpoint' | 'data'

export type PermissionAction =
  | 'create'
  | 'read'
  | 'update'
  | 'delete'
  | 'approve'
  | 'reject'
  | 'export'
  | 'import'
  | 'publish'
  | 'finalize'

export interface Permission {
  id: string
  name: string
  slug: string
  scope: PermissionScope
  action: PermissionAction
  resource: string
  description: string | null
  is_system: boolean
  created_at: string | null
}

export interface Role {
  id: string
  name: string
  slug: string
  description: string | null
  is_system: boolean
  permissions: Permission[]
  created_at: string | null
}

export interface UserSummary {
  id: string
  name: string
  email: string
  is_active: boolean
}

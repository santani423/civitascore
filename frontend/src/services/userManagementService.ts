import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Permission, Role, UserSummary } from '@/types/userManagement'
import { toQueryParams } from '@/utils/listParams'

export interface RolePayload {
  name: string
  description?: string | null
}

export interface PermissionPayload {
  name: string
  resource: string
  scope: Permission['scope']
  action: Permission['action']
  description?: string | null
}

export const roleService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Role>> {
    const response = await apiClient.get<ApiSuccessResponse<Role[]>>('/roles', { params: toQueryParams(params) })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Role> {
    const response = await apiClient.get<ApiSuccessResponse<Role>>(`/roles/${id}`)
    return response.data.data
  },

  async create(payload: RolePayload): Promise<Role> {
    const response = await apiClient.post<ApiSuccessResponse<Role>>('/roles', payload)
    return response.data.data
  },

  async update(id: string, payload: Partial<RolePayload>): Promise<Role> {
    const response = await apiClient.put<ApiSuccessResponse<Role>>(`/roles/${id}`, payload)
    return response.data.data
  },

  async destroy(id: string): Promise<void> {
    await apiClient.delete(`/roles/${id}`)
  },

  async syncPermissions(id: string, permissionIds: string[]): Promise<Role> {
    const response = await apiClient.put<ApiSuccessResponse<Role>>(`/roles/${id}/permissions`, {
      permission_ids: permissionIds,
    })
    return response.data.data
  },
}

export const permissionService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Permission>> {
    const response = await apiClient.get<ApiSuccessResponse<Permission[]>>('/permissions', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async create(payload: PermissionPayload): Promise<Permission> {
    const response = await apiClient.post<ApiSuccessResponse<Permission>>('/permissions', payload)
    return response.data.data
  },

  async update(id: string, payload: { name?: string; description?: string | null }): Promise<Permission> {
    const response = await apiClient.put<ApiSuccessResponse<Permission>>(`/permissions/${id}`, payload)
    return response.data.data
  },

  async destroy(id: string): Promise<void> {
    await apiClient.delete(`/permissions/${id}`)
  },
}

export const userService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<UserSummary>> {
    const response = await apiClient.get<ApiSuccessResponse<UserSummary[]>>('/users', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },
}

export const userRoleService = {
  async index(userId: string): Promise<Role[]> {
    const response = await apiClient.get<ApiSuccessResponse<Role[]>>(`/users/${userId}/roles`)
    return response.data.data
  },

  async assign(userId: string, roleId: string, expiresAt?: string | null): Promise<Role[]> {
    const response = await apiClient.post<ApiSuccessResponse<Role[]>>(`/users/${userId}/roles`, {
      role_id: roleId,
      expires_at: expiresAt ?? undefined,
    })
    return response.data.data
  },

  async revoke(userId: string, roleId: string): Promise<void> {
    await apiClient.delete(`/users/${userId}/roles/${roleId}`)
  },
}

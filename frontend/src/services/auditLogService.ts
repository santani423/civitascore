import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { AuditLogEntry } from '@/types/auditLog'
import { toQueryParams } from '@/utils/listParams'

export const auditLogService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<AuditLogEntry>> {
    const response = await apiClient.get<ApiSuccessResponse<AuditLogEntry[]>>('/audit-logs', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<AuditLogEntry> {
    const response = await apiClient.get<ApiSuccessResponse<AuditLogEntry>>(`/audit-logs/${id}`)
    return response.data.data
  },
}

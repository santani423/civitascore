import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { PlatformStatistics, SupportSession, University } from '@/types/tenancy'
import { toQueryParams } from '@/utils/listParams'

export interface UniversityPayload {
  code: string
  name: string
  short_name?: string | null
  legal_name?: string | null
  education_institution_type?: string | null
  accreditation?: string | null
  email?: string | null
  phone?: string | null
  website?: string | null
  timezone?: string
  locale?: string
  currency?: string
  primary_color?: string | null
  secondary_color?: string | null
}

export const universityService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<University>> {
    const response = await apiClient.get<ApiSuccessResponse<University[]>>('/platform/universities', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<University> {
    const response = await apiClient.get<ApiSuccessResponse<University>>(`/platform/universities/${id}`)
    return response.data.data
  },

  async create(payload: UniversityPayload): Promise<University> {
    const response = await apiClient.post<ApiSuccessResponse<University>>('/platform/universities', payload)
    return response.data.data
  },

  async update(id: string, payload: Partial<UniversityPayload>): Promise<University> {
    const response = await apiClient.patch<ApiSuccessResponse<University>>(`/platform/universities/${id}`, payload)
    return response.data.data
  },

  async activate(id: string): Promise<University> {
    const response = await apiClient.post<ApiSuccessResponse<University>>(`/platform/universities/${id}/activate`)
    return response.data.data
  },

  async suspend(id: string): Promise<University> {
    const response = await apiClient.post<ApiSuccessResponse<University>>(`/platform/universities/${id}/suspend`)
    return response.data.data
  },
}

export const platformStatisticsService = {
  async index(): Promise<PlatformStatistics> {
    const response = await apiClient.get<ApiSuccessResponse<PlatformStatistics>>('/platform/statistics')
    return response.data.data
  },
}

export const supportSessionService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<SupportSession>> {
    const response = await apiClient.get<ApiSuccessResponse<SupportSession[]>>('/platform/support-sessions', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async start(universityId: string, reason: string): Promise<SupportSession> {
    const response = await apiClient.post<ApiSuccessResponse<SupportSession>>('/platform/support-sessions', {
      university_id: universityId,
      reason,
    })
    return response.data.data
  },

  async end(id: string): Promise<SupportSession> {
    const response = await apiClient.post<ApiSuccessResponse<SupportSession>>(`/platform/support-sessions/${id}/end`)
    return response.data.data
  },
}

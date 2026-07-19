import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { FeatureFlag, SystemSetting } from '@/types/systemSetting'
import { toQueryParams } from '@/utils/listParams'

export const systemSettingService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<SystemSetting>> {
    const response = await apiClient.get<ApiSuccessResponse<SystemSetting[]>>('/system-settings', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async update(id: string, value: string): Promise<SystemSetting> {
    const response = await apiClient.put<ApiSuccessResponse<SystemSetting>>(`/system-settings/${id}`, { value })
    return response.data.data
  },
}

export const featureFlagService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<FeatureFlag>> {
    const response = await apiClient.get<ApiSuccessResponse<FeatureFlag[]>>('/feature-flags', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async update(id: string, isEnabled: boolean): Promise<FeatureFlag> {
    const response = await apiClient.put<ApiSuccessResponse<FeatureFlag>>(`/feature-flags/${id}`, {
      is_enabled: isEnabled,
    })
    return response.data.data
  },
}

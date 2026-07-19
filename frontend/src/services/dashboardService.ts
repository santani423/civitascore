import { apiClient } from '@/services/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { DashboardQueryFilters, DashboardResponse } from '@/types/dashboard'

export const dashboardService = {
  async getStats(filters: DashboardQueryFilters = {}): Promise<DashboardResponse> {
    const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => Boolean(value)))
    const response = await apiClient.get<ApiSuccessResponse<DashboardResponse>>('/dashboard', { params })
    return response.data.data
  },
}

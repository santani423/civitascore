import { apiClient } from '@/services/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { AcademicCalendarEvent, DashboardQueryFilters, DashboardResponse } from '@/types/dashboard'

export const dashboardService = {
  async getStats(filters: DashboardQueryFilters = {}): Promise<DashboardResponse> {
    const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => Boolean(value)))
    const response = await apiClient.get<ApiSuccessResponse<DashboardResponse>>('/dashboard', { params })
    return response.data.data
  },

  /** Agenda kalender akademik yang berakhir pada/after `from` dan dimulai pada/before `to` (YYYY-MM-DD). */
  async getAcademicAgenda(from: string, to: string): Promise<AcademicCalendarEvent[]> {
    const response = await apiClient.get<ApiSuccessResponse<AcademicCalendarEvent[]>>('/academic-calendar-events', {
      params: { from, to },
    })
    return response.data.data
  },
}

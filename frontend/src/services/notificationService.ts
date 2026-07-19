import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type {
  NotificationChannel,
  NotificationChannelConfig,
  NotificationTemplate,
  UserNotificationPreference,
} from '@/types/notification'
import { toQueryParams } from '@/utils/listParams'

export interface NotificationTemplatePayload {
  event_key: string
  name: string
  channel: NotificationChannel
  subject?: string | null
  body_template: string
  is_active?: boolean
}

export const notificationTemplateService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<NotificationTemplate>> {
    const response = await apiClient.get<ApiSuccessResponse<NotificationTemplate[]>>('/notification-templates', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async create(payload: NotificationTemplatePayload): Promise<NotificationTemplate> {
    const response = await apiClient.post<ApiSuccessResponse<NotificationTemplate>>(
      '/notification-templates',
      payload,
    )
    return response.data.data
  },

  async update(id: string, payload: Partial<NotificationTemplatePayload>): Promise<NotificationTemplate> {
    const response = await apiClient.put<ApiSuccessResponse<NotificationTemplate>>(
      `/notification-templates/${id}`,
      payload,
    )
    return response.data.data
  },
}

export const notificationChannelService = {
  async index(): Promise<NotificationChannelConfig[]> {
    const response = await apiClient.get<ApiSuccessResponse<NotificationChannelConfig[]>>('/notification-channels')
    return response.data.data
  },

  async update(id: string, isEnabled: boolean): Promise<NotificationChannelConfig> {
    const response = await apiClient.put<ApiSuccessResponse<NotificationChannelConfig>>(
      `/notification-channels/${id}`,
      { is_enabled: isEnabled },
    )
    return response.data.data
  },
}

export const notificationPreferenceService = {
  async index(): Promise<UserNotificationPreference[]> {
    const response = await apiClient.get<ApiSuccessResponse<UserNotificationPreference[]>>(
      '/notification-preferences',
    )
    return response.data.data
  },

  async upsert(payload: {
    channel: NotificationChannel
    notification_type?: string
    is_enabled: boolean
  }): Promise<UserNotificationPreference> {
    const response = await apiClient.post<ApiSuccessResponse<UserNotificationPreference>>(
      '/notification-preferences',
      payload,
    )
    return response.data.data
  },
}

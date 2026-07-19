export type NotificationChannel = 'database' | 'mail' | 'push' | 'whatsapp' | 'sms'

export interface NotificationTemplate {
  id: string
  event_key: string
  name: string
  channel: NotificationChannel
  subject: string | null
  body_template: string
  is_active: boolean
  created_at: string | null
}

export interface NotificationChannelConfig {
  id: string
  code: NotificationChannel
  name: string
  is_enabled: boolean
}

export interface UserNotificationPreference {
  id: string
  channel: NotificationChannel
  notification_type: string
  is_enabled: boolean
}

export type SettingValueType = 'string' | 'integer' | 'boolean' | 'json'

export interface SystemSetting {
  id: string
  key: string
  value: string | number | boolean | Record<string, unknown> | null
  type: SettingValueType
  group: string | null
  description: string | null
  is_public: boolean
  updated_at: string | null
}

export interface FeatureFlag {
  id: string
  key: string
  name: string
  description: string | null
  is_enabled: boolean
  updated_at: string | null
}

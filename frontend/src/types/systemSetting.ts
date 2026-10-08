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
  /** Yang diubah oleh update(): override universitas aktif, atau nilai global (konteks platform). */
  scope: 'university' | 'global'
  /** Nilai efektif: override universitas aktif bila ada, selain itu nilai global. */
  is_enabled: boolean
  /** Nilai global (default semua universitas). */
  default_enabled: boolean
  /** null = universitas mengikuti nilai global (selalu null di konteks platform). */
  university_override: boolean | null
  updated_at: string | null
}

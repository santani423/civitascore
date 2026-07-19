export type UniversityStatus = 'draft' | 'trial' | 'active' | 'suspended' | 'expired' | 'terminated'

export interface UniversityActiveSubscription {
  plan: string | null
  status: string
  current_period_ends_at: string | null
}

export interface University {
  id: string
  code: string
  slug: string
  name: string
  short_name: string | null
  legal_name: string | null
  education_institution_type: string | null
  accreditation: string | null
  email: string | null
  phone: string | null
  website: string | null
  status: UniversityStatus
  timezone: string
  locale: string
  currency: string
  date_format: string
  primary_color: string | null
  secondary_color: string | null
  is_active: boolean
  activated_at: string | null
  suspended_at: string | null
  domains?: string[]
  active_subscription?: UniversityActiveSubscription | null
  created_at: string | null
}

export interface PlatformStatistics {
  universities_total: number
  universities_active: number
  universities_by_status: Record<string, number>
  users_total: number
  memberships_total: number
}

export interface SupportSession {
  id: string
  super_admin_id: string
  super_admin_name: string | null
  university_id: string
  university_name: string | null
  reason: string
  started_at: string | null
  ended_at: string | null
  ip_address: string | null
  created_at: string | null
}

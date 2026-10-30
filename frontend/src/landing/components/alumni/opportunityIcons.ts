import { Briefcase, GraduationCap, Handshake, HeartHandshake, Sprout, TrendingUp, type LucideIcon } from 'lucide-react'
import type { OpportunityType } from '../../data/alumni'

export const OPPORTUNITY_ICON: Record<OpportunityType, LucideIcon> = {
  job: Briefcase,
  internship: GraduationCap,
  trainee: Sprout,
  development: TrendingUp,
  partnership: Handshake,
  mentoring: HeartHandshake,
}

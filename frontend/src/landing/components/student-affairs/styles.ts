import {
  Building2,
  CalendarClock,
  CheckCircle2,
  Church,
  CircleDot,
  Cpu,
  Drama,
  Flag,
  GraduationCap,
  HandHeart,
  Landmark,
  MessagesSquare,
  Music,
  Newspaper,
  Palette,
  Scale,
  Trophy,
  UsersRound,
  Volleyball,
  type LucideIcon,
} from 'lucide-react'
import type { ActivityCategory, ActivityStatus, Org, OrmawaCategory, UkmCategory } from '../../data/student-affairs'

/** Cover/monogram surfaces, all from the landing tokens (so dark and high-contrast modes follow). */
export type Tone = 'band' | 'deep' | 'brand' | 'accent' | 'gold' | 'soft' | 'sunken'

export const TONE: Record<Tone, { surface: string; ink: string; ring: string }> = {
  band: { surface: 'bg-lp-band', ink: 'text-lp-accent', ring: 'border-white/10' },
  deep: { surface: 'bg-lp-band-elev', ink: 'text-lp-gold', ring: 'border-white/10' },
  brand: { surface: 'bg-lp-brand', ink: 'text-white', ring: 'border-white/15' },
  accent: { surface: 'bg-lp-accent', ink: 'text-white', ring: 'border-white/20' },
  gold: { surface: 'bg-lp-gold', ink: 'text-white', ring: 'border-white/20' },
  soft: { surface: 'bg-lp-brand-soft', ink: 'text-lp-brand-text', ring: 'border-lp-accent/15' },
  sunken: { surface: 'bg-lp-sunken', ink: 'text-lp-gold-text', ring: 'border-lp-gold/20' },
}

export interface CategoryStyle {
  icon: LucideIcon
  tone: Tone
}

export const ACTIVITY_STYLE: Record<ActivityCategory, CategoryStyle> = {
  akademik: { icon: GraduationCap, tone: 'band' },
  kompetisi: { icon: Trophy, tone: 'brand' },
  kepemimpinan: { icon: Flag, tone: 'deep' },
  'seni-budaya': { icon: Palette, tone: 'gold' },
  olahraga: { icon: Volleyball, tone: 'accent' },
  pengabdian: { icon: HandHeart, tone: 'sunken' },
  kerohanian: { icon: Church, tone: 'soft' },
}

const ORMAWA_STYLE: Record<OrmawaCategory, CategoryStyle> = {
  eksekutif: { icon: Landmark, tone: 'band' },
  legislatif: { icon: Scale, tone: 'deep' },
  fakultas: { icon: Building2, tone: 'brand' },
  himpunan: { icon: UsersRound, tone: 'soft' },
  lainnya: { icon: Newspaper, tone: 'sunken' },
}

const UKM_STYLE: Record<UkmCategory, CategoryStyle> = {
  olahraga: { icon: Volleyball, tone: 'accent' },
  seni: { icon: Palette, tone: 'gold' },
  musik: { icon: Music, tone: 'brand' },
  budaya: { icon: Drama, tone: 'deep' },
  teknologi: { icon: Cpu, tone: 'band' },
  penalaran: { icon: MessagesSquare, tone: 'sunken' },
  sosial: { icon: HandHeart, tone: 'soft' },
  kerohanian: { icon: Church, tone: 'soft' },
}

export function orgStyle(org: Org): CategoryStyle {
  return org.kind === 'ormawa' ? ORMAWA_STYLE[org.category] : UKM_STYLE[org.category]
}

/** Each status has its own icon and text, so it never depends on color alone. */
export const STATUS_STYLE: Record<ActivityStatus, { icon: LucideIcon; className: string }> = {
  upcoming: { icon: CalendarClock, className: 'text-lp-info ring-lp-info/30' },
  ongoing: { icon: CircleDot, className: 'text-lp-ok ring-lp-ok/30' },
  completed: { icon: CheckCircle2, className: 'text-lp-muted ring-lp-strong' },
}

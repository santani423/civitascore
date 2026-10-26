import { CalendarDays, CalendarRange, Landmark, School, Shapes, UsersRound, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import type { NavChildKey } from '../../data/navigation'

export const NAV_PAGE_ICONS: Record<NavChildKey, LucideIcon> = {
  faculties: School,
  academicCycle: CalendarRange,
  studentLife: Landmark,
  activities: CalendarDays,
  ormawa: UsersRound,
  ukm: Shapes,
}

/** Desktop navbar item styling, shared by section links and the group button. */
export function navLinkClass(active: boolean): string {
  return cn(
    // px-2.5: seven labels (two with a chevron) fit next to the wordmark from 1280px.
    'relative inline-flex min-h-16 items-center gap-0.5 whitespace-nowrap px-2.5 text-[0.9063rem] font-semibold transition-colors lg:min-h-[4.5rem]',
    active ? 'text-lp-brand-text' : 'text-lp-muted hover:text-lp-fg',
  )
}

/** Mobile menu row styling, shared by section links and the group's page links. */
export function mobileLinkClass(active: boolean): string {
  return cn(
    'flex h-12 items-center justify-between gap-3 rounded-lp-sm border-l-[3px] px-3 text-[1rem] font-semibold transition-colors',
    active ? 'border-lp-brand bg-lp-brand-soft text-lp-brand-text' : 'border-transparent text-lp-muted hover:bg-lp-sunken hover:text-lp-fg',
  )
}

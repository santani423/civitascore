import { LANDING_PATHS, SECTION_IDS, type LandingPath, type SectionId } from '../paths'

export type NavKey = 'home' | 'about' | 'services' | 'academic' | 'studentLife' | 'info'

/** A home-page section: `target` is scrolled to; `covers` are the sections that mark the item active. */
interface SectionNavItem {
  key: NavKey
  target: SectionId
  covers: SectionId[]
}

/** A page of its own, active while it is open. */
interface PageNavItem {
  key: NavKey
  path: LandingPath
}

export type NavItem = SectionNavItem | PageNavItem

/** Navbar items, in home-page order (the Student Life Journey teaser sits between Akademik and Informasi). */
export const NAV_ITEMS: NavItem[] = [
  { key: 'home', target: SECTION_IDS.home, covers: [SECTION_IDS.home] },
  { key: 'about', target: SECTION_IDS.about, covers: [SECTION_IDS.about, SECTION_IDS.integration] },
  { key: 'services', target: SECTION_IDS.services, covers: [SECTION_IDS.services] },
  { key: 'academic', target: SECTION_IDS.academic, covers: [SECTION_IDS.academic] },
  { key: 'studentLife', path: LANDING_PATHS.studentLife },
  { key: 'info', target: SECTION_IDS.info, covers: [SECTION_IDS.info] },
]

export const SPY_SECTIONS: SectionId[] = NAV_ITEMS.flatMap((item) => ('covers' in item ? item.covers : []))

/** `aria-current` for a nav item, given the current path and (on the home page) the section in view. */
export function navCurrent(item: NavItem, path: string, activeSection: string | null): 'page' | 'location' | undefined {
  if ('path' in item) return item.path === path ? 'page' : undefined
  return path === LANDING_PATHS.home && item.covers.some((c) => c === activeSection) ? 'location' : undefined
}

import { LANDING_PATHS, SECTION_IDS, type LandingPath, type SectionId } from '../paths'

export type NavKey = 'home' | 'about' | 'services' | 'academic' | 'studentAffairs' | 'info'
/** Items inside a group. */
export type NavChildKey = 'faculties' | 'academicCycle' | 'studentLife' | 'activities' | 'ormawa' | 'ukm'

/** A home-page section: `target` is scrolled to; `covers` are the sections that mark the item active. */
export interface SectionNavItem {
  key: NavKey
  target: SectionId
  covers: SectionId[]
}

/** A page of its own; its detail pages (`path/:slug`) count as inside it. */
export interface PageNavItem {
  key: NavChildKey
  path: LandingPath
}

/** A home-page section listed inside a group. */
export interface SectionChildItem {
  key: NavChildKey
  target: SectionId
  covers: SectionId[]
}

export type GroupChild = PageNavItem | SectionChildItem

/** A dropdown (desktop) / sub-list (mobile, footer). */
export interface GroupNavItem {
  key: NavKey
  items: GroupChild[]
}

export type NavItem = SectionNavItem | GroupNavItem

export const ACADEMIC_ITEMS: GroupChild[] = [
  { key: 'faculties', path: LANDING_PATHS.faculties },
  { key: 'academicCycle', target: SECTION_IDS.academic, covers: [SECTION_IDS.academic] },
]

export const STUDENT_AFFAIRS_PAGES: PageNavItem[] = [
  { key: 'studentLife', path: LANDING_PATHS.studentLife },
  { key: 'activities', path: LANDING_PATHS.activities },
  { key: 'ormawa', path: LANDING_PATHS.ormawa },
  { key: 'ukm', path: LANDING_PATHS.ukm },
]

/** Navbar items, in home-page order (the Student Life Journey teaser sits between Akademik and Informasi). */
export const NAV_ITEMS: NavItem[] = [
  { key: 'home', target: SECTION_IDS.home, covers: [SECTION_IDS.home] },
  { key: 'about', target: SECTION_IDS.about, covers: [SECTION_IDS.about, SECTION_IDS.integration] },
  { key: 'services', target: SECTION_IDS.services, covers: [SECTION_IDS.services] },
  { key: 'academic', items: ACADEMIC_ITEMS },
  { key: 'studentAffairs', items: STUDENT_AFFAIRS_PAGES },
  { key: 'info', target: SECTION_IDS.info, covers: [SECTION_IDS.info] },
]

/** Every home-page section the navigation reaches, top-level or inside a group, in page order (the footer lists these). */
export const HOME_SECTION_ITEMS = NAV_ITEMS.flatMap((item): Array<SectionNavItem | SectionChildItem> =>
  'items' in item ? item.items.filter((child): child is SectionChildItem => 'target' in child) : [item],
)

export const SPY_SECTIONS: SectionId[] = HOME_SECTION_ITEMS.flatMap((item) => item.covers)

/** `page` on the page itself, `true` on one of its detail pages. */
export function pageCurrent(item: PageNavItem, path: string): 'page' | 'true' | undefined {
  if (path === item.path) return 'page'
  return path.startsWith(`${item.path}/`) ? 'true' : undefined
}

function sectionCurrent(item: SectionNavItem | SectionChildItem, path: string, activeSection: string | null): 'location' | undefined {
  return path === LANDING_PATHS.home && item.covers.some((c) => c === activeSection) ? 'location' : undefined
}

/** `aria-current` of an item inside a group. */
export function childCurrent(child: GroupChild, path: string, activeSection: string | null): 'page' | 'true' | 'location' | undefined {
  return 'path' in child ? pageCurrent(child, path) : sectionCurrent(child, path, activeSection)
}

/** Whether a nav item is the current place, given the path and (on the home page) the section in view. */
export function isNavActive(item: NavItem, path: string, activeSection: string | null): boolean {
  if ('items' in item) return item.items.some((child) => childCurrent(child, path, activeSection) !== undefined)
  return sectionCurrent(item, path, activeSection) !== undefined
}

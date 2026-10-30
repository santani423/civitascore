import { SECTION_IDS, type SectionId } from '../paths'

export type NavKey = 'home' | 'about' | 'services' | 'academic' | 'info'

/**
 * Navbar items, in page order: `target` is scrolled to; `covers` are the
 * sections that mark the item active.
 */
export const NAV_ITEMS: Array<{ key: NavKey; target: SectionId; covers: SectionId[] }> = [
  { key: 'home', target: SECTION_IDS.home, covers: [SECTION_IDS.home] },
  { key: 'about', target: SECTION_IDS.about, covers: [SECTION_IDS.about, SECTION_IDS.integration] },
  { key: 'services', target: SECTION_IDS.services, covers: [SECTION_IDS.services] },
  { key: 'academic', target: SECTION_IDS.academic, covers: [SECTION_IDS.academic] },
  { key: 'info', target: SECTION_IDS.info, covers: [SECTION_IDS.info, SECTION_IDS.tuition] },
]

export const SPY_SECTIONS: SectionId[] = NAV_ITEMS.flatMap((item) => item.covers)

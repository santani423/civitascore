import type { SectionId } from '../paths'

export type NavKey = 'home' | 'platform' | 'modules' | 'benefits' | 'about' | 'faq'

/** Navbar items: `target` is scrolled to; `covers` are the sections that mark the item active. */
export const NAV_ITEMS: Array<{ key: NavKey; target: SectionId; covers: SectionId[] }> = [
  { key: 'home', target: 'home', covers: ['home'] },
  { key: 'platform', target: 'platform', covers: ['platform', 'demo'] },
  { key: 'modules', target: 'modules', covers: ['modules', 'personas'] },
  { key: 'benefits', target: 'benefits', covers: ['benefits', 'governance'] },
  { key: 'about', target: 'about', covers: ['about', 'journey'] },
  { key: 'faq', target: 'faq', covers: ['faq'] },
]

export const SPY_SECTIONS: SectionId[] = NAV_ITEMS.flatMap((item) => item.covers)

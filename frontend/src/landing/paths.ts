/**
 * Public marketing routes. Kept dependency-free: main.tsx imports this to
 * decide whether to boot the lightweight landing bundle or the full app.
 */
export const LANDING_PATHS = {
  home: '/',
  privacy: '/kebijakan-privasi',
  terms: '/syarat-layanan',
} as const

export type LandingPath = (typeof LANDING_PATHS)[keyof typeof LANDING_PATHS]

const ALL: string[] = Object.values(LANDING_PATHS)

export function isLandingPath(pathname: string): boolean {
  const normalized = pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname
  return ALL.includes(normalized)
}

/** In-page section anchors (language-neutral ids). */
export const SECTION_IDS = {
  home: 'home',
  platform: 'platform',
  modules: 'modules',
  demo: 'demo',
  personas: 'personas',
  benefits: 'benefits',
  governance: 'governance',
  about: 'about',
  journey: 'journey',
  faq: 'faq',
} as const

export type SectionId = (typeof SECTION_IDS)[keyof typeof SECTION_IDS]

/**
 * Public marketing routes. Kept dependency-free: main.tsx imports this to
 * decide whether to boot the lightweight landing bundle or the full app.
 */
export const LANDING_PATHS = {
  home: '/',
  studentLife: '/student-life-journey',
  privacy: '/kebijakan-privasi',
  terms: '/syarat-layanan',
} as const

export type LandingPath = (typeof LANDING_PATHS)[keyof typeof LANDING_PATHS]

const ALL: string[] = Object.values(LANDING_PATHS)

/** `/student-life-journey/` and `/student-life-journey` are the same page. */
export function normalizePath(pathname: string): string {
  return pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname
}

export function isLandingPath(pathname: string): boolean {
  return ALL.includes(normalizePath(pathname))
}

/** In-page section anchors (language-neutral ids — the same in every locale). */
export const SECTION_IDS = {
  home: 'beranda',
  about: 'tentang',
  integration: 'integrasi',
  services: 'layanan',
  academic: 'akademik',
  info: 'informasi',
} as const

export type SectionId = (typeof SECTION_IDS)[keyof typeof SECTION_IDS]

/** Anchors on /student-life-journey besides the pillars themselves (those use the pillar slug, data/pillars.ts). */
export const STUDENT_LIFE_IDS = {
  hero: 'sekilas',
  activities: 'kegiatan',
  contact: 'kontak-bkk',
} as const

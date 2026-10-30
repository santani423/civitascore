/**
 * Public marketing routes. Kept dependency-free: main.tsx imports this to
 * decide whether to boot the lightweight landing bundle or the full app.
 */
export const LANDING_PATHS = {
  home: '/',
  studentLife: '/student-life-journey',
  activities: '/kegiatan',
  ormawa: '/daftar-ormawa',
  ukm: '/daftar-ukm',
  faculties: '/fakultas',
  // Not `/alumni`: that path belongs to the staff Alumni module (constants/routes.ts).
  alumni: '/komunitas-alumni',
  alumniDirectory: '/komunitas-alumni/direktori',
  alumniEvents: '/komunitas-alumni/kegiatan',
  alumniCareers: '/komunitas-alumni/karier',
  alumniStories: '/komunitas-alumni/kisah',
  alumniContributions: '/komunitas-alumni/kontribusi',
  privacy: '/kebijakan-privasi',
  terms: '/syarat-layanan',
} as const

export type LandingPath = (typeof LANDING_PATHS)[keyof typeof LANDING_PATHS]

const ALL: string[] = Object.values(LANDING_PATHS)

/** List pages whose items each have a detail page at `<list>/:slug`. */
export const DETAIL_PARENTS = [
  LANDING_PATHS.activities,
  LANDING_PATHS.ormawa,
  LANDING_PATHS.ukm,
  LANDING_PATHS.faculties,
  LANDING_PATHS.alumniDirectory,
  LANDING_PATHS.alumniStories,
] as const
export type DetailParent = (typeof DETAIL_PARENTS)[number]

/** Every landing route, as router patterns (App.tsx registers these). */
export const LANDING_ROUTE_PATTERNS: string[] = [...ALL, ...DETAIL_PARENTS.map((parent) => `${parent}/:slug`)]

/** List page of each organization kind (Ormawa / UKM). */
export const ORG_LIST_PATHS = { ormawa: LANDING_PATHS.ormawa, ukm: LANDING_PATHS.ukm } as const

export function detailPath(parent: DetailParent, slug: string): string {
  return `${parent}/${slug}`
}

/** `/student-life-journey/` and `/student-life-journey` are the same page. */
export function normalizePath(pathname: string): string {
  return pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname
}

export function isLandingPath(pathname: string): boolean {
  const path = normalizePath(pathname)
  if (ALL.includes(path)) return true
  // Detail pages: exactly one segment below a list page (unknown slugs get the landing's own not-found state).
  return DETAIL_PARENTS.some((parent) => path.startsWith(`${parent}/`) && !path.slice(parent.length + 1).includes('/'))
}

/** In-page section anchors (language-neutral ids — the same in every locale). */
export const SECTION_IDS = {
  home: 'beranda',
  about: 'tentang',
  integration: 'integrasi',
  services: 'layanan',
  academic: 'akademik',
  info: 'informasi',
  undergraduateTuition: 'biaya-sarjana',
  tuition: 'biaya',
} as const

export type SectionId = (typeof SECTION_IDS)[keyof typeof SECTION_IDS]

/** Anchors on /student-life-journey besides the pillars themselves (those use the pillar slug, data/pillars.ts). */
export const STUDENT_LIFE_IDS = {
  hero: 'sekilas',
  activities: 'kegiatan',
  contact: 'kontak-bkk',
} as const

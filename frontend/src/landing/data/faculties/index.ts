/**
 * Faculties of Unika Atma Jaya — the six names as the university lists
 * them (do not rename). Everything else about a faculty is OFFICIAL
 * information that the faculty has to supply; none is available in this
 * project yet, so those fields are left empty and the pages say "Menunggu
 * informasi resmi". Fill a field here and its section renders — never put
 * a made-up name, photo or statement in it.
 *
 * Study programs are not stored here: they come live from the PMB website's
 * public endpoint (lib/pmbPrograms.ts), matched by faculty name.
 */

export type FacultyKey =
  | 'pendidikan-dan-bahasa'
  | 'hukum'
  | 'kedokteran-dan-ilmu-kesehatan'
  | 'psikologi'
  | 'biosains-teknologi-dan-inovasi'
  | 'bisnis-dan-inovasi-sosial'

/** A person in an official role. Only add people the faculty has confirmed, with their own wording. */
export interface FacultyPerson {
  name: string
  /** Academic title(s), e.g. "Dr. … , M.Si." as the person writes it. */
  title?: string
  position: string
  /** Advisors: whose guidance this is. */
  role?: 'academic' | 'faculty' | 'organization'
  /** What the person is responsible for. */
  responsibility?: string
  photo?: string
  biography?: string
  education?: string[]
  interests?: string[]
  /** e.g. "Senin–Jumat, 09.00–15.00, Ruang Dekanat". */
  consultation?: string
  email?: string
}

export interface FacultyFacility {
  name: string
  description?: string
  /** URL of an official photo of this facility. */
  photo?: string
}

export interface Faculty {
  /** Also the URL segment: /fakultas/:slug. */
  slug: FacultyKey
  name: string
  /** Other spellings of the name in other data sources (e.g. the PMB website), for matching study programs. */
  aliases?: string[]
  /* ---- Official information (empty until supplied by the faculty) ---- */
  tagline?: string
  logo?: string
  coverImage?: string
  overview?: string[]
  history?: string[]
  /** Academic identity and educational focus. */
  identity?: string
  expertise?: string[]
  learning?: string
  research?: string
  studentDevelopment?: string
  dean?: FacultyPerson
  vision?: string
  mission?: string[]
  advisors?: FacultyPerson[]
  leadership?: FacultyPerson[]
  facilities?: FacultyFacility[]
  contact?: {
    address?: string
    email?: string
    phone?: string
    website?: string
    mapUrl?: string
    socialLinks?: Array<{ label: string; url: string }>
  }
  /* ---- Demonstration links (sample data, labelled as such on the page) ---- */
  /** Sample UKM shown as "UKM yang dapat diikuti" — UKM are open to every faculty. */
  ukmSuggestions: string[]
}

export const FACULTIES: readonly Faculty[] = [
  { slug: 'pendidikan-dan-bahasa', name: 'Fakultas Pendidikan dan Bahasa', ukmSuggestions: ['debat', 'teater', 'relawan-sosial'] },
  { slug: 'hukum', name: 'Fakultas Hukum', ukmSuggestions: ['debat', 'relawan-sosial', 'fotografi'] },
  { slug: 'kedokteran-dan-ilmu-kesehatan', name: 'Fakultas Kedokteran dan Ilmu Kesehatan', ukmSuggestions: ['relawan-sosial', 'pecinta-alam', 'paduan-suara'] },
  { slug: 'psikologi', name: 'Fakultas Psikologi', ukmSuggestions: ['relawan-sosial', 'teater', 'kerohanian-katolik'] },
  // The PMB data writes this one without the comma; isFacultyName() ignores punctuation.
  { slug: 'biosains-teknologi-dan-inovasi', name: 'Fakultas Biosains, Teknologi dan Inovasi', ukmSuggestions: ['robotika', 'fotografi', 'pecinta-alam'] },
  { slug: 'bisnis-dan-inovasi-sosial', name: 'Fakultas Bisnis dan Inovasi Sosial', ukmSuggestions: ['debat', 'relawan-sosial', 'musik-akustik'] },
]

export const FACULTY_KEYS = FACULTIES.map((f) => f.slug)

export function findFaculty(slug: string | undefined): Faculty | undefined {
  return FACULTIES.find((f) => f.slug === slug)
}

export function facultyName(key: FacultyKey): string {
  return FACULTIES.find((f) => f.slug === key)?.name ?? key
}

function fold(name: string): string {
  return name
    .toLowerCase()
    .replace(/^fakultas\s+/, '')
    .replace(/[^\p{L}\p{N}]+/gu, ' ')
    .trim()
}

/** Whether a faculty name from another source (e.g. a PMB study program) is this faculty. */
export function isFacultyName(faculty: Faculty, other: string | null | undefined): boolean {
  if (!other) return false
  const target = fold(other)
  return [faculty.name, ...(faculty.aliases ?? [])].some((n) => fold(n) === target)
}

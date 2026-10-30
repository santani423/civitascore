/**
 * Kegiatan Mahasiswa, Ormawa and UKM — demonstration data shapes.
 *
 * The entries in this folder are SAMPLE content for the UI (every page and
 * card says so). One Atma Jaya has no student-affairs module or API yet, so
 * none of this is official Unika Atma Jaya information. Content text is
 * Indonesian in every language (rendered with lang="id"); only the
 * interface around it is translated (i18n `affairs.*`).
 */
import type { FacultyKey } from '../faculties'
import type { PhotoKey } from '../photos'
import type { PillarKey } from '../pillars'

export type ActivityCategory = 'akademik' | 'olahraga' | 'seni-budaya' | 'pengabdian' | 'kepemimpinan' | 'kompetisi' | 'kerohanian'
export type ActivityStatus = 'upcoming' | 'ongoing' | 'completed'

export type OrgKind = 'ormawa' | 'ukm'
export type OrmawaCategory = 'eksekutif' | 'legislatif' | 'fakultas' | 'himpunan' | 'lainnya'
export type UkmCategory = 'olahraga' | 'seni' | 'musik' | 'budaya' | 'teknologi' | 'penalaran' | 'sosial' | 'kerohanian'
/** One of the six faculties (data/faculties). */
export type { FacultyKey } from '../faculties'

/** A point in time relative to today, so the demo always has upcoming, ongoing and past activities. */
export interface RelativeTime {
  /** Days from today: negative = past, 0 = today. */
  day: number
  /** 24-hour "HH:MM", Western Indonesia Time (WIB). */
  time: string
}

export interface ScheduleItem {
  /** Only for multi-day activities, e.g. "Hari 1". */
  day?: string
  /** "HH:MM". */
  time: string
  title: string
  detail?: string
}

export interface Activity {
  /** URL segment: /kegiatan/:slug — lowercase, hyphenated, stable. */
  slug: string
  title: string
  category: ActivityCategory
  start: RelativeTime
  end: RelativeTime
  /** Venue as shown to visitors, e.g. "Aula Kampus Semanggi" or "Daring (Zoom)". */
  venue: string
  /** The Ormawa/UKM in this folder that runs the activity (its detail page is linked). */
  organizer: { kind: OrgKind; slug: string }
  /** Student Life Journey pillars the activity supports (links to /student-life-journey). */
  pillars: PillarKey[]
  /** Who may take part, e.g. "Terbuka untuk seluruh mahasiswa aktif". */
  audience: string
  /** One or two sentences for cards (max ~180 characters). */
  excerpt: string
  /** Full description, one string per paragraph. */
  description: string[]
  objectives: string[]
  benefits: string[]
  schedule?: ScheduleItem[]
  /** Photos of the venue (campus photos in data/photos.ts). The first one is the cover; the rest show as "Foto lokasi". */
  photos?: PhotoKey[]
}

export interface OrgProgram {
  title: string
  description: string
}

export interface TrainingSession {
  /** e.g. "Selasa". */
  day: string
  /** e.g. "16.00–18.00". */
  time: string
  place: string
}

export interface Achievement {
  year: number
  title: string
}

/** Fields shared by Ormawa and UKM profiles. */
export interface OrgBase {
  /** URL segment: /daftar-ormawa/:slug or /daftar-ukm/:slug. */
  slug: string
  name: string
  /** Short name / acronym, also drawn as the monogram when there is no logo. */
  short: string
  /** One or two sentences for cards (max ~170 characters). */
  excerpt: string
  description: string[]
  vision?: string
  mission?: string[]
  /** 3–5 short tags (areas of focus / main interests). */
  focus: string[]
  programs: OrgProgram[]
  achievements?: Achievement[]
  /** Only real, verified links belong here — the sample entries leave it empty. */
  socialLinks?: Array<{ label: string; href: string }>
  /** Logo URL; sample entries have none (a monogram is drawn instead). */
  logo?: string
  photos?: PhotoKey[]
}

export interface Ormawa extends OrgBase {
  kind: 'ormawa'
  category: OrmawaCategory
  /** Omitted for university-level bodies. */
  faculty?: FacultyKey
}

export interface Ukm extends OrgBase {
  kind: 'ukm'
  category: UkmCategory
  training?: TrainingSession[]
}

export type Org = Ormawa | Ukm

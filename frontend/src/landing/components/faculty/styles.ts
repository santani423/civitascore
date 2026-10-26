import { BookOpen, Brain, BriefcaseBusiness, FlaskConical, Scale, Stethoscope } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { FacultyKey } from '../../data/faculties'
import type { CategoryStyle } from '../student-affairs/styles'

/** Cover motif per faculty — a field symbol on a landing-token surface, not an official emblem. */
export const FACULTY_STYLE: Record<FacultyKey, CategoryStyle> = {
  'pendidikan-dan-bahasa': { icon: BookOpen, tone: 'gold' },
  hukum: { icon: Scale, tone: 'deep' },
  'kedokteran-dan-ilmu-kesehatan': { icon: Stethoscope, tone: 'brand' },
  psikologi: { icon: Brain, tone: 'soft' },
  'biosains-teknologi-dan-inovasi': { icon: FlaskConical, tone: 'band' },
  'bisnis-dan-inovasi-sosial': { icon: BriefcaseBusiness, tone: 'accent' },
}

/** Section anchors on /fakultas/:slug, in page order. */
export const FACULTY_SECTION_IDS = {
  about: 'tentang',
  dean: 'dekan',
  visionMission: 'visi-misi',
  programs: 'program-studi',
  advisors: 'penasehat',
  leadership: 'pengurus',
  studentLife: 'kehidupan-mahasiswa',
  ormawa: 'ormawa',
  ukm: 'ukm',
  activities: 'kegiatan',
  achievements: 'prestasi',
  facilities: 'fasilitas',
  contact: 'kontak',
} as const

/** Readable name of a PMB degree level ("S1" → "Sarjana"); unknown levels are shown as they are. */
export const DEGREE_LEVELS: Record<string, 'diploma3' | 'diploma4' | 'bachelor' | 'master' | 'doctor' | 'profession'> = {
  D3: 'diploma3',
  D4: 'diploma4',
  S1: 'bachelor',
  S2: 'master',
  S3: 'doctor',
  PROFESI: 'profession',
}

/** "Sarjana (S1)" etc. in the visitor's language. */
export function useDegreeLabel(): (level: string) => string {
  const { m } = useLandingI18n()
  return (level: string) => {
    const key = DEGREE_LEVELS[level.trim().toUpperCase()]
    return key ? `${m.faculty.programs.levels[key]} (${level})` : level
  }
}

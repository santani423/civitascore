import type { Messages } from '../i18n/messages/id'
import raw from './tuition-postgraduate-2026-2027.json'

export type TuitionProgramKey = keyof Messages['tuition']['programs']
export type TuitionNoteKey = keyof Messages['tuition']['notes']

export interface TuitionTotal {
  /** Estimated total to graduation without matriculation; `null` where the source table shows "-". */
  base: number | null
  withMatriculation: number | null
  note: TuitionNoteKey | null
}

export interface TuitionStudy {
  /** Official programme name — shown as-is in every language. */
  name: string
  alumni: boolean
  spp: number
  ukt: number
  matriculation: number | null
  /** One entry per `durations` item of the programme, in the same order. */
  totals: TuitionTotal[]
}

export interface TuitionProgram {
  key: TuitionProgramKey
  hasMatriculation: boolean
  durations: Array<{ semesters: string; note: TuitionNoteKey | null }>
  studies: TuitionStudy[]
}

export interface TuitionTable {
  academicYear: string
  revision: string
  currency: 'IDR'
  /** Footnotes in display order; their text lives in the dictionaries (`tuition.notes`). */
  notes: TuitionNoteKey[]
  programs: TuitionProgram[]
}

/**
 * Postgraduate tuition, T.A. 2026/2027 (revision 2.0) — hardcoded from
 * docs/refrensi atmajaya/tabel-tarif-kuliah-pascasarjana-2026-2027.md.
 * Static data only: no API call. Replace the JSON file when a new table is published.
 */
export const TUITION = raw as TuitionTable

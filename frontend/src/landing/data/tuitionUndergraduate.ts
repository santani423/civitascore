import type { Messages } from '../i18n/messages/id'
import raw from './tuition-undergraduate-2027-2028.json'

export type UndergraduateProgramKey = keyof Messages['undergraduateTuition']['programs']
export type InitialPaymentKey = keyof Messages['undergraduateTuition']['initialPayment']['categories']

export interface UndergraduateProgram {
  key: UndergraduateProgramKey
  initialPayment: Record<InitialPaymentKey, number>
}

export interface UndergraduateStudy {
  /** Official programme name — shown as-is in every language. */
  name: string
  /** Struck through in the source table: the program's special rate is the one that applies. */
  regularSpp: number
  semesterFee: number
  specialSpp: Record<UndergraduateProgramKey, number>
}

export interface UndergraduateTuitionTable {
  academicYear: string
  currency: 'IDR'
  /** One-off uniform fee; only these two study programs have one. */
  uniform: { tourism: number; mechanicalEngineering: number }
  programs: UndergraduateProgram[]
  studies: UndergraduateStudy[]
}

/**
 * Undergraduate (S1) tuition for prospective students of 2027/2028 — hardcoded from
 * docs/refrensi atmajaya/tarif-kuliah-atma-jaya-2027-2028.md.
 * Static data only: no API call. Replace the JSON file when a new table is published.
 */
export const UNDERGRADUATE_TUITION = raw as UndergraduateTuitionTable

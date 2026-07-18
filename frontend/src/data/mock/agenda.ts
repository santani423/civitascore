import type { AgendaItem } from '@/types/dashboard'

const isoDate = (offsetDays: number) => new Date(Date.now() + offsetDays * 24 * 60 * 60_000).toISOString()

export const ACADEMIC_AGENDA: AgendaItem[] = [
  { id: 'agenda-1', title: 'Periode Pengisian KRS', category: 'krs', startDate: isoDate(-2), endDate: isoDate(2) },
  { id: 'agenda-2', title: 'Ujian Tengah Semester', category: 'exam', startDate: isoDate(12), endDate: isoDate(18) },
  { id: 'agenda-3', title: 'Batas Pembayaran UKT', category: 'payment', startDate: isoDate(6), endDate: isoDate(6) },
  { id: 'agenda-4', title: 'Batas Input Nilai UTS', category: 'grading', startDate: isoDate(20), endDate: isoDate(24) },
  { id: 'agenda-5', title: 'Ujian Akhir Semester', category: 'exam', startDate: isoDate(58), endDate: isoDate(64) },
  { id: 'agenda-6', title: 'Wisuda Periode I', category: 'graduation', startDate: isoDate(75), endDate: isoDate(75) },
]

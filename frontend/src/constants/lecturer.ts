import type { Lecturer, LecturerEducationLevel, LecturerEmploymentStatus, LecturerFunctionalRank } from '@/types/academic'
import type { BadgeVariant } from '@/components/ui/Badge'
import type { SelectOption } from '@/components/ui/Select'

/** Label tampilan enum dosen — nilai mengikuti Modules\Academic\Enums\Lecturer*. */
export const EMPLOYMENT_STATUS_LABEL: Record<LecturerEmploymentStatus, string> = {
  permanent: 'Dosen Tetap',
  contract: 'Dosen Kontrak',
  honorary: 'Dosen Tidak Tetap / Luar Biasa',
}

export const FUNCTIONAL_RANK_LABEL: Record<LecturerFunctionalRank, string> = {
  none: 'Tenaga Pengajar',
  asisten_ahli: 'Asisten Ahli',
  lektor: 'Lektor',
  lektor_kepala: 'Lektor Kepala',
  guru_besar: 'Guru Besar',
}

export const EDUCATION_LEVEL_LABEL: Record<LecturerEducationLevel, string> = {
  s1: 'S1',
  s2: 'S2',
  s3: 'S3',
}

export function toOptions<T extends string>(labels: Record<T, string>): SelectOption[] {
  return (Object.entries(labels) as Array<[T, string]>).map(([value, label]) => ({ value, label }))
}

/** Status akun login dosen untuk badge — "Belum ada akun" bila belum diprovisi. */
export function lecturerAccountBadge(lecturer: Lecturer): { label: string; variant: BadgeVariant } {
  if (!lecturer.has_account) return { label: 'Belum ada akun', variant: 'warning' }
  if (lecturer.account && !lecturer.account.is_active) return { label: 'Akun nonaktif', variant: 'danger' }
  return { label: 'Akun aktif', variant: 'success' }
}

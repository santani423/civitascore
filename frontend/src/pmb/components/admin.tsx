import { useEffect, useState, type ReactNode } from 'react'
import { pmbAdmin } from '@/pmb/api/admin'
import { PageHeader } from '@/components/ui/PageHeader'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import { ROUTES } from '@/constants/routes'
import { PMB } from '@/pmb/routes'

/** Slug permission PMB (Modules\Admission\Support\PmbPermission). */
export const P = {
  CONFIG_READ: 'pmb_config.read', CONFIG_MANAGE: 'pmb_config.update', CONFIG_PUBLISH: 'pmb_config.publish', FEES_MANAGE: 'pmb_fees.update',
  CONTENT_MANAGE: 'pmb_content.update', PROGRAM_PROFILE_MANAGE: 'pmb_program_profiles.update',
  APPLICATIONS_READ: 'pmb_applications.read', APPLICATIONS_CORRECT: 'pmb_applications.update', APPLICATIONS_ASSISTED: 'pmb_applications.create',
  SENSITIVE_READ: 'pmb_sensitive.read', DOCUMENTS_DOWNLOAD: 'pmb_documents.read',
  VERIFICATION_DECIDE: 'pmb_verification.approve', VERIFICATION_ASSIGN: 'pmb_verification.update', VERIFICATION_OVERRIDE: 'pmb_verification.finalize',
  PAYMENTS_READ: 'pmb_payments.read', PAYMENTS_VERIFY: 'pmb_payments.approve', PAYMENTS_RECONCILE: 'pmb_payments.finalize', REFUNDS_REQUEST: 'pmb_refunds.create',
  EXAMS_MANAGE: 'pmb_exams.update', EXAMS_ATTENDANCE: 'pmb_exam_attendance.update', SCORES_INPUT: 'pmb_scores.create', SCORES_IMPORT: 'pmb_scores.import', SCORES_LOCK: 'pmb_scores.finalize',
  SELECTION_MANAGE: 'pmb_selection.update', SELECTION_LOCK: 'pmb_selection.finalize', SELECTION_SUBMIT: 'pmb_selection.publish',
  RESULTS_READ: 'pmb_results.read', RESULTS_CORRECT: 'pmb_results.update', WAITLIST: 'pmb_waitlist.update',
  REREG_READ: 'pmb_reregistration.read', REREG_DECIDE: 'pmb_reregistration.approve', REREG_EXTEND: 'pmb_reregistration.update',
  CONVERSION_READ: 'pmb_conversion.read', CONVERSION_RETRY: 'pmb_conversion.update', NIM: 'pmb_nim.update',
  REPORTS_READ: 'pmb_reports.read', REPORTS_EXPORT: 'pmb_reports.export', BROADCAST: 'pmb_communication.publish', CHAT: 'pmb_communication.update', AUDIT: 'pmb_audit.read',
} as const

export function Can({ permission, children, fallback = null }: { permission: string | string[]; children: ReactNode; fallback?: ReactNode }) {
  return usePermission(permission) ? <>{children}</> : <>{fallback}</>
}

export function AdminPage({ title, description, actions, children, trail = [] }: { title: string; description?: string; actions?: ReactNode; children: ReactNode; trail?: Array<{ label: string; path?: string }> }) {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader title={title} description={description} actions={actions} breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'PMB', path: PMB.admin.dashboard }, ...trail, ...(trail.length === 0 ? [] : [])]} />
      {children}
    </div>
  )
}

export interface Lookups {
  academic_terms: Array<{ id: string; academic_year: string; semester: string; is_current: boolean }>
  faculties: Array<{ id: string; code: string; name: string }>
  study_programs: Array<{ id: string; faculty_id: string; code: string; name: string; degree_level: string; is_active: boolean }>
  admission_paths: Array<{ id: string; code: string; name: string; category: string; requires_exam: boolean; is_active: boolean }>
  study_systems: Array<{ id: string; code: string; name: string; is_active: boolean }>
  document_types: Array<{ id: string; code: string; name: string; allows_multiple: boolean }>
  form_templates: Array<{ id: string; code: string; name: string; version: number; status: string }>
  selection_schemes: Array<{ id: string; code: string; name: string; version: number; status: string }>
  periods: Array<{ id: string; code: string; name: string; status: string }>
  scholarships: Array<{ id: string; name: string }>
  enums: Record<string, string[]>
}

let cache: Promise<Lookups> | null = null

/** Data rujukan form admin (GET /pmb/admin/lookups), di-cache per sesi halaman. */
export function useLookups(refreshKey = 0): Lookups | null {
  const [data, setData] = useState<Lookups | null>(null)

  useEffect(() => {
    if (refreshKey > 0) cache = null
    cache ??= pmbAdmin.get<Lookups>('lookups')
    cache.then(setData).catch(() => {
      cache = null
    })
  }, [refreshKey])

  return data
}

export function invalidateLookups(): void {
  cache = null
}

export const options = <T extends { id: string }>(rows: T[] | undefined, label: (row: T) => string) => (rows ?? []).map((row) => ({ value: row.id, label: label(row) }))

/** Pemilih periode → gelombang → gelombang-jalur, dipakai halaman seleksi/ujian/laporan. */
export function WavePathPicker({ value, onChange, level = 'wavePath' }: { value: { period?: string; wave?: string; wavePath?: string }; onChange: (v: { period?: string; wave?: string; wavePath?: string }) => void; level?: 'period' | 'wave' | 'wavePath' }) {
  const lookups = useLookups()
  const [waves, setWaves] = useState<Array<{ id: string; name: string }>>([])
  const [wavePaths, setWavePaths] = useState<Array<{ id: string; admission_path: { name: string } | null }>>([])

  useEffect(() => {
    if (!value.period || level === 'period') return setWaves([])
    pmbAdmin.get<Array<{ id: string; name: string }>>(`periods/${value.period}/waves`).then(setWaves).catch(() => setWaves([]))
  }, [value.period, level])

  useEffect(() => {
    if (!value.wave || level !== 'wavePath') return setWavePaths([])
    pmbAdmin.get<Array<{ id: string; admission_path: { name: string } | null }>>(`waves/${value.wave}/paths`).then(setWavePaths).catch(() => setWavePaths([]))
  }, [value.wave, level])

  return (
    <div className="flex flex-wrap gap-3">
      <Select label="Periode" placeholder="Pilih periode" options={options(lookups?.periods, (p) => `${p.name} (${p.code})`)} value={value.period ?? ''} onChange={(e) => onChange({ period: e.target.value || undefined })} />
      {level !== 'period' && <Select label="Gelombang" placeholder="Pilih gelombang" options={options(waves, (w) => w.name)} value={value.wave ?? ''} onChange={(e) => onChange({ period: value.period, wave: e.target.value || undefined })} />}
      {level === 'wavePath' && <Select label="Jalur" placeholder="Pilih jalur" options={options(wavePaths, (wp) => wp.admission_path?.name ?? wp.id)} value={value.wavePath ?? ''} onChange={(e) => onChange({ ...value, wavePath: e.target.value || undefined })} />}
    </div>
  )
}

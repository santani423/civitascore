import { Card } from '@/components/ui/Card'
import { Select } from '@/components/ui/Select'
import { Input } from '@/components/ui/Input'
import type { DashboardFilterOptions, DashboardQueryFilters } from '@/types/dashboard'

export interface DashboardFilterBarProps {
  options: DashboardFilterOptions
  value: DashboardQueryFilters
  onChange: (next: DashboardQueryFilters) => void
}

/** Universitas sendiri tidak difilter di sini — itu urusan Tenant Switcher di Header (Super Admin) / membership default (pengguna tenant). */
export function DashboardFilterBar({ options, value, onChange }: DashboardFilterBarProps) {
  const studyProgramOptions = value.faculty_id
    ? options.study_programs.filter((program) => program.faculty_id === value.faculty_id)
    : options.study_programs

  return (
    <Card className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
      <Select
        label="Periode Akademik"
        placeholder="Semua periode"
        value={value.academic_term_id ?? ''}
        onChange={(event) => onChange({ ...value, academic_term_id: event.target.value || undefined })}
        options={options.academic_terms.map((term) => ({
          value: term.id,
          label: term.is_current ? `${term.label} (berjalan)` : term.label,
        }))}
      />
      <Select
        label="Fakultas"
        placeholder="Semua fakultas"
        value={value.faculty_id ?? ''}
        onChange={(event) =>
          onChange({ ...value, faculty_id: event.target.value || undefined, study_program_id: undefined })
        }
        options={options.faculties.map((faculty) => ({ value: faculty.id, label: faculty.name }))}
      />
      <Select
        label="Program Studi"
        placeholder="Semua program studi"
        value={value.study_program_id ?? ''}
        onChange={(event) => onChange({ ...value, study_program_id: event.target.value || undefined })}
        options={studyProgramOptions.map((program) => ({ value: program.id, label: program.name }))}
      />
      <Input
        label="Dari Tanggal"
        type="date"
        value={value.date_from ?? ''}
        onChange={(event) => onChange({ ...value, date_from: event.target.value || undefined })}
      />
      <Input
        label="Sampai Tanggal"
        type="date"
        value={value.date_to ?? ''}
        onChange={(event) => onChange({ ...value, date_to: event.target.value || undefined })}
      />
    </Card>
  )
}

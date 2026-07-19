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
    <Card className="p-4">
      {/* Horizontal pada layar lebar (satu baris, scroll kalau kepepet); turun
          jadi wrap alami di layar sempit — tiap field punya lebar minimum
          sendiri jadi tidak gepeng saat wrap. */}
      <div className="flex flex-wrap items-end gap-3 lg:flex-nowrap lg:overflow-x-auto">
        <div className="w-full min-w-[180px] sm:w-auto sm:flex-1">
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
        </div>
        <div className="w-full min-w-[160px] sm:w-auto sm:flex-1">
          <Select
            label="Fakultas"
            placeholder="Semua fakultas"
            value={value.faculty_id ?? ''}
            onChange={(event) =>
              onChange({ ...value, faculty_id: event.target.value || undefined, study_program_id: undefined })
            }
            options={options.faculties.map((faculty) => ({ value: faculty.id, label: faculty.name }))}
          />
        </div>
        <div className="w-full min-w-[180px] sm:w-auto sm:flex-1">
          <Select
            label="Program Studi"
            placeholder="Semua program studi"
            value={value.study_program_id ?? ''}
            onChange={(event) => onChange({ ...value, study_program_id: event.target.value || undefined })}
            options={studyProgramOptions.map((program) => ({ value: program.id, label: program.name }))}
          />
        </div>
        <div className="w-full min-w-[150px] sm:w-auto sm:flex-1">
          <Input
            label="Dari Tanggal"
            type="date"
            value={value.date_from ?? ''}
            onChange={(event) => onChange({ ...value, date_from: event.target.value || undefined })}
          />
        </div>
        <div className="w-full min-w-[150px] sm:w-auto sm:flex-1">
          <Input
            label="Sampai Tanggal"
            type="date"
            value={value.date_to ?? ''}
            onChange={(event) => onChange({ ...value, date_to: event.target.value || undefined })}
          />
        </div>
      </div>
    </Card>
  )
}
